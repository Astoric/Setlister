<?php

namespace Database\Factories;

use App\Models\Gig;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gig>
 */
class GigFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'artist_band_name' => $this->faker->company,
            'venue' => $this->faker->city,
            'gig_date_time' => $this->faker->dateTimeBetween('now', '+1 year'),
            'user_id' => User::factory(),
            'support_acts' => [],
            'people_attending' => [],
        ];
    }
}
