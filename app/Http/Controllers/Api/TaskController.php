<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * List all tasks for the authenticated user.
     *
     * GET /api/tasks
     * Query params: status, priority, category, due_date, overdue, search, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::forUser(auth('api')->id())
            ->orderBy('due_date', 'asc')
            ->orderBy('priority', 'desc');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }

        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        if ($request->boolean('due_today')) {
            $query->dueToday();
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $perPage = min($request->get('per_page', 15), 100);
        $tasks = $query->paginate($perPage);

        // Summary counts
        $summary = [
            'total'       => Task::forUser(auth('api')->id())->count(),
            'pending'     => Task::forUser(auth('api')->id())->where('status', Task::STATUS_PENDING)->count(),
            'in_progress' => Task::forUser(auth('api')->id())->where('status', Task::STATUS_IN_PROGRESS)->count(),
            'completed'   => Task::forUser(auth('api')->id())->where('status', Task::STATUS_COMPLETED)->count(),
            'overdue'     => Task::forUser(auth('api')->id())->overdue()->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data'    => $tasks,
        ]);
    }

    /**
     * Create a new task.
     *
     * POST /api/tasks
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'status'      => 'nullable|in:' . implode(',', Task::STATUSES),
            'priority'    => 'nullable|in:' . implode(',', Task::PRIORITIES),
            'category'    => 'nullable|in:' . implode(',', Task::CATEGORIES),
            'due_date'    => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $task = Task::create([
            'user_id'     => auth('api')->id(),
            'title'       => $request->title,
            'description' => $request->description,
            'status'      => $request->status ?? Task::STATUS_PENDING,
            'priority'    => $request->priority ?? Task::PRIORITY_MEDIUM,
            'category'    => $request->category ?? 'general',
            'due_date'    => $request->due_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data'    => $task,
        ], 201);
    }

    /**
     * Get a single task.
     *
     * GET /api/tasks/{id}
     */
    public function show(int $id): JsonResponse
    {
        $task = Task::forUser(auth('api')->id())->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $task,
        ]);
    }

    /**
     * Update a task (including status change).
     *
     * PUT /api/tasks/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::forUser(auth('api')->id())->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|string|max:200',
            'description' => 'sometimes|nullable|string|max:1000',
            'status'      => 'sometimes|in:' . implode(',', Task::STATUSES),
            'priority'    => 'sometimes|in:' . implode(',', Task::PRIORITIES),
            'category'    => 'sometimes|in:' . implode(',', Task::CATEGORIES),
            'due_date'    => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['title', 'description', 'status', 'priority', 'category', 'due_date']);

        // Auto-set completed_at when marking as completed
        if (isset($data['status'])) {
            if ($data['status'] === Task::STATUS_COMPLETED && $task->status !== Task::STATUS_COMPLETED) {
                $data['completed_at'] = now();
            } elseif ($data['status'] !== Task::STATUS_COMPLETED) {
                $data['completed_at'] = null;
            }
        }

        $task->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data'    => $task->fresh(),
        ]);
    }

    /**
     * Delete a task (soft delete).
     *
     * DELETE /api/tasks/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::forUser(auth('api')->id())->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        if ($task->status !== Task::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Only completed tasks can be deleted',
            ], 403);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);
    }

    /**
     * Bulk update task statuses.
     *
     * POST /api/tasks/bulk-update
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
            'status' => 'required|in:' . implode(',', Task::STATUSES),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = ['status' => $request->status];
        if ($request->status === Task::STATUS_COMPLETED) {
            $data['completed_at'] = now();
        }

        $updated = Task::forUser(auth('api')->id())
            ->whereIn('id', $request->ids)
            ->update($data);

        return response()->json([
            'success' => true,
            'message' => "{$updated} task(s) updated",
            'count'   => $updated,
        ]);
    }
}