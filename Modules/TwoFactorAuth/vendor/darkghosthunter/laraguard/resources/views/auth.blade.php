@extends('laraguard::layout')

@section('card-body')
    <form action="{{ $action }}" method="post">
        @csrf
        @foreach((array)$credentials as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        @if($remember)
            <input type="hidden" name="remember" value="on">
        @endif
        <p class="text-center">
            {{ trans('laraguard::messages.continue') }}
        </p>
        <div class="form-row justify-content-center py-3">
            <div class="col-sm-8 col-8 mb-3">
                <input type="text" name="{{ $input }}" id="{{ $input }}"
                       class="@if($error) is-invalid @endif form-control form-control-lg"
                       minlength="6" placeholder="123456" required>
                @if($error)
                    <div class="invalid-feedback">
                        {{ trans('laraguard::validation.totp_code') }}
                    </div>
                @endif
            </div>
            <div class="w-100"></div>
            <div class="col-auto mb-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ trans('laraguard::messages.confirm') }}
                </button>
            </div>
        </div>
    </form>
@endsection
