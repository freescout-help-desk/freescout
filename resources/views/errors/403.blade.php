@extends('errors::layout')

@section('title', 'Access denied')

@section('message')
    {{ __('Access denied') }}
    <br/><br/><small>{{ __('Go to') }} <a href="{{ url('/') }}">{{ __('Homepage') }}</a></small>
@endsection