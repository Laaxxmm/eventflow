<?php

namespace Tests\Feature\Reports;

use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\User;
use App\Payments\PaymentStatus;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds stream E fixtures from tests/Feature/Reports/fixtures/skating-open.yaml.
 */
final class ReportFixtures
{
    /** @return array<string, mixed> */
    public static function config(array $overrides = []): array
    {
        return array_replace(Yaml::parseFile(__DIR__.'/fixtures/skating-open.yaml'), $overrides);
    }

    public static function event(?Org $org = null, array $configOverrides = []): Event
    {
        $config = self::config($configOverrides);

        return Event::factory()->for($org ?? Org::factory()->create())->create([
            'name' => $config['name'],
            'sport' => $config['sport'],
            'date' => $config['date'],
            'venue' => $config['venue'],
            'distances' => $config['distances'],
            'config' => $config,
        ]);
    }

    public static function owner(Event $event): User
    {
        return User::factory()->for($event->org)->create();
    }

    /** Paid entrant; pass checkedIn: true to stamp attendance. */
    public static function entrant(Event $event, string $distance, int $age, string $gender, array $attributes = []): Registration
    {
        $checkedIn = (bool) ($attributes['checkedIn'] ?? false);
        unset($attributes['checkedIn']);

        return Registration::factory()->for($event)->create([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'distance_code' => $distance,
            'answers' => ['age' => $age, 'gender' => $gender],
            'checked_in_at' => $checkedIn ? now() : null,
            ...$attributes,
        ]);
    }
}
