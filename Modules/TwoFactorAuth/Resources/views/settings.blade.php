<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
    {{ csrf_field() }}

    <input type="hidden" name="settings[dummy]" value="1" />

    <div class="form-group">
        <label for="twofactorauth_required" class="col-sm-2 control-label">{{ __("Required For All Users") }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[twofactorauth.required]" value="1" id="twofactorauth_required" class="onoffswitch-checkbox" @if (old('settings[twofactorauth.required]', $settings['twofactorauth.required']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="twofactorauth_required"></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>

</form>

@section('javascript')
    @parent
    initSpamFilterSettings();
@endsection