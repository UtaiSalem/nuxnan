<?php

namespace Database\Factories;

use App\Models\Assignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => 1,
            'points' => 10,
            'passing_score' => 5,
        ];
    }
}
