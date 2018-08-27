@extends('layouts.app')

@section('title', __('Logs'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    <div class="sidebar-title">
        {{ __('Logs') }}
    </div>
    <ul class="sidebar-menu">
        @foreach ($names as $name)
            <li @if ($current_name == $name)class="active"@endif><i class="glyphicon glyphicon-list-alt"></i> <a href="{{ route('logs', ['name'=>$name]) }}">{{ App\ActivityLog::getLogTitle($name) }}</a></li>
        @endforeach
    </ul>
@endsection

@section('content')
<div class="container">
    <form method="post">
        {{ csrf_field() }}
        <div class="section-heading margin-bottom">
            {{ __('Log Records') }} @if ($current_name != App\ActivityLog::NAME_OUT_EMAILS)&nbsp;&nbsp;<button type="submit" name="action" value="clean" class="btn btn-default btn-xs" data-toggle="tooltip" title="{{ __('Clear this log') }}">{{ __('Clear Log') }}</button>@endif
        </div>
    </form>

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
                        @foreach ($cols as $col)
                            <td class="break-words">
                                @if (isset($row[$col]))
                                    @if ($col == 'user' || $col == 'customer')
                                        <a href="{{ $row[$col]->url() }}">{{ $row[$col]->getFullName(true) }}</a>
                                    @elseif ($col == 'date')
                                        {{  App\User::dateFormat(new Illuminate\Support\Carbon($row[$col]), 'M j, H:i:s') }}
                                    @elseif (is_object($row[$col]) && get_class($row[$col]) == 'App\Thread')
                                        <a href="{{ route('conversations.view', ['id' => $row[$col]->conversation_id]) }}#thread-{{ $row[$col]->id }}" target="_blank">#{{ $row[$col]->conversation->number }}</a>
                                    @else
                                        {{ $row[$col] }}
                                    @endif
                                @else
                                    &nbsp;
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $activities->links() }}

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