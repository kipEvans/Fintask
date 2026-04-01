<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([Transaction::TYPE_INCOME, Transaction::TYPE_EXPENSE]);

        return [
            'user_id'     => User::factory(),
            'type'        => $type,
            'amount'      => $this->faker->randomFloat(2, 10, 5000),
            'category'    => $type === Transaction::TYPE_INCOME
                ? $this->faker->randomElement(Transaction::INCOME_CATEGORIES)
                : $this->faker->randomElement(Transaction::EXPENSE_CATEGORIES),
            'description' => $this->faker->sentence(),
            'date'        => $this->faker->date(),
            'reference'   => $this->faker->uuid(),
        ];
    }
}
