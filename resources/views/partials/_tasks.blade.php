@php
    // Fetch users once for the dropdown to avoid querying in a loop
    $crmUsers = \App\Models\CrmUser::orderBy('full_name')->get();
@endphp
<div class="card mt-4">
    <div class="card-header">
        <h2 class="h5 mb-0">{{ __('partials.Tasks') }}</h2>
    </div>
    <div class="card-body">
        {{-- Form to add a new task --}}
        <form action="{{ route('tasks.store') }}" method="POST" class="mb-4 border-bottom pb-4">
            @csrf
            <input type="hidden" name="taskable_id" value="{{ $model->getKey() }}">
            <input type="hidden" name="taskable_type" value="{{ class_basename($model) }}">

            <div class="mb-3">
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="{{ __('partials.New task title...') }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="assigned_to_user_id" class="form-label small">{{ __('partials.Assign To') }}</label>
                    <select name="assigned_to_user_id" class="form-select form-select-sm">
                        <option value="">{{ __('partials.Unassigned') }}</option>
                        @foreach($crmUsers as $user)
                            <option value="{{ $user->user_id }}">{{ $user->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="due_date" class="form-label small">{{ __('partials.Due Date') }}</label>
                    <input type="date" name="due_date" class="form-control form-control-sm">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="priority" class="form-label small">{{ __('partials.Priority') }}</label>
                    <select name="priority" class="form-select form-select-sm" required>
                        @foreach(\App\Models\Task::$priorities as $priority)
                            <option value="{{ $priority }}" {{ $priority === 'Normal' ? 'selected' : '' }}>{{ __('partials.priority.' . $priority) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">{{ __('partials.Add Task') }}</button>
        </form>

        {{-- List of existing tasks --}}
        @forelse($model->tasks()->latest()->with(['assignedTo', 'createdBy'])->get() as $task)
            <div class="d-flex align-items-start mb-3">
                <form action="{{ route('tasks.update', $task) }}" method="POST" class="me-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $task->status === 'Completed' ? 'Pending' : 'Completed' }}">
                    <input type="checkbox" class="form-check-input" onchange="this.form.submit()" {{ $task->status === 'Completed' ? 'checked' : '' }}>
                </form>
                <div class="flex-grow-1">
                    <span class="{{ $task->status === 'Completed' ? 'text-decoration-line-through text-muted' : '' }}">
                        {{ $task->title }}
                    </span>
                    <div class="small text-muted">
                        @if($task->assignedTo)
                            {{ __('partials.Assigned to:') }} {{ $task->assignedTo->full_name }}
                        @else
                            {{ __('partials.Unassigned') }}
                        @endif
                        @if($task->due_date)
                        - {{ __('partials.Due:') }} <span class="{{ $task->due_date->isPast() && $task->status !== 'Completed' ? 'text-danger' : '' }}">{{ $task->due_date->format('M d, Y') }}</span>
                        @endif
                    </div>
                </div>
                <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm(__('partials.Are you sure?'));" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm ">{{ __('partials.Delete') }}</button>
                </form>
            </div>
        @empty
            <p>{{ __('partials.No tasks yet.') }}</p>
        @endforelse
    </div>
</div>
