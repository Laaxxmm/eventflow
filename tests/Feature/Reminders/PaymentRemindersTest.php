<?php

use App\Enums\Module;
use App\Enums\Role;
use App\Events\RegistrationPaid;
use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\RegistrationReminderLog;
use App\Models\SeasonPass;
use App\Models\User;
use App\Notifications\NotificationSettings;
use App\Payments\PaymentStatus;
use App\Reminders\Jobs\SendPaymentReminder;
use App\Reminders\ReminderSchedule;
use App\Services\WhatsApp\WhatsAppGatewayFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/*
 * Session N2: unpaid payment reminders. Times are pinned to a weekday morning in IST so quiet
 * hours (21:00–08:00 Asia/Kolkata) only apply where a test asks for them.
 */

beforeEach(function () {
    config(['mail.default' => 'array', 'mail.from.address' => 'passes@eventflow.test', 'queue.default' => 'sync']);
    $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00', 'Asia/Kolkata'));
});

/** The demo marathon (17 Jan 2027) on an org with every module (Platform mail, WhatsApp). */
function reminderEvent(array $reminders = ['enabled' => true], ?array $modules = null): Event
{
    Artisan::call('events:sync', ['paths' => [base_path('events/demo/example-marathon.yaml')]]);
    $event = Event::withoutGlobalScopes()->where('slug', 'bengaluru-city-marathon-2027')->sole();
    SeasonPass::withoutGlobalScopes()->create([
        'org_id' => $event->org_id, 'plan' => 'Test', 'starts_on' => '2026-01-01', 'ends_on' => '2028-12-31',
        'status' => 'active', 'modules' => $modules ?? Module::cases(),
    ]);
    $event->forceFill(['payment_reminders' => $reminders])->save();

    return $event->fresh();
}

function unpaidRegistration(Event $event, array $attributes = []): Registration
{
    return Registration::factory()->for($event)->create([
        'org_id' => $event->org_id,
        'status' => PaymentStatus::Pending,
        'participant_name' => 'Ravi Kumar',
        'email' => 'ravi@example.com',
        'phone' => '98450 12345',
        'distance_code' => '10K',
        'amount_paise' => 89900,
        ...$attributes,
    ]);
}

/** Payment reminder emails only (paying also sends the pass confirmation). */
function reminderMails(): array
{
    return app('mailer')->getSymfonyTransport()->messages()->map->getOriginalMessage()
        ->filter(fn ($m) => str_starts_with((string) $m->getSubject(), 'Complete your payment'))
        ->values()->all();
}

function runReminders(): void
{
    Artisan::call('reminders:payments');
}

function reminderOwner(Event $event): User
{
    return User::factory()->create(['org_id' => $event->org_id, 'role' => Role::Owner, 'email_verified_at' => now()]);
}

it('is off by default for every event', function () {
    $event = reminderEvent(reminders: []);
    unpaidRegistration($event, ['created_at' => now()->subDay()]);

    runReminders();

    expect(reminderMails())->toBe([])->and(RegistrationReminderLog::withoutGlobalScopes()->count())->toBe(0);
});

it('emails unpaid registrations on the default cadence, capped at 3', function () {
    $event = reminderEvent();
    $registration = unpaidRegistration($event);

    $this->travel(59)->minutes();
    runReminders();
    expect(reminderMails())->toHaveCount(0); // not due yet

    $this->travel(2)->minutes(); // 1 hour after registering
    runReminders();
    $mails = reminderMails();
    expect($mails)->toHaveCount(1)
        ->and($mails[0]->getSubject())->toBe('Complete your payment — Bengaluru City Marathon 2027')
        ->and($mails[0]->getTo()[0]->getAddress())->toBe('ravi@example.com')
        ->and($mails[0]->getHtmlBody())
        ->toContain('Hi Ravi Kumar, thanks for starting your entry for Bengaluru City Marathon 2027')
        ->toContain('Pay now')
        ->toContain('/r/'.$registration->id.'/review?expires=')
        ->toContain('/r/'.$registration->id.'/reminders/stop?expires=')
        ->toContain('Sunday, 17 January 2027');

    runReminders(); // idempotent: nothing new
    expect(reminderMails())->toHaveCount(1);

    $this->travel(24)->hours();
    runReminders();
    $this->travel(72)->hours();
    runReminders();
    $this->travel(10)->days();
    runReminders();

    $logs = RegistrationReminderLog::withoutGlobalScopes()->orderBy('id')->get();
    expect(reminderMails())->toHaveCount(3)
        ->and($logs->pluck('sequence')->all())->toBe([1, 2, 3])
        ->and($logs->pluck('status')->unique()->all())->toBe(['sent'])
        ->and($logs->pluck('channel')->unique()->all())->toBe(['mail']); // no WhatsApp template set
});

it('stops as soon as the registration is paid', function () {
    $event = reminderEvent();
    $registration = unpaidRegistration($event);

    $this->travel(61)->minutes();
    runReminders();
    expect(reminderMails())->toHaveCount(1);

    // A reminder claimed but not yet sent when the payment webhook lands is cancelled.
    RegistrationReminderLog::withoutGlobalScopes()->create([
        'org_id' => $event->org_id, 'registration_id' => $registration->id, 'kind' => 'auto', 'sequence' => 2,
        'dispatch_key' => 'auto-2', 'channel' => 'mail', 'status' => 'queued',
    ]);
    $registration->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);
    event(new RegistrationPaid($registration->id));
    expect(RegistrationReminderLog::withoutGlobalScopes()->where('dispatch_key', 'auto-2')->sole())
        ->status->toBe('skipped')->detail->toBe('Cancelled: payment received.');

    // Even if that job still runs, it re-checks and sends nothing.
    (new SendPaymentReminder($registration->id, 'auto-2', 'auto', 2))->handle(app(WhatsAppGatewayFactory::class));
    $this->travel(5)->days();
    runReminders();
    expect(reminderMails())->toHaveCount(1);
});

it('includes failed payments, never refunded ones or opt-outs', function () {
    $event = reminderEvent();
    unpaidRegistration($event, ['status' => PaymentStatus::Failed, 'email' => 'failed@example.com']);
    unpaidRegistration($event, ['status' => PaymentStatus::Refunded, 'email' => 'refunded@example.com']);
    unpaidRegistration($event, ['email' => 'out@example.com', 'reminders_opted_out_at' => now()]);

    $this->travel(2)->hours();
    runReminders();

    expect(collect(reminderMails())->map(fn ($m) => $m->getTo()[0]->getAddress())->all())->toBe(['failed@example.com']);
});

it('lets participants opt out with the signed link', function () {
    $event = reminderEvent();
    $registration = unpaidRegistration($event);
    $this->travel(61)->minutes();
    runReminders();

    preg_match('#href="([^"]+/reminders/stop[^"]+)"#', reminderMails()[0]->getHtmlBody(), $m);
    $this->get(html_entity_decode($m[1]))->assertOk()->assertSee('No more payment reminders');
    $this->get('/r/'.$registration->id.'/reminders/stop')->assertForbidden(); // unsigned

    expect($registration->fresh()->reminders_opted_out_at)->not->toBeNull();
    $this->travel(2)->days();
    runReminders();
    expect(reminderMails())->toHaveCount(1);
});

it('waits for 8 am IST during quiet hours', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-02 20:30', 'Asia/Kolkata'));
    $event = reminderEvent();
    $registration = unpaidRegistration($event);

    expect(ReminderSchedule::next($registration->fresh())[1]->setTimezone('Asia/Kolkata')->format('Y-m-d H:i'))->toBe('2026-11-03 08:00');

    $this->travelTo(CarbonImmutable::parse('2026-11-02 22:00', 'Asia/Kolkata'));
    runReminders();
    expect(reminderMails())->toBe([]);

    $this->travelTo(CarbonImmutable::parse('2026-11-03 08:05', 'Asia/Kolkata'));
    runReminders();
    expect(reminderMails())->toHaveCount(1);
});

it('sends the 3rd reminder no later than 48 hours before the start, and skips it when the event is closer', function () {
    $event = reminderEvent();
    $event->update(['date' => CarbonImmutable::parse('2026-11-06 06:00', 'Asia/Kolkata')]);
    $registration = unpaidRegistration($event); // 2 Nov 10:00

    $this->travel(61)->minutes();   // #1 at 11:01
    runReminders();
    $this->travel(24)->hours();     // #2 at 3 Nov 11:01
    runReminders();
    // #3: 72h later would be 6 Nov, after the start; 48h before the start is 4 Nov 06:00.
    expect(ReminderSchedule::next($registration->fresh()->load('reminderLogs'))[1]->setTimezone('Asia/Kolkata')->format('Y-m-d H:i'))->toBe('2026-11-04 08:00'); // 06:00 is quiet

    $this->travelTo(CarbonImmutable::parse('2026-11-04 08:05', 'Asia/Kolkata'));
    runReminders();
    expect(reminderMails())->toHaveCount(3);

    // An event closer than the window: after #2 nothing more is scheduled.
    $close = unpaidRegistration($event, ['email' => 'late@example.com']);
    RegistrationReminderLog::withoutGlobalScopes()->insert([
        ['org_id' => $event->org_id, 'registration_id' => $close->id, 'kind' => 'auto', 'sequence' => 1, 'dispatch_key' => 'auto-1', 'channel' => 'mail', 'template' => 'payment_reminder', 'status' => 'sent', 'sent_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ['org_id' => $event->org_id, 'registration_id' => $close->id, 'kind' => 'auto', 'sequence' => 2, 'dispatch_key' => 'auto-2', 'channel' => 'mail', 'template' => 'payment_reminder', 'status' => 'sent', 'sent_at' => now(), 'created_at' => now(), 'updated_at' => now()],
    ]);
    expect(ReminderSchedule::next($close->fresh()))->toBeNull();
});

it('sends nothing once the event has started', function () {
    $event = reminderEvent();
    unpaidRegistration($event);
    $event->update(['date' => now()->addMinutes(30)]);

    $this->travel(2)->hours();
    runReminders();

    expect(reminderMails())->toBe([]);
});

it('adds WhatsApp only with the add-on, a payment template and a mobile number', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.PAY']]])]);
    $event = reminderEvent();
    NotificationSettings::for($event->org)->fill([
        'whatsapp_enabled' => true, 'whatsapp_phone_number_id' => '1098765432', 'whatsapp_token' => 'EAAG-secret',
        'whatsapp_template_confirmed' => 'registration_confirmed', 'whatsapp_template_payment' => 'payment_reminder',
    ])->save();
    $registration = unpaidRegistration($event);
    unpaidRegistration($event, ['email' => 'nophone@example.com', 'phone' => '']);

    $this->travel(61)->minutes();
    runReminders();

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['template']['name'] === 'payment_reminder'
        && $request['to'] === '919845012345'
        && $request['template']['components'][0]['parameters'][0]['text'] === 'Ravi Kumar'
        && str_contains($request['template']['components'][0]['parameters'][3]['text'], '/r/'.$registration->id.'/review'));

    $wa = RegistrationReminderLog::withoutGlobalScopes()->where('channel', 'whatsapp')->orderBy('id')->get();
    expect($wa->pluck('status')->all())->toBe(['sent', 'skipped'])
        ->and($wa[0]->provider_id)->toBe('wamid.PAY')
        ->and($wa[1]->detail)->toBe('No valid mobile number.')
        ->and(reminderMails())->toHaveCount(2);
});

it('skips WhatsApp silently without the add-on, and still emails', function () {
    Http::fake();
    $event = reminderEvent(modules: [Module::Registration, Module::Payments, Module::Passes, Module::EmailBranding]);
    NotificationSettings::for($event->org)->fill([
        'whatsapp_enabled' => true, 'whatsapp_phone_number_id' => '1098765432', 'whatsapp_token' => 'EAAG-secret',
        'whatsapp_template_confirmed' => 'registration_confirmed', 'whatsapp_template_payment' => 'payment_reminder',
    ])->save();
    unpaidRegistration($event);

    $this->travel(61)->minutes();
    runReminders();

    Http::assertNothingSent();
    expect(RegistrationReminderLog::withoutGlobalScopes()->where('channel', 'whatsapp')->count())->toBe(0)
        ->and(reminderMails())->toHaveCount(1);
});

it('logs the reminder as held when the org has neither its own SMTP nor Platform mail', function () {
    $event = reminderEvent(modules: [Module::Registration, Module::Payments, Module::Passes]);
    unpaidRegistration($event);

    $this->travel(61)->minutes();
    runReminders();

    expect(reminderMails())->toBe([])
        ->and(RegistrationReminderLog::withoutGlobalScopes()->sole())
        ->status->toBe('skipped')
        ->detail->toBe('Held: connect your SMTP or add Platform mail.');
});

it('respects the feature flag', function () {
    config(['features.payment_reminders' => false]);
    $event = reminderEvent();
    unpaidRegistration($event);

    $this->travel(2)->hours();
    runReminders();

    expect(reminderMails())->toBe([]);
});

it('sends a manual reminder now from the unpaid list, outside the cap, with a cooldown', function () {
    $event = reminderEvent(reminders: []); // automation off: manual still works
    $owner = reminderOwner($event);
    $unpaid = unpaidRegistration($event);
    $paid = unpaidRegistration($event, ['status' => PaymentStatus::Paid, 'email' => 'paid@example.com']);
    $otherOrg = unpaidRegistration(reminderEventForOtherOrg(), ['email' => 'other@example.com']);

    $this->actingAs($owner)->post(route('dashboard.participants.remind'), ['registrations' => [$unpaid->id, $paid->id, $otherOrg->id]])
        ->assertRedirect()
        ->assertSessionHas('reminders_queued', 1);

    $log = RegistrationReminderLog::withoutGlobalScopes()->sole();
    expect(collect(reminderMails())->map(fn ($m) => $m->getTo()[0]->getAddress())->all())->toBe(['ravi@example.com'])
        ->and($log->kind)->toBe('manual')
        ->and($log->sequence)->toBeNull()
        ->and($log->status)->toBe('sent');

    // A second click inside the cooldown is refused.
    $this->actingAs($owner)->post(route('dashboard.participants.remind'), ['registrations' => [$unpaid->id]])
        ->assertSessionHas('reminders_queued', 0);
    expect(reminderMails())->toHaveCount(1);

    // Manual reminders do not use up automatic ones, but the next automatic one waits out the cooldown.
    $event->forceFill(['payment_reminders' => ['enabled' => true]])->save();
    [$sequence, $due] = ReminderSchedule::next($unpaid->fresh());
    expect($sequence)->toBe(1)
        // manual at 10:00 + 12h cooldown = 22:00, in quiet hours, so 08:00 the next morning
        ->and($due->setTimezone('Asia/Kolkata')->format('Y-m-d H:i'))->toBe('2026-11-03 08:00');
});

it('queues manual reminders for 8 am during quiet hours', function () {
    Queue::fake();
    $this->travelTo(CarbonImmutable::parse('2026-11-02 23:00', 'Asia/Kolkata'));
    $event = reminderEvent();
    $unpaid = unpaidRegistration($event);

    $this->actingAs(reminderOwner($event))->post(route('dashboard.participants.remind'), ['registrations' => [$unpaid->id]])
        ->assertSessionHas('reminders_deferred', true);

    Queue::assertPushed(SendPaymentReminder::class, fn ($job) => $job->kind === 'manual'
        && CarbonImmutable::instance($job->delay)->setTimezone('Asia/Kolkata')->format('Y-m-d H:i') === '2026-11-03 08:00');
});

it('lets organisers switch reminders on, edit the cadence and copy, and preview them', function () {
    $event = reminderEvent(reminders: []);
    $owner = reminderOwner($event);
    unpaidRegistration($event);

    $this->actingAs($owner)->get(route('organiser.events.communications', $event))
        ->assertOk()
        ->assertSee('Payment reminders')
        ->assertSee('Complete your payment — Bengaluru City Marathon 2027')
        ->assertSee('Platform mail')
        ->assertSee('1 unpaid registration');

    $this->actingAs($owner)->put(route('organiser.events.communications.update', $event), [
        'enabled' => '1', 'max' => '4', 'delays_hours' => ['2', '12', '48', '48'], 'before_event_hours' => '24',
        'subject' => 'Your {event} place is waiting, {name}', 'message' => 'Finish your entry for {event}.',
    ])->assertRedirect(route('organiser.events.communications', $event));

    expect($event->fresh()->payment_reminders)->toMatchArray([
        'enabled' => true, 'max' => 4, 'delays_hours' => [2, 12, 48, 48], 'before_event_hours' => 24,
    ]);

    $this->actingAs($owner)->get(route('organiser.events.communications.preview', $event))
        ->assertOk()->assertSee('Finish your entry for Bengaluru City Marathon 2027.')->assertSee('Pay now');

    $this->actingAs($owner)->put(route('organiser.events.communications.update', $event), [
        'enabled' => '1', 'max' => '9', 'delays_hours' => ['0'], 'before_event_hours' => '24', 'subject' => '', 'message' => 'x',
    ])->assertSessionHasErrors(['max', 'delays_hours.0', 'subject']);

    $stranger = User::factory()->create();
    $this->actingAs($stranger)->get(route('organiser.events.communications', $event))->assertNotFound();
});

it('filters the participants list by paid and unpaid and shows reminder status', function () {
    $event = reminderEvent();
    $owner = reminderOwner($event);
    $unpaid = unpaidRegistration($event);
    unpaidRegistration($event, ['status' => PaymentStatus::Paid, 'participant_name' => 'Paid Person', 'email' => 'paid@example.com']);

    $this->travel(61)->minutes();
    runReminders();

    $this->actingAs($owner)->get(route('dashboard.participants', ['status' => 'unpaid']))
        ->assertOk()
        ->assertSee('Ravi Kumar')->assertDontSee('Paid Person')
        ->assertSee('Send reminder now')
        ->assertSee('name="registrations[]" value="'.$unpaid->id.'"', false)
        ->assertSee('Last: 2 Nov, 11:01 AM')
        ->assertSee('Next: 3 Nov, 11:01 AM')
        ->assertSee('✉️');

    $this->actingAs($owner)->get(route('dashboard.participants', ['status' => 'paid']))
        ->assertSee('Paid Person')->assertDontSee('Ravi Kumar');
});

function reminderEventForOtherOrg(): Event
{
    $org = Org::factory()->create();

    return Event::factory()->for($org)->create(['date' => now()->addMonth()]);
}
