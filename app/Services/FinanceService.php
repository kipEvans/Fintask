<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

class FinanceService
{
    public function __construct(private readonly User $user) {}

    /**
     * Full monthly financial summary.
     */
    public function monthlySummary(int $month, int $year): array
    {
        $income   = $this->sumFor('income', $month, $year);
        $expenses = $this->sumFor('expense', $month, $year);
        $budget   = (float) $this->user->monthly_budget;
        $net      = $income - $expenses;

        return [
            'period'            => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'currency'          => $this->user->currency,
            'income'            => round($income, 2),
            'expenses'          => round($expenses, 2),
            'net'               => round($net, 2),
            'savings'           => round(max(0, $net), 2),
            'savings_rate'      => $income > 0 ? round(($net / $income) * 100, 1) : 0,
            'budget'            => round($budget, 2),
            'remaining_budget'  => round($budget - $expenses, 2),
            'budget_used_pct'   => $budget > 0 ? round(($expenses / $budget) * 100, 1) : 0,
            'over_budget'       => $expenses > $budget,
        ];
    }

    /**
     * Spending by category for a given month.
     */
    public function categoryBreakdown(int $month, int $year): Collection
    {
        $total = $this->sumFor('expense', $month, $year);

        return Transaction::forUser($this->user->id)
            ->expenses()
            ->forMonth($month, $year)
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($total) {
                $row->percentage = $total > 0 ? round(($row->total / $total) * 100, 1) : 0;
                return $row;
            });
    }

    /**
     * Historical data for the past N months (for charts).
     */
    public function historicalSummary(int $months = 6): array
    {
        $result = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $m    = $date->month;
            $y    = $date->year;

            $income   = $this->sumFor('income', $m, $y);
            $expenses = $this->sumFor('expense', $m, $y);

            $result[] = [
                'month'       => $date->format('M Y'),
                'month_short' => $date->format('M'),
                'income'      => round($income, 2),
                'expenses'    => round($expenses, 2),
                'net'         => round($income - $expenses, 2),
                'savings'     => round(max(0, $income - $expenses), 2),
            ];
        }

        return $result;
    }

    /**
     * Budget alert level: 'safe' | 'warning' | 'danger' | 'exceeded'
     */
    public function budgetAlertLevel(): string
    {
        $budget   = (float) $this->user->monthly_budget;
        $expenses = $this->sumFor('expense', now()->month, now()->year);

        if ($budget <= 0) {
            return 'none';
        }

        $pct = ($expenses / $budget) * 100;

        return match (true) {
            $pct >= 100 => 'exceeded',
            $pct >= 85  => 'danger',
            $pct >= 70  => 'warning',
            default     => 'safe',
        };
    }

    /**
     * Top N spending categories this month.
     */
    public function topSpendingCategories(int $limit = 3): Collection
    {
        return Transaction::forUser($this->user->id)
            ->expenses()
            ->thisMonth()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    private function sumFor(string $type, int $month, int $year): float
    {
        return (float) Transaction::forUser($this->user->id)
            ->where('type', $type)
            ->forMonth($month, $year)
            ->sum('amount');
    }
}
