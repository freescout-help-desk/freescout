@extends('layouts.app')

@section('title', __('System'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    <div class="dropdown sidebar-title">
        <span class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
            {{ __('System') }}
        </span>
    </div>
    <ul class="sidebar-menu">
        <li><a href="#jobs"><i class="glyphicon glyphicon-time"></i> {{ __('Jobs') }}</a></li>
    </ul>
@endsection

@section('content')
<div class="container">

    <h3 id="jobs">{{ __('Jobs') }}</h3>
    <table class="table table-dark-header table-bordered table-responsive">
        <tbody>
            <tr>
                <th>{{ __('Queued Jobs') }}</th>
                <td class="table-main-col">
                    <p>
                        {{ __('Total') }}: <strong>{{ count($queued_jobs)}}</strong>
                    </p>
                    @foreach ($queued_jobs as $job)
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th colspan="2">{{ $loop->index+1 }}. {{ json_decode($job->payload, true)['displayName'] }}</th>
                                </tr>
                                <tr>
                                    <td>{{ __('Queue') }}</td>
                                    <td>{{ $job->queue }}</td>
                                </tr>
                                <tr>
                                    <td>{{ __('Attempts') }}</td>
                                    <td>
                                        @if ($job->attempts > 0)<strong class="text-danger">@endif
                                            {{ $job->attempts }}
                                        @if ($job->attempts > 0)</strong>@endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{ __('Created At') }}</td>
                                    <td>{{  App\User::dateFormat($job->created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    @endforeach
                </td>
            </tr>
            <tr>
                <th>{{ __('Failed Jobs') }}</th>
                <td>
                    <p>
                        {{ __('Total') }}:  @if (count($failed_jobs) > 0)<strong class="text-danger">@endif{{ count($failed_jobs) }}</strong>
                    </p>
                    @foreach ($failed_jobs as $job)
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th colspan="2">{{ $loop->index+1 }}. {{ json_decode($job->payload, true)['displayName'] }}</th>
                                </tr>
                                <tr>
                                    <td>{{ __('Queue') }}</td>
                                    <td>{{ $job->queue }}</td>
                                </tr>
                                <tr>
                                    <td>{{ __('Failed At') }}</td>
                                    <td>{{  App\User::dateFormat($job->failed_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    @endforeach
                </td>
            </tr>
        </tbody>
    </table>

</div>
@endsection