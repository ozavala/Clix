<div class="card mt-4">
    <div class="card-header">
        <h2 class="h5 mb-0">{{ __('notes.Notes & Activity') }}</h2>
    </div>
    <div class="card-body">
        {{-- Form to add a new note --}}
        <form action="{{ route('notes.store') }}" method="POST" class="mb-4">
            @csrf
            <input type="hidden" name="noteable_id" value="{{ $model->getKey() }}">
            <input type="hidden" name="noteable_type" value="{{ class_basename($model) }}">
            <div class="mb-3">
                <textarea name="body" class="form-control @error('body') is-invalid @enderror" rows="3" placeholder="{{ __('notes.Add a note...') }}" required></textarea>
                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('notes.Add Note') }}</button>
        </form>

        {{-- List of existing notes --}}
        @forelse($model->notes()->latest()->with('createdBy')->get() as $note)
            <div class="d-flex mb-3 border-bottom pb-3">
                <div class="flex-shrink-0 me-3">
                    {{-- Placeholder for user avatar --}}
                    <div class="avatar bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        {{ substr($note->createdBy->full_name ?? 'U', 0, 1) }}
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $note->createdBy->full_name ?? __('partials.Unknown User') }}</strong>
                        <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                    </div>
                    <p class="mb-1" style="white-space: pre-wrap;">{{ $note->body }}</p>
                    <form action="{{ route('notes.destroy', $note) }}" method="POST" onsubmit="return confirm(__('partials.Are you sure you want to delete this note?'));" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm ">{{ __('partials.Delete') }}</button>
                    </form>
                    <a href="{{ route('notes.show', $note) }}" class="btn btn-primary btn-sm">{{ __('notes.View') }}</a>
                </div>
            </div>
        @empty
            <p>{{ __('notes.No notes yet.') }}</p>
        @endforelse
    </div>
</div>