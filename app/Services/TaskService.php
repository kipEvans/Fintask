<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class TaskService
{
    public function __construct(private readonly User $user) {}

    /**
     * Task completion stats for the current month.
     */
    public function monthlyStats(): array
    {
        $userId = $this->user->id;

        $total     = Task::forUser($userId)->whereMonth('created_at', now()->month)->count();
        $completed = Task::forUser($userId)->where('status', Task::STATUS_COMPLETED)->whereMonth('completed_at', now()->month)->count();
        $pending   = Task::forUser($userId)->where('status', Task::STATUS_PENDING)->count();
        $overdue   = Task::forUser($userId)->overdue()->count();
        $dueToday  = Task::forUser($userId)->dueToday()->count();

        return [
            'total'           => $total,
            'completed'       => $completed,
            'pending'         => $pending,
            'overdue'         => $overdue,
            'due_today'       => $dueToday,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Tasks grouped by priority with counts.
     */
    public function byPriority(): array
    {
        $userId = $this->user->id;

        return [
            'high'   => Task::forUser($userId)->where('priority', 'high')->where('status', '!=', 'completed')->count(),
            'medium' => Task::forUser($userId)->where('priority', 'medium')->where('status', '!=', 'completed')->count(),
            'low'    => Task::forUser($userId)->where('priority', 'low')->where('status', '!=', 'completed')->count(),
        ];
    }

    /**
     * Mark a task complete and return updated model.
     */
    public function complete(Task $task): Task
    {
        $task->update([
            'status'       => Task::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return $task->fresh();
    }

    /**
     * Upcoming tasks (next N days), sorted by due date then priority.
     */
    public function upcoming(int $days = 7): Collection
    {
        return Task::forUser($this->user->id)
            ->whereNotIn('status', [Task::STATUS_COMPLETED])
            ->whereBetween('due_date', [today(), today()->addDays($days)])
            ->orderBy('due_date')
            ->orderByRaw("CASE WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 WHEN priority = 'low' THEN 3 ELSE 4 END")
            ->get();
    }
}
