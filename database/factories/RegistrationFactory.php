<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Registration;
use App\Payments\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'status' => PaymentStatus::Pending,
            'participant_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('9#########'),
            'distance_code' => '10K',
            'amount_paise' => 59900,
            'answers' => [],
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'gateway' => 'cashfree',
            'gateway_order_id' => 'order_'.fake()->unique()->bothify('??########'),
            'paid_at' => now(),
        ]);
    }

    public function checkedIn(): static
    {
        return $this->paid()->state(fn (array $attributes) => ['checked_in_at' => now()]);
    }
}
