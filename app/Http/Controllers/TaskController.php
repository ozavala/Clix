<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => ['required', 'string', Rule::in(Task::$priorities)],
            'assigned_to_user_id' => 'nullable|exists:crm_users,user_id',
            'taskable_id' => 'required|integer',
            'taskable_type' => 'required|string',
        ]);

        $modelClass = 'App\\Models\\' . $validated['taskable_type'];

        if (!class_exists($modelClass)) {
            return back()->with('error', 'Invalid entity type provided.');
        }

        $taskable = $modelClass::findOrFail($validated['taskable_id']);

        $taskable->tasks()->create(array_merge($validated, [
            'created_by_user_id' => Auth::id(),
            'status' => 'Pending', // Default status
        ]));

        return back()->with('success', 'Task added successfully.');
    }

    public function update(Request $request, Task $task)
    {
        // A simple update, e.g., for changing status from a checkbox
        $task->update(['status' => $request->input('status', $task->status)]);
        return back()->with('success', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        $task->delete(); // Soft delete

        return back()->with('success', 'Task deleted successfully.');
    }
}