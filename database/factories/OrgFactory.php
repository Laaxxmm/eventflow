<?php

namespace Database\Factories;

use App\Models\Org;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Org>
 */
class OrgFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Org::uniqueSlug($name),
            'phone' => fake()->numerify('98########'),
            'colours' => ['primary' => '#d9ff45', 'secondary' => '#ff6e61'],
        ];
    }
}
