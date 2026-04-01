<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * List transactions with filters.
     *
     * GET /api/transactions
     * Query params: type, category, month, year, start_date, end_date, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::forUser(auth('api')->id())
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        if (!$request->filled('start_date')) {
            $query->forMonth($month, $year);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $transactions = $query->paginate($perPage);

        // Monthly summary for the current filter
        $income   = Transaction::forUser(auth('api')->id())->income()->forMonth($month, $year)->sum('amount');
        $expenses = Transaction::forUser(auth('api')->id())->expenses()->forMonth($month, $year)->sum('amount');
        $budget   = auth('api')->user()->monthly_budget;

        return response()->json([
            'success' => true,
            'summary' => [
                'income'           => round($income, 2),
                'expenses'         => round($expenses, 2),
                'net'              => round($income - $expenses, 2),
                'monthly_budget'   => round($budget, 2),
                'remaining_budget' => round($budget - $expenses, 2),
                'budget_used_pct'  => $budget > 0 ? round(($expenses / $budget) * 100, 1) : 0,
                'currency'         => auth('api')->user()->currency,
                'period'           => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            ],
            'data' => $transactions,
        ]);
    }

    /**
     * Add a new transaction (income or expense).
     *
     * POST /api/transactions
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount'      => 'required|numeric|min:0.01',
            'type'        => 'required|in:income,expense',
            'category'    => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'date'        => 'nullable|date',
            'reference'   => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $transaction = Transaction::create([
            'user_id'     => auth('api')->id(),
            'amount'      => $request->amount,
            'type'        => $request->type,
            'category'    => $request->category,
            'description' => $request->description,
            'date'        => $request->date ?? today(),
            'reference'   => $request->reference,
        ]);

        // Budget warning
        $user      = auth('api')->user();
        $remaining = $user->remainingBudget();
        $warning   = null;
        if ($request->type === 'expense' && $remaining < 0) {
            $warning = "⚠️ You have exceeded your monthly budget by " . $user->currency . ' ' . number_format(abs($remaining), 2);
        } elseif ($request->type === 'expense' && $remaining < ($user->monthly_budget * 0.1)) {
            $warning = "⚠️ You have less than 10% of your budget remaining: " . $user->currency . ' ' . number_format($remaining, 2);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction recorded successfully',
            'data'    => $transaction,
            'warning' => $warning,
        ], 201);
    }

    /**
     * Show a single transaction.
     *
     * GET /api/transactions/{id}
     */
    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::forUser(auth('api')->id())->find($id);

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $transaction]);
    }

    /**
     * Update a transaction.
     *
     * PUT /api/transactions/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::forUser(auth('api')->id())->find($id);

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount'      => 'sometimes|numeric|min:0.01',
            'type'        => 'sometimes|in:income,expense',
            'category'    => 'sometimes|string|max:50',
            'description' => 'sometimes|nullable|string|max:500',
            'date'        => 'sometimes|date',
            'reference'   => 'sometimes|nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $transaction->update($request->only(['amount', 'type', 'category', 'description', 'date', 'reference']));

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated',
            'data'    => $transaction->fresh(),
        ]);
    }

    /**
     * Delete a transaction.
     *
     * DELETE /api/transactions/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $transaction = Transaction::forUser(auth('api')->id())->find($id);

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        $transaction->delete();

        return response()->json(['success' => true, 'message' => 'Transaction deleted']);
    }

    /**
     * Spending breakdown by category for a given month.
     *
     * GET /api/transactions/summary/categories
     */
    public function categoryBreakdown(Request $request): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $breakdown = Transaction::forUser(auth('api')->id())
            ->expenses()
            ->forMonth($month, $year)
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $totalExpenses = $breakdown->sum('total');

        $breakdown = $breakdown->map(function ($item) use ($totalExpenses) {
            $item->percentage = $totalExpenses > 0
                ? round(($item->total / $totalExpenses) * 100, 1)
                : 0;
            return $item;
        });

        return response()->json([
            'success' => true,
            'data'    => $breakdown,
            'total'   => round($totalExpenses, 2),
            'period'  => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
        ]);
    }

    /**
     * Month-by-month financial summary for the past N months.
     *
     * GET /api/transactions/summary/monthly?months=6
     */
    public function monthlySummary(Request $request): JsonResponse
    {
        $months = min($request->get('months', 6), 24);
        $result = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date    = now()->subMonths($i);
            $m       = $date->month;
            $y       = $date->year;
            $income  = Transaction::forUser(auth('api')->id())->income()->forMonth($m, $y)->sum('amount');
            $expense = Transaction::forUser(auth('api')->id())->expenses()->forMonth($m, $y)->sum('amount');

            $result[] = [
                'month'    => $date->format('M Y'),
                'income'   => round($income, 2),
                'expenses' => round($expense, 2),
                'net'      => round($income - $expense, 2),
                'savings'  => round(max(0, $income - $expense), 2),
            ];
        }

        return response()->json(['success' => true, 'data' => $result]);
    }
}