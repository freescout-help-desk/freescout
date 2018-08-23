@extends('layouts.app')

@section('title', $q.' - '.__('Search'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    <div class="sidebar-title">
		{{ __('Search') }}
	</div>
    <ul class="sidebar-menu sidebar-menu-noicons">
        <li class="no-link"><h3>{{ __('Recent') }}</h3></li>
        <li class="menu-link menu-padded"><a href="javascript: alert('todo: implement recent search');void(0);">{{ $q }}</a></li>
        <li class="menu-padded"><a href="javascript: alert('todo: implement recent search');void(0);" class="help-link">{{ __('more') }}…</a></li>
        <li class="no-link"><h3>{{ __('Filters') }}</h3></li>
		@foreach (App\Conversation::$filters as $filter)
            <li class="menu-link menu-padded"><a href="javascript: alert('todo: implement search filters');void(0);">{{ $filter }}:</a></li>
        @endforeach
    </ul>
@endsection

@section('content')
	<div class="section-heading section-search">
		<form action="{{ route('conversations.search') }}">
	        <div class="input-group input-group-lg">
	            <input type="text" class="form-control" name="q" value="{{ $q }}">
	            <span class="input-group-btn">
	                <button class="btn btn-default" type="submit">{{ __('Search') }}</button>
	            </span>
	        </div>
	    </form>
	</div>
    @include('conversations/conversations_table')
@endsection

@section('javascript')
    @parent
    searchInit();
@endsection