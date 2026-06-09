<?php

namespace Database\Factories;

use App\Models\Topic;
use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'lesson_id' => Lesson::factory(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraph(),
            'min_read' => $this->faker->numberBetween(1, 10),
            'status' => 'active',
            'sort_order' => 0,
        ];
    }
}
