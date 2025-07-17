@extends('layouts.app')

@section('title', __('Tools'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('system/sidebar_menu')
@endsection

@section('content')

<div class="section-heading">
    {{ __('Tools') }}
</div>

<div class="container">

    @action('system.tools.before_form')

    <form class="form-inline margin-top" method="POST" action="">
        {{ csrf_field() }}

        @action('system.tools.form_start')

        <div>
            <button type="submit" class="btn btn-default" name="action" value="clear_cache">
                {{ __('Clear Cache') }}
            </button>
            &nbsp;
            <button type="submit" class="btn btn-default" name="action" value="migrate_db">
                {{ __('Migrate DB') }}
            </button>
            &nbsp;
            <button type="submit" class="btn btn-default" name="action" value="logout_users">
                {{ __('Logout Users') }}
            </button>
            @action('system.tools.main_buttons')
        </div>

        @action('system.tools.after_main_buttons')

        <hr/>
        <div class="margin-top text-help">
            <button type="submit" class="btn btn-default" name="action" value="fetch_emails">
                {{ __('Fetch Emails') }}
            </button>

            &nbsp;
            {{ __('Days') }}: <input type="number" name="days" value="{{ old('days', 3) }}" class="form-control input-sm" />

            &nbsp;
            <input type="radio" value="1" name="unseen" @if ((int)old('unseen', 1)) checked @endif /> {{ __('Unread') }}
            &nbsp;
            <input type="radio" value="0" name="unseen" @if (!(int)old('unseen', 1)) checked @endif /> {{ __('All') }}

            &nbsp;
            <input type="checkbox" value="1" name="debug" @if ((int)old('debug')) checked @endif /> {{ __('Debug') }}

            @action('system.tools.fetch_emails_append')
        </div>

        @action('system.tools.form_append')

    </form>

    @action('system.tools.after_form')

    @if ($output)
        @action('system.tools.before_output')
        <div class="console margin-top">{{ $output }}</div>
        @action('system.tools.after_output')
    @endif

    @action('system.tools.after_content')

</div>
@endsection
