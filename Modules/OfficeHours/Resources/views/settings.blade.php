<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
    {{ csrf_field() }}

    <div class="form-group">
        <div class="col-sm-offset-1">
            <p class="text-help">{{ __("Auto replies will be sent outside of the specified office hours.") }} {!! __("Office hours are based on your :%a_begin%company timezone:%a_end%.", ['%a_begin%' => '<a href="'.route('settings').'" target="_blank">', '%a_end%' => '</a> ('.config('app.timezone').')']) !!}</p>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __("Monday") }}</label>

        <div class="col-sm-6 form-inline">
            @include('officehours::partials/schedule_select', ['day' => 1])
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __("Tuesday") }}</label>

        <div class="col-sm-6 form-inline">
            @include('officehours::partials/schedule_select', ['day' => 2])
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __("Wednesday") }}</label>

        <div class="col-sm-6 form-inline">
            @include('officehours::partials/schedule_select', ['day' => 3])
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __("Thursday") }}</label>

        <div class="col-sm-6 form-inline">
            @include('officehours::partials/schedule_select', ['day' => 4])
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __("Friday") }}</label>

        <div class="col-sm-6 form-inline">
            @include('officehours::partials/schedule_select', ['day' => 5])
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __("Saturday") }}</label>

        <div class="col-sm-6 form-inline">
            @include('officehours::partials/schedule_select', ['day' => 6])
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __("Sunday") }}</label>

        <div class="col-sm-6 form-inline">
            @include('officehours::partials/schedule_select', ['day' => 0])
        </div>
    </div>

    <div class="form-group margin-bottom-30 margin-top-25">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary" name="action">
                {{ __('Save') }}
            </button>
        </div>
    </div>

</form>
