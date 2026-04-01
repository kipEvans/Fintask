<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FinanceService;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Single endpoint that powers the app home screen.
     *
     * GET /api/dashboard
     */
    public function index(): JsonResponse
    {
        $user    = auth('api')->user();
        $finance = new FinanceService($user);
        $tasks   = new TaskService($user);

        $monthly  = $finance->monthlySummary(now()->month, now()->year);
        $history  = $finance->historicalSummary(6);
        $topSpend = $finance->topSpendingCategories(4);
        $alert    = $finance->budgetAlertLevel();

        $taskStats  = $tasks->monthlyStats();
        $upcoming   = $tasks->upcoming(7);
        $priorities = $tasks->byPriority();

        return response()->json([
            'success' => true,
            'data'    => [
                'user' => [
                    'name'           => $user->name,
                    'currency'       => $user->currency,
                    'monthly_budget' => $user->monthly_budget,
                ],
                'finance' => [
                    'current_month'          => $monthly,
                    'budget_alert'           => $alert,
                    'top_spending_categories' => $topSpend,
                    'history_6_months'       => $history,
                ],
                'tasks' => [
                    'stats'      => $taskStats,
                    'priorities' => $priorities,
                    'upcoming'   => $upcoming,
                ],
            ],
        ]);
    }
}
