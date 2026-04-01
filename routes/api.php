<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FinTrack API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api (configured in RouteServiceProvider)
|
*/

// ─── Authentication (public) ─────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// ─── Protected routes ─────────────────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('me',           [AuthController::class, 'me']);
        Route::post('logout',      [AuthController::class, 'logout']);
        Route::post('refresh',     [AuthController::class, 'refresh']);
        Route::put('profile',      [AuthController::class, 'updateProfile']);
    });

    // ─── Tasks ────────────────────────────────────────────────────────────────
    // POST   /api/tasks            – Create task
    // GET    /api/tasks            – List tasks
    // GET    /api/tasks/{id}       – Get task
    // PUT    /api/tasks/{id}       – Update task / change status
    // DELETE /api/tasks/{id}       – Delete task
    // POST   /api/tasks/bulk-update – Bulk status update
    Route::post('tasks/bulk-update', [TaskController::class, 'bulkUpdate']);
    Route::apiResource('tasks', TaskController::class);

    // ─── Transactions ─────────────────────────────────────────────────────────
    // POST   /api/transactions                      – Add income/expense
    // GET    /api/transactions                      – List transactions
    // GET    /api/transactions/{id}                 – Get transaction
    // PUT    /api/transactions/{id}                 – Update transaction
    // DELETE /api/transactions/{id}                 – Delete transaction
    // GET    /api/transactions/summary/categories   – Category breakdown
    // GET    /api/transactions/summary/monthly      – Month-by-month summary
    Route::prefix('transactions')->group(function () {
        Route::get('summary/categories', [TransactionController::class, 'categoryBreakdown']);
        Route::get('summary/monthly',    [TransactionController::class, 'monthlySummary']);
    });
    Route::apiResource('transactions', TransactionController::class);

    // Dashboard (single home-screen endpoint)
    Route::get('dashboard', [DashboardController::class, 'index']);

    // ─── Reports ─────────────────────────────────────────────────────────────
    Route::prefix('reports')->group(function () {
        Route::get('daily',   [ReportController::class, 'daily']);
        Route::get('weekly',  [ReportController::class, 'weekly']);
        Route::get('monthly', [ReportController::class, 'monthly']);
    });

    // ─── Exports ─────────────────────────────────────────────────────────────
    // GET /api/export/transactions?month=1&year=2024   → CSV download
    // GET /api/export/tasks?status=completed           → CSV download
    // GET /api/export/report/monthly?month=1&year=2024 → JSON for PDF
    Route::prefix('export')->group(function () {
        Route::get('transactions',   [ExportController::class, 'transactionsCsv']);
        Route::get('tasks',          [ExportController::class, 'tasksCsv']);
        Route::get('report/monthly', [ExportController::class, 'monthlyReportJson']);
    });
});