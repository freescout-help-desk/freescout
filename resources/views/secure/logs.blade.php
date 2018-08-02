@extends('layouts.app')

@section('title', __('Logs'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    <div class="dropdown sidebar-title">
        <span class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
            {{ __('Logs') }}
        </span>
    </div>
    <ul class="sidebar-menu">
        @foreach ($names as $name)
            <li @if ($current_name == $name)class="active"@endif><i class="glyphicon glyphicon-list-alt"></i> <a href="{{ route('logs', ['name'=>$name]) }}">{{ App\ActivityLog::getLogTitle($name) }}</a></li>
        @endforeach
    </ul>
@endsection

@section('content')
<div class="container">
    <div class="section-heading margin-bottom">
        {{ __('Log Records') }}
    </div>
    @if (count($logs))
        <table id="table-logs" class="stripe hover order-column row-border" style="width:100%">
            <thead>
                <tr>
                    @foreach ($cols as $col)
                        <th>{{ ucfirst($col) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $row)
                    <tr>
                        @foreach ($row as $col => $value)
                            <td>
                                @if ($col == 'causer')
                                    <a href="{{ $value->url() }}">{{ $value->getFullName(true) }}</a>
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        @include('partials/empty', ['empty_text' => __('Log is empty')])
    @endif
</div>
@endsection

@section('stylesheets')
    <link href="{{ asset('js/datatables/datatables.min.css') }}" rel="stylesheet">
@endsection

@section('javascripts')
    <script src="{{ asset('js/datatables/datatables.min.js') }}"></script>
@endsection

@section('javascript')
    @parent
    logsInit();
@endsection