@extends('satratings::layouts.landing')

@section('title', $trans['title'])
@section('body_class', 'thanks')

@section('content')

    <i class="glyphicon glyphicon-ok satr-thanks-icon text-success"></i>
    <h1>{{ $trans['success_message'] }}</h1>

@endsection