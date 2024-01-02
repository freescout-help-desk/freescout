<div class="form-group">
    <div class="input-group input-group-lg">
        <select class="form-control input-lg conv-assignee-filter">
            <option value=""></option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @if ($user->id == $user_id) selected="selected" @endif>{{ $user->getFullName() }}</option>
            @endforeach
        </select>
        <span class="input-group-btn">
            <button class="btn btn-default btn-lg conv-assignee-filter-reset" type="button"><i class="glyphicon glyphicon-remove"></i></button>
        </span>
    </div>
</div>