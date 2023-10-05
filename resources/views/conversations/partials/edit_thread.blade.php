<div class="thread-editor-container @if ($thread->type == \App\Thread::TYPE_NOTE) conv-note-block @endif">
    <textarea class="form-control thread-editor" rows="8">{!! htmlspecialchars($thread->body) !!}</textarea>

    <div class="thread-editor-statusbar">
        <a href="#" class="btn btn-link link-grey thread-editor-cancel">{{ __('Cancel') }}</a> 
        <button type="submit" class="btn btn-primary thread-editor-save" data-loading-text="{{ __('Saving') }}…">
            {{ __('Save') }}
        </button>
    </div>
   
</div>