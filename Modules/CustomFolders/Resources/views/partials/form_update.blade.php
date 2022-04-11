<?php
$folderIcon = $folder->meta['icon'] ?? '';
?>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Name') }}</label>

    <div class="col-sm-10">
        <input class="form-control" name="name" value="{{ $folder->meta['name'] ?? '' }}" maxlength="15" required/>
    </div>
</div>

<hr/>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Tag') }}</label>

    <div class="col-sm-10">
        <select name="tag_name" class="form-control" style="width: 100%">
            @if ($folder->tag_name)
                <option value="{{ $folder->tag_name }}" selected>{{ $folder->tag_name }}</option>
            @endif
        </select>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Show Only To') }}</label>

    <div class="col-sm-10">
        <select name="user_id" class="form-control">
            <option value=""></option>
            @php
                if (!isset($mailbox)) {
                    $mailbox = $folder->mailbox;
                }
            @endphp
            @foreach($mailbox->usersHavingAccess(true) as $user)
                <option value="{{ $user->id }}" @if ($folder->user_id && $folder->user_id == $user->id) selected @endif>{{ $user->getFullName() }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Status') }}</label>

    <div class="col-sm-10">
        @php
            $status_filter = $folder->meta['status_filter'] ?? [];
            if (empty($status_filter)) {
                $status_filter = array_keys(App\Conversation::$statuses);
            }
        @endphp
        @foreach(App\Conversation::$statuses as $status_code => $dummy)
            <label class="checkbox checkbox-inline" for="status_filter_{{ $status_code }}">
                <input type="checkbox" name="status_filter[]" value="{{ $status_code }}" id="status_filter_{{ $status_code }}" @if (in_array($status_code, $status_filter)) checked @endif />{{ App\Conversation::statusCodeToName($status_code) }}
            </label>
        @endforeach
    </div>
</div>

<div class="form-group">
    <div class="col-sm-2"></div>

    <div class="col-sm-10">
        <label class="checkbox" for="own_only">
            <input type="checkbox" name="own_only" value="1" id="own_only" @if (!empty($folder->meta['own_only'])) checked @endif />{{ __('Show only own conversations') }}
        </label>
    </div>
</div>

<hr/>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Counter') }}</label>

    <div class="col-sm-10">
        <select name="counter" class="form-control" required>
            <option value="{{ App\Folder::COUNTER_TOTAL }}" @if (!empty($folder->meta['counter']) && $folder->meta['counter'] == App\Folder::COUNTER_TOTAL) selected @endif>{{ __('Count all conversations') }}</option>
            <option value="{{ App\Folder::COUNTER_ACTIVE }}" @if (!empty($folder->meta['counter']) && $folder->meta['counter'] == App\Folder::COUNTER_ACTIVE) selected @endif>{{ __('Count only active') }}</option>
        </select>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Icon') }}</label>

    <div class="col-sm-10">
        <select name="icon" class="form-control">
            <option value=""></option>
            @foreach(config('customfolders.icons') as $icon)
                <option value="{{ $icon }}" {{ ($folderIcon == $icon)? 'selected' : '' }}>{{ ucfirst(str_replace('-', ' ', $icon)) }}</option>
            @endforeach
        </select>
        <div class="form-help">
            <a href="https://glyphicons.bootstrapcheatsheets.com/" target="_blank">{{ __('Icons') }}</a>
        </div>
    </div>
</div>