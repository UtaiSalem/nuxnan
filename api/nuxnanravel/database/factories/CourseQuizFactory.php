<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseQuiz>
 */
class CourseQuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'time_limit' => 60,
            'is_active' => true,
            'passing_score' => 60,
        ];
    }
}
