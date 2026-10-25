<?php

use App\Enums\Module;
use App\Enums\Role;
use App\Events\RegistrationPaid;
use App\Mail\CheckInConfirmedMail;
use App\Mail\EventReminderMail;
use App\Mail\PassMail;
use App\Mail\RegistrationConfirmedMail;
use App\Mail\TestMail;
use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\SeasonPass;
use App\Models\User;
use App\Notifications\Jobs\SendWhatsAppTemplate;
use App\Notifications\NotificationDelivery;
use App\Notifications\NotificationSettings;
use App\Notifications\OrgMailer;
use App\Payments\PaymentStatus;
use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\WhatsAppException;
use App\Services\WhatsApp\WhatsAppGatewayFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    config([
        'eventflow.pass.signing_key' => str_repeat('ef', 32),
        'mail.default' => 'array',
        'mail.from.address' => 'passes@eventflow.test',
        'queue.default' => 'sync',
    ]);
    Storage::fake(config('eventflow.pass.disk'));
});

/** The demo marathon (branding from YAML) with an owner and one paid registration. */
function notifiedRegistration(array $attributes = []): Registration
{
    Artisan::call('events:sync', ['paths' => [base_path('events/demo/example-marathon.yaml')]]);
    $event = Event::withoutGlobalScopes()->where('slug', 'bengaluru-city-marathon-2027')->sole();
    User::factory()->create(['org_id' => $event->org_id, 'role' => Role::Owner, 'email' => 'owner@runners.test']);

    // Notification add-ons are optional modules: they need an active Season Pass
    // that includes them (covers today and the event date, for the reminder tests).
    SeasonPass::withoutGlobalScopes()->create([
        'org_id' => $event->org_id,
        'plan' => 'Test season',
        'starts_on' => '2026-01-01',
        'ends_on' => '2028-12-31',
        'status' => 'active',
        'modules' => Module::cases(),
    ]);

    return Registration::factory()->for($event)->paid()->create([
        'org_id' => $event->org_id,
        'pass_no' => Registration::newPassNo(),
        'participant_name' => 'Asha Rao',
        'email' => 'asha@example.com',
        'phone' => '98765 43210',
        'distance_code' => 'HM',
        'amount_paise' => 149900,
        'gateway_payment_id' => 'pay_123',
        'answers' => ['age_band' => 'Open'],
        ...$attributes,
    ]);
}

function notificationMails(): array
{
    return app('mailer')->getSymfonyTransport()->messages()->map->getOriginalMessage()->all();
}

function enableWhatsApp(Org $org, array $overrides = []): NotificationSettings
{
    return tap(NotificationSettings::for($org)->fill([
        'whatsapp_enabled' => true,
        'whatsapp_phone_number_id' => '1098765432',
        'whatsapp_token' => 'EAAG-secret-token',
        'whatsapp_template_confirmed' => 'registration_confirmed',
        'whatsapp_template_reminder' => 'event_reminder',
        ...$overrides,
    ]))->save();
}

it('emails a branded confirmation with the pass PDF after payment', function () {
    $registration = notifiedRegistration();

    event(new RegistrationPaid($registration->id));

    $messages = notificationMails();
    expect($messages)->toHaveCount(1);
    $mail = $messages[0];
    $html = $mail->getHtmlBody();

    expect($mail->getTo()[0]->getAddress())->toBe('asha@example.com')
        ->and($mail->getFrom()[0]->getAddress())->toBe('passes@eventflow.test')
        ->and($mail->getFrom()[0]->getName())->toBe('Demo Runners')
        ->and($mail->getReplyTo()[0]->getAddress())->toBe('owner@runners.test')
        ->and($mail->getSubject())->toContain('Bengaluru City Marathon 2027')->toContain($registration->pass_no)
        ->and(collect($mail->getAttachments())->map->getContentType()->sort()->values()->all())->toBe(['application/pdf', 'image/png'])
        ->and($html)->toContain('#ff6e61') // event secondary (header)
        ->toContain('#d9ff45') // event primary (accent + button)
        ->toContain($registration->pass_no)
        ->toContain('Half Marathon (21.1K)')
        ->toContain('Sree Kanteerava Stadium')
        ->toContain('View pass')
        ->toContain('calendar.google.com')
        ->and($mail->getTextBody())->toContain($registration->pass_no);

    $delivery = NotificationDelivery::withoutGlobalScopes()->sole();
    expect($delivery->only(['channel', 'type', 'status', 'recipient']))->toBe([
        'channel' => 'mail', 'type' => 'registration_confirmed', 'status' => 'sent', 'recipient' => 'asha@example.com',
    ]);
});

it('queues the confirmation instead of sending during the request', function () {
    Mail::fake();
    $registration = notifiedRegistration();
    config(['queue.default' => 'sync']);

    event(new RegistrationPaid($registration->id));

    Mail::assertQueued(PassMail::class);
    expect(new PassMail($registration->id))->toBeInstanceOf(RegistrationConfirmedMail::class)
        ->toBeInstanceOf(ShouldQueue::class);
});

it('uses custom SMTP only when enabled, verified and in the Season Pass', function () {
    $registration = notifiedRegistration();
    $org = $registration->event->org;
    $settings = NotificationSettings::for($org)->fill([
        'smtp_enabled' => true, 'smtp_host' => 'smtp.runners.test', 'smtp_port' => 587, 'smtp_encryption' => 'tls',
        'smtp_username' => 'mailer', 'smtp_password' => 'p4ss', 'smtp_from_email' => 'hello@runners.test',
    ]);

    expect(OrgMailer::mailerName($org, $settings))->toBe('array'); // not verified yet

    $settings->smtp_verified_at = now();
    expect(OrgMailer::mailerName($org, $settings))->toBe("org_{$org->id}")
        ->and(config("mail.mailers.org_{$org->id}"))->toMatchArray(['host' => 'smtp.runners.test', 'port' => 587, 'scheme' => 'smtp', 'password' => 'p4ss'])
        ->and(OrgMailer::from($org, $settings)->getAddress())->toBe('hello@runners.test');

    $org->update(['modules' => ['email_branding' => false]]);
    expect(OrgMailer::mailerName($org->fresh(), $settings))->toBe('array');
});

it('stores SMTP passwords and WhatsApp tokens encrypted', function () {
    $registration = notifiedRegistration();
    enableWhatsApp($registration->event->org, ['smtp_password' => 'p4ss-plain']);

    $row = DB::table('notification_settings')->first();
    expect($row->smtp_password)->not->toContain('p4ss-plain')
        ->and($row->whatsapp_token)->not->toContain('EAAG-secret-token')
        ->and(NotificationSettings::withoutGlobalScopes()->first()->whatsapp_token)->toBe('EAAG-secret-token');
});

it('makes no WhatsApp calls when the module is off and still emails', function () {
    Http::fake();
    $registration = notifiedRegistration();
    enableWhatsApp($registration->event->org, ['whatsapp_enabled' => false]);

    event(new RegistrationPaid($registration->id));

    Http::assertNothingSent();
    expect(notificationMails())->toHaveCount(1);
});

it('sends the WhatsApp confirmation template with the pass link when on', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ABC']]])]);
    $registration = notifiedRegistration();
    enableWhatsApp($registration->event->org);

    event(new RegistrationPaid($registration->id));

    Http::assertSent(function (HttpRequest $request) use ($registration) {
        $template = $request['template'];

        return $request->url() === 'https://graph.facebook.com/v21.0/1098765432/messages'
            && $request->hasHeader('Authorization', 'Bearer EAAG-secret-token')
            && $request['to'] === '919876543210'
            && $template['name'] === 'registration_confirmed'
            && str_contains($template['components'][0]['parameters'][0]['document']['link'], "/passes/{$registration->id}/pass.pdf")
            && array_column($template['components'][1]['parameters'], 'text') === [
                'Asha Rao', 'Bengaluru City Marathon 2027', $registration->pass_no, 'Sun 17 Jan 2027, 5:30 AM',
            ];
    });

    $whatsapp = NotificationDelivery::withoutGlobalScopes()->where('channel', 'whatsapp')->sole();
    expect($whatsapp->status)->toBe('sent')->and($whatsapp->provider_message_id)->toBe('wamid.ABC')
        ->and(notificationMails())->toHaveCount(1); // email still goes out
});

it('records WhatsApp failures for retry without touching the registration', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template name does not exist']], 404)]);
    $registration = notifiedRegistration();
    enableWhatsApp($registration->event->org);

    expect(fn () => (new SendWhatsAppTemplate($registration->id, SendWhatsAppTemplate::CONFIRMED))->handle(app(WhatsAppGatewayFactory::class)))
        ->toThrow(WhatsAppException::class, 'Template name does not exist');

    $delivery = NotificationDelivery::withoutGlobalScopes()->sole();
    expect($delivery->status)->toBe('queued')->and($delivery->error)->toContain('Template name does not exist')
        ->and($registration->fresh()->status)->toBe(PaymentStatus::Paid);

    (new SendWhatsAppTemplate($registration->id, SendWhatsAppTemplate::CONFIRMED))->failed(new WhatsAppException('gave up'));
    expect($delivery->fresh()->status)->toBe('failed');
});

it('normalises Indian mobile numbers for WhatsApp', function (string $in, ?string $out) {
    expect(PhoneNumber::toWhatsApp($in))->toBe($out);
})->with([
    ['98765 43210', '919876543210'],
    ['+91 98765-43210', '919876543210'],
    ['098765 43210', '919876543210'],
    ['12345', null],
]);

it('queues one reminder per registration for events in the next 24 hours', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.R']]])]);
    $registration = notifiedRegistration();
    enableWhatsApp($registration->event->org);
    $this->travelTo($registration->event->date->subHours(20));

    Mail::fake();
    $this->artisan('notifications:send-reminders')->assertSuccessful();
    $this->artisan('notifications:send-reminders')->assertSuccessful();

    Mail::assertQueuedCount(1);
    Mail::assertQueued(EventReminderMail::class, fn ($mail) => $mail->hasTo('asha@example.com'));
    Http::assertSentCount(1);
    Http::assertSent(fn (HttpRequest $r) => $r['template']['name'] === 'event_reminder');
});

it('skips reminders when the organiser turned them off', function () {
    $registration = notifiedRegistration();
    NotificationSettings::for($registration->event->org)->fill(['send_reminders' => false])->save();
    $this->travelTo($registration->event->date->subHours(2));

    Mail::fake();
    $this->artisan('notifications:send-reminders');
    Mail::assertNothingQueued();
});

it('emails a check-in confirmation only when the organiser turns it on', function () {
    Mail::fake();
    $registration = notifiedRegistration();

    $registration->update(['checked_in_at' => now()]);
    Mail::assertNothingQueued();

    NotificationSettings::for($registration->event->org)->fill(['send_checkin_confirmation' => true])->save();
    $other = Registration::factory()->for($registration->event)->paid()->create(['org_id' => $registration->org_id, 'pass_no' => Registration::newPassNo()]);
    $other->update(['checked_in_at' => now()]);

    Mail::assertQueued(CheckInConfirmedMail::class, fn ($mail) => $mail->registrationId === $other->id);
});

it('serves the pass PDF only on a valid signed link', function () {
    $registration = notifiedRegistration();
    $url = URL::temporarySignedRoute('notifications.pass', now()->addDay(), ['registration' => $registration->id]);

    $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->get(route('notifications.pass', $registration))->assertForbidden();
});

it('lets owners manage notification settings and test SMTP', function () {
    $registration = notifiedRegistration();
    $owner = User::withoutGlobalScopes()->where('email', 'owner@runners.test')->sole();
    $owner->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($owner)->get('/dashboard/settings/notifications')
        ->assertOk()->assertSee('Platform email')->assertSee('Custom SMTP')->assertSee('Setup checklist')
        ->assertSee('Registration confirmed');

    $this->actingAs($owner)->put('/dashboard/settings/notifications/smtp', [
        'smtp_enabled' => '1', 'smtp_host' => 'smtp.runners.test', 'smtp_port' => '587', 'smtp_encryption' => 'tls',
        'smtp_username' => 'mailer', 'smtp_password' => 'secret', 'smtp_from_email' => 'hello@runners.test', 'smtp_from_name' => 'Runners',
    ])->assertSessionHasNoErrors();

    $settings = NotificationSettings::withoutGlobalScopes()->sole();
    expect($settings->smtp_password)->toBe('secret')->and($settings->smtp_verified_at)->toBeNull();

    Mail::fake();
    $this->actingAs($owner)->post('/dashboard/settings/notifications/smtp/test', ['test_email' => 'owner@runners.test'])
        ->assertSessionHasNoErrors();
    Mail::assertSent(TestMail::class);
    expect($settings->fresh()->smtp_verified_at)->not->toBeNull();

    // A blank password keeps the stored one; changing the host needs a new test.
    $this->actingAs($owner)->put('/dashboard/settings/notifications/smtp', [
        'smtp_enabled' => '1', 'smtp_host' => 'smtp2.runners.test', 'smtp_port' => '465', 'smtp_encryption' => 'ssl',
        'smtp_username' => 'mailer', 'smtp_password' => '', 'smtp_from_email' => 'hello@runners.test',
    ])->assertSessionHasNoErrors();
    expect($settings->fresh()->smtp_password)->toBe('secret')->and($settings->fresh()->smtp_verified_at)->toBeNull();

    $this->actingAs($owner)->get('/dashboard/settings/notifications/preview/registration-confirmed')
        ->assertOk()->assertSee('#ff6e61', false)->assertSee($registration->pass_no);
});

it('reports a failed SMTP test without saving it as verified', function () {
    $registration = notifiedRegistration();
    $owner = User::withoutGlobalScopes()->where('email', 'owner@runners.test')->sole();
    NotificationSettings::for($registration->event->org)->fill([
        'smtp_host' => '127.0.0.1', 'smtp_port' => 1, 'smtp_encryption' => 'none', 'smtp_from_email' => 'hello@runners.test',
    ])->save();

    $this->actingAs($owner)->post('/dashboard/settings/notifications/smtp/test', ['test_email' => 'owner@runners.test'])
        ->assertSessionHasErrors('smtp_test');
    expect(NotificationSettings::withoutGlobalScopes()->sole()->smtp_verified_at)->toBeNull();
});

it('hides paid add-ons outside the Season Pass and blocks non-owners', function () {
    $registration = notifiedRegistration();
    $org = $registration->event->org;
    $org->update(['modules' => ['sms_whatsapp' => false, 'email_branding' => false]]);
    $owner = User::withoutGlobalScopes()->where('email', 'owner@runners.test')->sole();

    $this->actingAs($owner)->get('/dashboard/settings/notifications')
        ->assertOk()->assertSee('not in your Season Pass')->assertDontSee('Setup checklist');
    $this->actingAs($owner)->put('/dashboard/settings/notifications/whatsapp', ['whatsapp_provider' => 'meta', 'whatsapp_language' => 'en'])
        ->assertForbidden();

    $staff = User::factory()->create(['org_id' => $org->id, 'role' => Role::Staff]);
    $this->actingAs($staff)->get('/dashboard/settings/notifications')->assertForbidden();
});

it('needs the token before WhatsApp can be switched on, and tests with hello_world', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.T']]])]);
    $registration = notifiedRegistration();
    $owner = User::withoutGlobalScopes()->where('email', 'owner@runners.test')->sole();
    $form = [
        'whatsapp_enabled' => '1', 'whatsapp_provider' => 'meta', 'whatsapp_phone_number_id' => '1098765432',
        'whatsapp_template_confirmed' => 'registration_confirmed', 'whatsapp_language' => 'en',
    ];

    $this->actingAs($owner)->put('/dashboard/settings/notifications/whatsapp', $form)->assertSessionHasErrors('whatsapp_token');
    $this->actingAs($owner)->put('/dashboard/settings/notifications/whatsapp', $form + ['whatsapp_token' => 'EAAG-x'])->assertSessionHasNoErrors();
    $this->actingAs($owner)->post('/dashboard/settings/notifications/whatsapp/test', ['test_phone' => '9876543210'])->assertSessionHasNoErrors();

    Http::assertSent(fn (HttpRequest $r) => $r['template']['name'] === 'hello_world' && $r['to'] === '919876543210');
    expect(NotificationSettings::withoutGlobalScopes()->sole()->usesWhatsApp($registration->event->org->fresh()))->toBeTrue();
});
