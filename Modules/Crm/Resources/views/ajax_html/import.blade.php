<form class="form-horizontal crm-import-form" method="POST">

    {{ csrf_field() }}

    <div class="alert alert-info crm-import-result hidden">
    </div>

    <div class="col-sm-12 crm-import-notice">
        <div class="alert alert-warning">
            — {{ __('Each imported customer must have First Name or Email.') }}<br/>
            — {{ __('Importing mechanism compares existing and imported customers by email only. If imported customer has no email, new customer is always created. Keep this in mind to avoid creating duplicates.') }}<br/>
            — {{ __('If values on the next step are enclosed in single or double quotes, try another enclosure or another encoding.') }}<br/>
            — {{ __('If after imporing some symbols are garbled (for example look like �, ?, etc), try another encoding.') }}
        </div>
    </div>

    <div class="crm-import-step1">

        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('File') }} (CSV)</label>

            <div class="col-sm-7">
                <label class="checkbox">
                    <input type="file" name="file" accept=".csv" required class="crm-import-file">
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('Field Separator') }}</label>

            <div class="col-sm-7">
                <select name="separator" required="required" class="form-control">
                    <option value=",">{{ __('Comma') }} (,)</option>
                    <option value=";">{{ __('Semicolon') }} (;)</option>
                    <option value="TAB">TAB (\t)</option>
                    <option value="|">{{ __('Pipe') }} (|)</option>
                    <option value="&amp;">{{ __('Ampersand') }} (&amp;)</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('Enclosure') }}</label>

            <div class="col-sm-7">
                <select name="enclosure" required="required" class="form-control">
                    <option value='"'>{{ __('Double quote') }} (")</option>
                    <option value="'">{{ __('Single quote') }} (')</option>
                </select>
            </div>
        </div>       
        
        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('Encoding') }}</label>

            <div class="col-sm-7">
                <select name="encoding" required="required" class="form-control">
                    <option value="UTF-8">UTF-8</option>
                    <option value="UCS-2LE">UTF-16LE (UCS-2LE)</option>
                    <option value="Windows-1251">ANSI (Windows-1251)</option>
                    <option value="Windows-1252">ANSI (Windows-1252)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label"></label>

            <div class="col-sm-7">
                <label class="checkbox">
                    <input type="checkbox" name="skip_header" > {{ __('First row is the header − skip it') }}
                </label>
            </div>
        </div>
    </div>

    <div class="crm-import-step2 hidden">

        <div class="form-group">
            <div class="col-sm-9 col-sm-offset-3">
                <a href="#" class="btn btn-link crm-import-back">« {{ __('Back') }}</a>
            </div>
        </div>

        @foreach (Crm::getExportableFields() as $field_name => $field_title) 
            @if ($field_name == 'customers.id')
                @continue
            @endif
            <div class="form-group">

                <label class="col-sm-3 control-label">{{ $field_title }}</label>

                <div class="col-sm-9">
                    <select data-field-name="{{ $field_name }}" class="crm-import-mapping form-control">
                        
                    </select>
                </div>
            </div>
        @endforeach

    </div>

    <div class="form-group margin-top">
        <div class="col-sm-9 col-sm-offset-3">
           <button class="btn btn-primary" type="submit" data-loading-text="{{ __('Parse') }}…">{{ __('Parse') }}</button>
    	   <button class="btn btn-primary hidden" type="submit" data-loading-text="{{ __('Import') }}…">{{ __('Import') }}</button>
        </div>
    </div>
</form>