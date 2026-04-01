<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(Task::STATUSES);

        return [
            'user_id'      => User::factory(),
            'title'        => $this->faker->sentence(3),
            'description'  => $this->faker->paragraph(),
            'status'       => $status,
            'priority'     => $this->faker->randomElement(Task::PRIORITIES),
            'category'     => $this->faker->randomElement(Task::CATEGORIES),
            'due_date'     => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'completed_at' => $status === Task::STATUS_COMPLETED ? now() : null,
        ];
    }
}
