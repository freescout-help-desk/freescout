@extends('errors::layout')

@section('title', 'Access denied')

@section('message')
    {{ __('Access denied') }}
    @php
    	$msg = $exception->getMessage();
    @endphp
    @if ($msg && strstr($msg, '[display]'))
    	<br/><br/>{{ str_replace('[display]', '', $msg) }}
    @endif
    <br/><br/><small>{{ __('Go to') }} <a href="{{ \Helper::urlHome() }}">{{ __('Homepage') }}</a></small>
@endsection