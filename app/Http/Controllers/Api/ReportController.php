<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Daily report.
     *
     * GET /api/reports/daily
     * GET /api/reports/daily?date=2024-01-15
     */
    public function daily(Request $request): JsonResponse
    {
        $date   = $request->get('date', today()->toDateString());
        $user   = auth('api')->user();
        $userId = $user->id;

        // Tasks
        $completedTasks = Task::forUser($userId)
            ->where('status', Task::STATUS_COMPLETED)
            ->whereDate('completed_at', $date)
            ->get(['id', 'title', 'category', 'priority', 'completed_at']);

        $pendingTasks = Task::forUser($userId)
            ->where('status', Task::STATUS_PENDING)
            ->whereDate('due_date', $date)
            ->get(['id', 'title', 'category', 'priority', 'due_date']);

        $overdueTasks = Task::forUser($userId)
            ->overdue()
            ->get(['id', 'title', 'category', 'priority', 'due_date']);

        // Transactions for the day
        $todayTransactions = Transaction::forUser($userId)
            ->whereDate('date', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        $incomeToday   = $todayTransactions->where('type', 'income')->sum('amount');
        $expensesToday = $todayTransactions->where('type', 'expense')->sum('amount');

        // Monthly context
        $monthlyExpenses = Transaction::forUser($userId)
            ->expenses()
            ->thisMonth()
            ->sum('amount');

        $monthlyIncome = Transaction::forUser($userId)
            ->income()
            ->thisMonth()
            ->sum('amount');

        $remainingBudget = $user->monthly_budget - $monthlyExpenses;
        $budgetUsedPct   = $user->monthly_budget > 0
            ? round(($monthlyExpenses / $user->monthly_budget) * 100, 1)
            : 0;

        // Expense breakdown by category (today)
        $expensesByCategory = $todayTransactions
            ->where('type', 'expense')
            ->groupBy('category')
            ->map(fn($items) => round($items->sum('amount'), 2));

        return response()->json([
            'success' => true,
            'data'    => [
                'date'     => $date,
                'currency' => $user->currency,

                'tasks' => [
                    'completed_today' => [
                        'count' => $completedTasks->count(),
                        'items' => $completedTasks,
                    ],
                    'pending_today' => [
                        'count' => $pendingTasks->count(),
                        'items' => $pendingTasks,
                    ],
                    'overdue' => [
                        'count' => $overdueTasks->count(),
                        'items' => $overdueTasks,
                    ],
                ],

                'finances' => [
                    'today' => [
                        'income'               => round($incomeToday, 2),
                        'expenses'             => round($expensesToday, 2),
                        'net'                  => round($incomeToday - $expensesToday, 2),
                        'transactions_count'   => $todayTransactions->count(),
                        'expenses_by_category' => $expensesByCategory,
                    ],
                    'this_month' => [
                        'income'           => round($monthlyIncome, 2),
                        'expenses'         => round($monthlyExpenses, 2),
                        'net'              => round($monthlyIncome - $monthlyExpenses, 2),
                        'budget'           => round($user->monthly_budget, 2),
                        'remaining_budget' => round($remainingBudget, 2),
                        'budget_used_pct'  => $budgetUsedPct,
                        'on_track'         => $remainingBudget >= 0,
                    ],
                ],

                'summary_text' => $this->buildSummaryText(
                    $completedTasks->count(),
                    $pendingTasks->count(),
                    $overdueTasks->count(),
                    $expensesToday,
                    $remainingBudget,
                    $user->currency
                ),
            ],
        ]);
    }

    /**
     * Weekly report.
     *
     * GET /api/reports/weekly
     */
    public function weekly(Request $request): JsonResponse
    {
        $userId    = auth('api')->id();
        $startDate = now()->startOfWeek()->toDateString();
        $endDate   = now()->endOfWeek()->toDateString();

        $tasksCompleted = Task::forUser($userId)
            ->where('status', Task::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->count();

        $totalTasks = Task::forUser($userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $weeklyExpenses = Transaction::forUser($userId)
            ->expenses()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $weeklyIncome = Transaction::forUser($userId)
            ->income()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Daily breakdown for the week
        $dailyData = [];
        $current   = now()->startOfWeek();

        for ($i = 0; $i < 7; $i++) {
            $day      = $current->copy()->addDays($i)->toDateString();
            $dayLabel = $current->copy()->addDays($i)->format('D, d M');

            $dailyData[] = [
                'date'      => $day,
                'label'     => $dayLabel,
                'income'    => round(Transaction::forUser($userId)->income()->whereDate('date', $day)->sum('amount'), 2),
                'expenses'  => round(Transaction::forUser($userId)->expenses()->whereDate('date', $day)->sum('amount'), 2),
                'tasks_done' => Task::forUser($userId)->where('status', 'completed')->whereDate('completed_at', $day)->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'period'       => "Week of {$startDate} to {$endDate}",
                'tasks'        => [
                    'completed' => $tasksCompleted,
                    'total'     => $totalTasks,
                    'completion_rate' => $totalTasks > 0 ? round(($tasksCompleted / $totalTasks) * 100, 1) : 0,
                ],
                'finances'     => [
                    'income'   => round($weeklyIncome, 2),
                    'expenses' => round($weeklyExpenses, 2),
                    'net'      => round($weeklyIncome - $weeklyExpenses, 2),
                    'currency' => auth('api')->user()->currency,
                ],
                'daily_breakdown' => $dailyData,
            ],
        ]);
    }

    /**
     * Monthly report with full breakdown.
     *
     * GET /api/reports/monthly?month=1&year=2024
     */
    public function monthly(Request $request): JsonResponse
    {
        $month  = $request->get('month', now()->month);
        $year   = $request->get('year', now()->year);
        $userId = auth('api')->id();
        $user   = auth('api')->user();

        $income   = Transaction::forUser($userId)->income()->forMonth($month, $year)->sum('amount');
        $expenses = Transaction::forUser($userId)->expenses()->forMonth($month, $year)->sum('amount');

        $categoryBreakdown = Transaction::forUser($userId)
            ->expenses()
            ->forMonth($month, $year)
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) use ($expenses) {
                $item->percentage = $expenses > 0 ? round(($item->total / $expenses) * 100, 1) : 0;
                return $item;
            });

        $tasksCompleted = Task::forUser($userId)
            ->where('status', Task::STATUS_COMPLETED)
            ->whereMonth('completed_at', $month)
            ->whereYear('completed_at', $year)
            ->count();

        $tasksCreated = Task::forUser($userId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'period'   => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
                'currency' => $user->currency,
                'finances' => [
                    'income'            => round($income, 2),
                    'expenses'          => round($expenses, 2),
                    'net'               => round($income - $expenses, 2),
                    'savings_rate'      => $income > 0 ? round((($income - $expenses) / $income) * 100, 1) : 0,
                    'budget'            => round($user->monthly_budget, 2),
                    'remaining_budget'  => round($user->monthly_budget - $expenses, 2),
                    'budget_used_pct'   => $user->monthly_budget > 0 ? round(($expenses / $user->monthly_budget) * 100, 1) : 0,
                    'category_breakdown' => $categoryBreakdown,
                ],
                'tasks' => [
                    'completed'       => $tasksCompleted,
                    'created'         => $tasksCreated,
                    'completion_rate' => $tasksCreated > 0 ? round(($tasksCompleted / $tasksCreated) * 100, 1) : 0,
                ],
            ],
        ]);
    }

    /**
     * Build a human-readable daily summary text.
     */
    private function buildSummaryText(
        int    $completedCount,
        int    $pendingCount,
        int    $overdueCount,
        float  $expenses,
        float  $remainingBudget,
        string $currency
    ): string {
        $lines = [
            "📊 Daily Report — " . today()->format('D, d M Y'),
            "─────────────────────────────",
            "✅ Tasks completed today: {$completedCount}",
            "📋 Tasks pending today:   {$pendingCount}",
        ];

        if ($overdueCount > 0) {
            $lines[] = "⚠️  Overdue tasks:         {$overdueCount}";
        }

        $lines[] = "─────────────────────────────";
        $lines[] = "💸 Expenses today:        {$currency} " . number_format($expenses, 2);
        $lines[] = "💰 Remaining budget:      {$currency} " . number_format($remainingBudget, 2);

        if ($remainingBudget < 0) {
            $lines[] = "🔴 Budget exceeded by:    {$currency} " . number_format(abs($remainingBudget), 2);
        }

        return implode("\n", $lines);
    }
}