<?php

namespace Database\Factories;

use App\Enums\Sport;
use App\Models\Event;
use App\Models\Org;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'org_id' => Org::factory(),
            'name' => fake()->city().' '.fake()->randomElement(['10K', 'Half Marathon', 'Skating Open']),
            'sport' => Sport::Running,
            'date' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'venue' => fake()->city(),
            'price_paise' => 59900,
            'capacity' => 500,
            'distances' => [['code' => '10K', 'label' => '10K', 'price_inr' => 599, 'capacity' => 500]],
            'status' => 'published',
        ];
    }
}
