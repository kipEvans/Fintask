<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo user
        $user = User::create([
            'name'           => 'Jane Wanjiku',
            'email'          => 'jane@example.com',
            'password'       => Hash::make('password123'),
            'monthly_budget' => 50000,
            'currency'       => 'KES',
        ]);

        // ── Tasks ─────────────────────────────────────────────────────────────
        $tasks = [
            ['title' => 'Pay rent', 'category' => 'payment', 'priority' => 'high', 'status' => 'completed', 'due_date' => now()->subDays(2), 'completed_at' => now()->subDays(2)],
            ['title' => 'Transfer KES 5,000 to savings', 'category' => 'savings', 'priority' => 'high', 'status' => 'completed', 'due_date' => today(), 'completed_at' => now()],
            ['title' => 'Pay KPLC electricity bill', 'category' => 'bill', 'priority' => 'high', 'status' => 'pending', 'due_date' => today()],
            ['title' => 'Review investment portfolio', 'category' => 'investment', 'priority' => 'medium', 'status' => 'in_progress', 'due_date' => now()->addDays(3)],
            ['title' => 'File KRA tax returns', 'category' => 'payment', 'priority' => 'high', 'status' => 'pending', 'due_date' => now()->addDays(7)],
            ['title' => 'Pay NHIF & NSSF contributions', 'category' => 'bill', 'priority' => 'medium', 'status' => 'pending', 'due_date' => now()->addDays(5)],
            ['title' => 'Buy Safaricom shares', 'category' => 'investment', 'priority' => 'low', 'status' => 'pending', 'due_date' => now()->addDays(14)],
            ['title' => 'Renew car insurance', 'category' => 'bill', 'priority' => 'medium', 'status' => 'pending', 'due_date' => now()->addDays(10)],
        ];

        foreach ($tasks as $task) {
            Task::create(array_merge($task, ['user_id' => $user->id]));
        }

        // ── Transactions ──────────────────────────────────────────────────────
        $transactions = [
            // Income
            ['amount' => 75000, 'type' => 'income', 'category' => 'salary',     'description' => 'Monthly salary',          'date' => now()->startOfMonth()],
            ['amount' => 8000,  'type' => 'income', 'category' => 'freelance',  'description' => 'Web design project',       'date' => now()->subDays(5)],
            ['amount' => 2500,  'type' => 'income', 'category' => 'investment', 'description' => 'M-Akiba interest',         'date' => now()->subDays(3)],

            // Expenses
            ['amount' => 18000, 'type' => 'expense', 'category' => 'rent',         'description' => 'House rent - Westlands',  'date' => now()->subDays(2)],
            ['amount' => 3200,  'type' => 'expense', 'category' => 'food',          'description' => 'Carrefour groceries',     'date' => now()->subDays(1)],
            ['amount' => 1500,  'type' => 'expense', 'category' => 'transport',     'description' => 'Uber rides this week',    'date' => now()->subDays(1)],
            ['amount' => 2800,  'type' => 'expense', 'category' => 'bills',         'description' => 'Electricity bill KPLC',  'date' => now()->subDays(3)],
            ['amount' => 1200,  'type' => 'expense', 'category' => 'food',          'description' => 'Java House lunch',        'date' => today()],
            ['amount' => 500,   'type' => 'expense', 'category' => 'transport',     'description' => 'Matatu fare',             'date' => today()],
            ['amount' => 5000,  'type' => 'expense', 'category' => 'savings',       'description' => 'Savings account deposit', 'date' => today()],
            ['amount' => 3500,  'type' => 'expense', 'category' => 'entertainment', 'description' => 'Netflix + Showmax',       'date' => now()->subDays(4)],
            ['amount' => 1800,  'type' => 'expense', 'category' => 'health',        'description' => 'Pharmacy - vitamins',     'date' => now()->subDays(6)],
        ];

        foreach ($transactions as $txn) {
            Transaction::create(array_merge($txn, ['user_id' => $user->id]));
        }

        $this->command->info('✅ Demo user created: jane@example.com / password123');
        $this->command->info('✅ ' . count($tasks) . ' tasks seeded');
        $this->command->info('✅ ' . count($transactions) . ' transactions seeded');
    }
}
