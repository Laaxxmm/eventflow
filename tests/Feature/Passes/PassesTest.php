<?php

use App\Enums\Module;
use App\Events\RegistrationPaid;
use App\Listeners\SendPass;
use App\Mail\PassMail;
use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\SeasonPass;
use App\Models\User;
use App\Passes\DomPdfPassGenerator;
use App\Passes\SignedPassQr;
use App\Payments\PaymentStatus;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function passOrg(): array
{
    $org = Org::create(['slug' => 'demo-runners', 'name' => 'Demo Runners', 'colours' => ['primary' => '#111a31', 'accent' => '#d3f36b']]);
    $event = Event::create([
        'org_id' => $org->id,
        'slug' => 'city-run',
        'name' => 'Bengaluru City Run',
        'sport' => 'running',
        'date' => now()->addMonth(),
        'venue' => 'Kanteerava Stadium',
        'colours' => ['secondary' => '#5148d9'],
    ]);

    // Check-in is an optional module: the org needs a Season Pass that includes it.
    SeasonPass::withoutGlobalScopes()->create([
        'org_id' => $org->id,
        'plan' => 'Test season',
        'starts_on' => now()->subDay()->toDateString(),
        'ends_on' => now()->addYear()->toDateString(),
        'status' => 'active',
        'modules' => Module::cases(),
    ]);

    return [$org, $event];
}

function paidRegistration(Event $event): Registration
{
    return Registration::create([
        'event_id' => $event->id,
        'pass_no' => Registration::newPassNo(),
        'status' => PaymentStatus::Paid,
        'participant_name' => 'Asha Rao',
        'email' => 'asha@example.com',
        'phone' => '9845012345',
        'distance_code' => '10K',
        'amount_paise' => 89900,
        'paid_at' => now(),
    ]);
}

beforeEach(function () {
    config(['eventflow.pass.signing_key' => str_repeat('ab', 32)]);
});

it('queues the pass listener and the pass email', function () {
    [, $event] = passOrg();
    $registration = paidRegistration($event);

    Queue::fake();
    event(new RegistrationPaid($registration->id));

    Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
        return $job->class === SendPass::class;
    });
});

it('renders a local pass and queues the mailable when RegistrationPaid fires', function () {
    Storage::fake('local');
    Mail::fake();

    [, $event] = passOrg();
    $registration = paidRegistration($event);

    event(new RegistrationPaid($registration->id));

    $registration->refresh();
    expect($registration->pass_no)->toMatch(config('eventflow.pass.pattern'))
        ->and($registration->pass_png_path)->not->toBeNull()
        ->and($registration->pass_pdf_path)->not->toBeNull();

    $png = Storage::disk('local')->get($registration->pass_png_path);
    $pdf = Storage::disk('local')->get($registration->pass_pdf_path);
    expect(substr($png, 0, 8))->toBe("\x89PNG\r\n\x1a\n")
        ->and(substr($pdf, 0, 5))->toBe('%PDF-');

    Mail::assertQueued(PassMail::class, function (PassMail $mail) use ($registration) {
        return $mail->registrationId === $registration->id
            && $mail->hasTo('asha@example.com');
    });
});

it('rejects a tampered QR and does not stamp attendance', function () {
    [$org, $event] = passOrg();
    $registration = paidRegistration($event);
    $volunteer = User::factory()->create(['role' => 'volunteer', 'org_id' => $org->id]);

    $payload = SignedPassQr::payload($registration->pass_no);
    $tampered = substr($payload, 0, -1).(str_ends_with($payload, 'a') ? 'b' : 'a');

    $this->actingAs($volunteer)
        ->postJson('/checkin', ['payload' => $tampered])
        ->assertStatus(422)
        ->assertJson(['ok' => false]);

    expect($registration->fresh()->checked_in_at)->toBeNull();
});

it('stamps attendance once and warns with the original time on a second scan', function () {
    [$org, $event] = passOrg();
    $registration = paidRegistration($event);
    $volunteer = User::factory()->create(['role' => 'volunteer', 'org_id' => $org->id]);
    $payload = SignedPassQr::payload($registration->pass_no);

    $first = $this->actingAs($volunteer)->postJson('/checkin', ['payload' => $payload]);
    $first->assertOk()->assertJson(['ok' => true, 'duplicate' => false, 'pass_no' => $registration->pass_no]);

    $stamped = $registration->fresh()->checked_in_at;
    expect($stamped)->not->toBeNull()
        ->and($registration->fresh()->checked_in_by)->toBe($volunteer->id);

    $this->travel(5)->minutes();

    $second = $this->actingAs($volunteer)->postJson('/checkin', ['payload' => $payload]);
    $second->assertOk()->assertJson(['ok' => true, 'duplicate' => true]);

    expect($registration->fresh()->checked_in_at->equalTo($stamped))->toBeTrue();
});

it('accepts a manual pass number when the volunteer cannot scan', function () {
    [$org, $event] = passOrg();
    $registration = paidRegistration($event);
    $volunteer = User::factory()->create(['role' => 'volunteer', 'org_id' => $org->id]);

    $this->actingAs($volunteer)
        ->post('/checkin', ['pass_no' => strtolower($registration->pass_no)])
        ->assertRedirect(route('checkin.index'));

    expect($registration->fresh()->checked_in_at)->not->toBeNull();
});

it('blocks check-in without a Season Pass that includes it, or when switched off', function () {
    [$org, $event] = passOrg();
    $volunteer = User::factory()->create(['role' => 'volunteer', 'org_id' => $org->id]);
    $registration = paidRegistration($event);

    $this->actingAs($volunteer)->get('/checkin')->assertOk();

    // Each request gets a fresh user, as in production (the org relation is cached per model).
    $org->update(['modules' => ['checkin' => false]]); // switched off in Org Settings
    $this->actingAs($volunteer->fresh())->get('/checkin')->assertForbidden();
    $this->actingAs($volunteer->fresh())->post('/checkin', ['pass_no' => $registration->pass_no])->assertForbidden();
    expect($registration->fresh()->checked_in_at)->toBeNull();

    $org->update(['modules' => null]);
    SeasonPass::withoutGlobalScopes()->where('org_id', $org->id)->update(['status' => 'expired']);
    $this->app->forgetScopedInstances();
    $this->actingAs($volunteer->fresh())->get('/checkin')->assertForbidden(); // no active pass
});

it('keeps pass text readable on light brand colours', function () {
    [$org, $event] = passOrg();
    $generator = app(DomPdfPassGenerator::class);

    // Default navy strip with lime text, purple org name on white: brand colours kept.
    $default = $generator->colours(paidRegistration($event));
    expect($default['onPrimary'])->toBe('#d3f36b')
        ->and($default['secondaryText'])->toBe('#5148d9');

    // The prototype's lime strip and coral org name: lime-on-lime and coral-on-white fall back to ink.
    $org->update(['colours' => ['primary' => '#d9ff45', 'secondary' => '#ff6e61', 'accent' => '#d3f36b']]);
    $event->update(['colours' => null]);
    $light = $generator->colours(paidRegistration($event->fresh())->load('event.org'));

    expect($light['primary'])->toBe('#d9ff45')                 // the strip keeps the brand colour
        ->and($light['onPrimary'])->toBe('#111a31')
        ->and($light['secondaryText'])->toBe('#111a31')
        ->and(DomPdfPassGenerator::contrast($light['onPrimary'], $light['primary']))->toBeGreaterThanOrEqual(4.5);
});
