<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Transaction;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    /**
     * Export transactions to CSV.
     *
     * GET /api/export/transactions?month=1&year=2024&type=expense
     */
    public function transactionsCsv(Request $request): Response
    {
        $month  = $request->get('month', now()->month);
        $year   = $request->get('year', now()->year);
        $user   = auth('api')->user();

        $query = Transaction::forUser($user->id)
            ->forMonth($month, $year)
            ->orderBy('date', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->get();
        $period       = date('M-Y', mktime(0, 0, 0, $month, 1, $year));

        $csv  = "Date,Type,Category,Description,Amount ({$user->currency}),Reference\n";

        foreach ($transactions as $txn) {
            $csv .= implode(',', [
                $txn->date->format('Y-m-d'),
                $txn->type,
                $txn->category,
                '"' . str_replace('"', '""', $txn->description ?? '') . '"',
                number_format($txn->amount, 2, '.', ''),
                $txn->reference ?? '',
            ]) . "\n";
        }

        // Add summary footer
        $income   = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');
        $net      = $income - $expenses;

        $csv .= "\n";
        $csv .= "Total Income,,,,{$income},\n";
        $csv .= "Total Expenses,,,,{$expenses},\n";
        $csv .= "Net,,,,{$net},\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"fintrack-transactions-{$period}.csv\"",
        ]);
    }

    /**
     * Export tasks to CSV.
     *
     * GET /api/export/tasks?status=completed
     */
    public function tasksCsv(Request $request): Response
    {
        $query = Task::forUser(auth('api')->id())->orderBy('due_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query->get();

        $csv  = "ID,Title,Category,Priority,Status,Due Date,Completed At,Description\n";

        foreach ($tasks as $task) {
            $csv .= implode(',', [
                $task->id,
                '"' . str_replace('"', '""', $task->title) . '"',
                $task->category,
                $task->priority,
                $task->status,
                $task->due_date?->format('Y-m-d') ?? '',
                $task->completed_at?->format('Y-m-d H:i') ?? '',
                '"' . str_replace('"', '""', $task->description ?? '') . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="fintrack-tasks-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Export full monthly report as JSON (can be consumed by PDF libraries on the frontend).
     *
     * GET /api/export/report/monthly?month=1&year=2024
     */
    public function monthlyReportJson(Request $request): \Illuminate\Http\JsonResponse
    {
        $month  = $request->get('month', now()->month);
        $year   = $request->get('year', now()->year);
        $user   = auth('api')->user();

        $finance = new FinanceService($user);

        $transactions = Transaction::forUser($user->id)
            ->forMonth($month, $year)
            ->orderBy('date')
            ->get(['date', 'type', 'category', 'description', 'amount', 'reference']);

        $tasks = Task::forUser($user->id)
            ->where(function ($q) use ($month, $year) {
                $q->whereMonth('due_date', $month)->whereYear('due_date', $year);
            })
            ->orWhere(function ($q) use ($month, $year) {
                $q->whereMonth('completed_at', $month)->whereYear('completed_at', $year);
            })
            ->orderBy('due_date')
            ->get(['title', 'category', 'priority', 'status', 'due_date', 'completed_at']);

        return response()->json([
            'success'      => true,
            'generated_at' => now()->toDateTimeString(),
            'user'         => ['name' => $user->name, 'currency' => $user->currency],
            'summary'      => $finance->monthlySummary($month, $year),
            'categories'   => $finance->categoryBreakdown($month, $year),
            'transactions' => $transactions,
            'tasks'        => $tasks,
        ]);
    }
}
