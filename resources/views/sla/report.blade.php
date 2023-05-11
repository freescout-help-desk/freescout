@extends('layouts.app')
@section('content')
<div class="container">
    <table class="table datatable table-borderless">
        <thead>
            <tr>
                <th class="custom-cell">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="selectAll">
                    </div>
                </th>
                <th class="custom-cell">TICKET NO</th>
                <th class="custom-cell">STATUS</th>
                <th class="custom-cell">PREFERENCE</th>
                <th class="custom-cell">ENGINEER</th>
                <th class="custom-cell">CATEGORY</th>
                <th class="custom-cell">SUBJECT</th>
                <th class="custom-cell">RESOLUTION TIME</th>
                <th class="custom-cell">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
            @php
                $status = $ticket['status'] == 1 ? 'ACTIVE' : ($ticket['status'] == 2 ? 'PENDING' : ($ticket['status'] == 3 ? 'CLOSED' : 'SPAM'));
                $createdAt = \Carbon\Carbon::parse($ticket['created_at']);
                $lastReplyAt = \Carbon\Carbon::parse($ticket['last_reply_at']);
                $duration = $lastReplyAt->diff($createdAt);
            @endphp
            <tr>
                <td class="custom-cell">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="">
                        <label class="form-check-label" for="defaultCheck1">
                        </label>
                    </div>
                </td>
                <td class="custom-cell">#{{$ticket->number}}</td>
                <td class="custom-cell"><span class="tag tag-{{ $status }}">{{$status}}</span></td>
                <td class="custom-cell">normal</td>
                <td class="custom-cell">{{$ticket->user->first_name . ' ' . $ticket->user->last_name}}</td>
                <td class="custom-cell">network</td>
                <td class="custom-cell">{{$ticket->subject}}</td>
                <td class="custom-cell">{{$duration->format('%h HRS')}}</td>
                <td class="custom-cell">
                    <div class="text-right">
                        <i class="glyphicon glyphicon glyphicon-trash"></i>
                        <i class="glyphicon glyphicon-eye-open"></i>
                        <i class="glyphicon glyphicon-option-vertical"></i>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endsection