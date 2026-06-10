<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donate>
 */
class DonateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'donor_id' => null,
            'donor_name' => $this->faker->name,
            'amounts' => $this->faker->randomFloat(2, 100, 5000),
            'slip' => 'sample_slip.jpg',
            'transfer_date' => now(),
            'transfer_time' => now()->format('H:i'),
            'donor_email' => $this->faker->safeEmail,
            'status' => 0,
            'privacy_settings' => 3,
            'notes' => $this->faker->sentence,
        ];
    }
}
