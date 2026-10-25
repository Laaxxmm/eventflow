<?php

use App\Contracts\LandingSource;
use App\Contracts\PassGenerator;
use App\Contracts\PaymentGateway;
use App\Events\RegistrationPaid;
use App\Landing\EventConfig;
use App\Models\Event;
use App\Models\Org;
use App\Models\PaymentEvent;
use App\Models\Registration;
use App\Payments\Dto\PaymentOrder;
use App\Payments\PaymentStatus;
use App\Support\EventSchema;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Yaml\Yaml;

function demoConfig(): array
{
    return Yaml::parseFile(base_path('events/demo/example-marathon.yaml'));
}

function schemaErrors(array $data): array
{
    return EventSchema::errors($data);
}

it('validates the demo marathon against events/schema.json', function () {
    expect(schemaErrors(demoConfig()))->toBe([]);
});

it('rejects configs missing required keys or with bad colours', function () {
    $data = demoConfig();
    unset($data['venue']);
    $data['branding']['primary'] = 'red';

    expect(schemaErrors($data))->not->toBe([]);
});

it('maps the demo config onto EventConfig', function () {
    $config = EventConfig::fromArray(demoConfig());

    expect($config->org)->toBe('demo-runners')
        ->and($config->distances)->toHaveCount(4)
        ->and($config->distances[0]->pricePaise())->toBe(59900)
        ->and($config->ageBands[0]->min)->toBe(12)
        ->and($config->fields[1]->options)->toContain('Female')
        ->and($config->certificate?->template)->toBe('heritage-gold');
});

it('declares the contract interfaces', function () {
    expect(interface_exists(PaymentGateway::class))->toBeTrue()
        ->and(interface_exists(PassGenerator::class))->toBeTrue()
        ->and(interface_exists(LandingSource::class))->toBeTrue()
        ->and(PaymentStatus::cases())->toHaveCount(4)
        ->and((new ReflectionClass(PaymentOrder::class))->isReadOnly())->toBeTrue()
        ->and(new RegistrationPaid(1))->toBeInstanceOf(ShouldDispatchAfterCommit::class);
});

it('creates the schema tables with their final columns', function () {
    expect(Schema::hasColumns('orgs', ['slug', 'name', 'colours', 'payment_provider']))->toBeTrue()
        ->and(Schema::hasColumns('users', ['org_id', 'role']))->toBeTrue()
        ->and(Schema::hasColumns('events', ['org_id', 'slug', 'date', 'venue', 'price_paise', 'capacity', 'distances', 'min_age', 'max_age', 'colours', 'form_fields', 'config']))->toBeTrue()
        ->and(Schema::hasColumns('registrations', ['event_id', 'pass_no', 'status', 'amount_paise', 'gateway', 'gateway_order_id', 'answers', 'checked_in_at']))->toBeTrue()
        ->and(Schema::hasColumns('payment_events', ['provider', 'event_id', 'payload']))->toBeTrue();
});

it('persists a registration with an EF- pass number', function () {
    $org = Org::create(['slug' => 'demo-runners', 'name' => 'Demo Runners']);
    $event = Event::create(['org_id' => $org->id, 'slug' => 'bcm-2027', 'name' => 'BCM', 'date' => '2027-01-17 05:30', 'venue' => 'Bengaluru']);

    $registration = Registration::create([
        'event_id' => $event->id,
        'pass_no' => Registration::newPassNo(),
        'status' => PaymentStatus::Pending,
        'participant_name' => 'Asha',
        'email' => 'asha@example.com',
        'phone' => '9999999999',
        'amount_paise' => 59900,
    ]);

    expect($registration->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($registration->pass_no)->toMatch(config('eventflow.pass.pattern'));
});

it('enforces provider + event_id uniqueness on payment_events', function () {
    PaymentEvent::create(['provider' => 'razorpay', 'event_id' => 'evt_1', 'payload' => []]);
    PaymentEvent::create(['provider' => 'razorpay', 'event_id' => 'evt_1', 'payload' => []]);
})->throws(UniqueConstraintViolationException::class);
