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

    <form class="form-horizontal margin-top" method="POST" action="">
        {{ csrf_field() }}

        <button type="submit" class="btn btn-default" name="action" value="fetch_emails">
            {{ __('Fetch Emails') }}
        </button>
        &nbsp;
        
        <button type="submit" class="btn btn-default" name="action" value="clear_cache">
            {{ __('Clear Cache') }}
        </button>
        &nbsp;

        <button type="submit" class="btn btn-default" name="action" value="migrate_db">
            {{ __('Migrate DB') }}
        </button>
    </form>

    @if ($output)
        <div class="console margin-top">{{ $output }}</div>
    @endif

</div>
@endsection
