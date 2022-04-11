@extends('layouts.app')

@section('content')
    <p class="text-center">
        {{ trans('laraguard::messages.enable') }}
    </p>
    @isset($url)
    <div class="col-auto mb-3">
        <a href="{{ $url }}" class="btn btn-primary btn-lg">
            {{ trans('laraguard::messages.switch_on') }} &raquo;
        </a>
    </div>
    @endisset
@endsection
