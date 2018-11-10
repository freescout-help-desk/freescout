<form class="form-horizontal form-reply" method="POST" action="{{ isset($reply) ? route('savedreplies.update', ['id' => $reply->id]) : route('savedreplies.create') }}">
    {{ csrf_field() }}
    <input type="hidden" name="mailbox_id" value="{{ $mailbox->id }}"/>
    <div class="thread-attachments attachments-upload form-group">
        <ul></ul>
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        <label for="name" class="col-sm-1 control-label">{{ __('Name') }}</label>

        <div class="col-sm-11">
            <input type="text" class="form-control" name="name" value="{{  isset($reply) ? ($reply->id == $reply_id ? old('name', $reply->name) : $reply->name) : ($is_new ? old('name', '') : '') }}">
            @include('partials/field_error', ['field'=>'name', 'scope' => isset($reply) ? ('updateSave'.$reply->id) : 'createSave'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }} conv-reply-body">
        <label for="body" class="col-sm-1 control-label">{{ __('Reply') }}</label>
        <div class="col-sm-11">
            <textarea class="saved-reply-body" class="form-control" name="body" rows="13" data-parsley-required="true" data-parsley-required-message="{{ __('Please enter a message') }}">{{ isset($reply) ? ($reply->id == $reply_id ? old('body', $reply->body) : $reply->body) : ($is_new ? old('body', '') : '') }}</textarea>
            <div class="help-block has-error">
                @include('partials/field_error', ['field'=>'body', 'scope' => isset($reply) ? ('updateSave'.$reply->id) : 'createSave'])
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-1 col-sm-11">
          <button type="submit" class="btn btn-primary">{{ __('Save Reply') }}</button>
          @if (isset($reply))
          <a href="#" class="btn btn-link text-danger button-delete-saved-reply" data-id="{{ $reply->id }}">{{ __('Delete') }}</a>
          @endif
        </div>
    </div>
</form>