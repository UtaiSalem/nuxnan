<?php

namespace Database\Factories;

use App\Models\CourseGroup;
use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseGroupFactory extends Factory
{
    protected $model = CourseGroup::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'status' => 1,
            'sort_order' => 0,
        ];
    }
}
