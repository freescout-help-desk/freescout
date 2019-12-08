<div class="thread-editor-container @if ($thread->type == \App\Thread::TYPE_NOTE) conv-note-block @endif">
    <textarea class="form-control thread-editor" rows="8">{{ $thread->body }}</textarea>

    <div class="thread-editor-statusbar">
        <a href="#" class="btn btn-link link-grey" onclick="cancelThreadEdit(this);return false;">{{ __('Cancel') }}</a> 
        <button type="submit" class="btn btn-primary" data-loading-text="{{ __('Saving') }}…" onclick="saveThreadEdit(this)">
            {{ __('Save') }}
        </button>
    </div>
   
</div>