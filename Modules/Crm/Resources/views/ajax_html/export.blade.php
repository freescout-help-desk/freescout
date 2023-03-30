<form class="form-horizontal" method="POST" action="{{ route('crm.export') }}" target="_blank">

    {{ csrf_field() }}

    <div class="form-group">

        <label class="col-sm-3 control-label">{{ __('Fields') }}</label>

        <div class="col-sm-9">
            @foreach (Crm::getExportableFields() as $field_name => $field_title) 
                <label class="checkbox col-xs-6">
                    <input type="checkbox" name="fields[]" value="{{ $field_name }}" checked /> {{ $field_title }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{ __('Encoding') }}</label>

        <div class="col-sm-7">
            <select name="encoding" class="form-control">
                <option value="UCS-2LE">UTF-16LE (UCS-2LE)</option>
                <option value="UTF-8">UTF-8</option>
                <option value="Windows-1251">ANSI (Windows-1251)</option>
                <option value="Windows-1252">ANSI (Windows-1252)</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{ __('Field Separator') }}</label>

        <div class="col-sm-7">
            <select name="separator" class="form-control">
                <option value="TAB">TAB (\t)</option>
                <option value=",">{{ __('Comma') }} (,)</option>
                <option value=";">{{ __('Semicolon') }} (;)</option>
                <option value="|">{{ __('Pipe') }} (|)</option>
                <option value="&amp;">{{ __('Ampersand') }} (&amp;)</option>
            </select>
        </div>
    </div>

    <div class="form-group margin-top">
        <div class="col-sm-9 col-sm-offset-3">
    	   <button class="btn btn-primary" type="submit">{{ __('Export') }}</button>
        </div>
    </div>
</form>