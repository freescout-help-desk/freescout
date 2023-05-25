@extends('layouts.app')
@section('content')
<div class="container report-container">
    <table class="table datatable table-borderless slatable" >
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
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
            @php
                $dataArray = json_decode($ticket->conversationCustomField, true);
                $status = $ticket['status'] == 1 ? 'ACTIVE' : ($ticket['status'] == 2 ? 'PENDING' : ($ticket['status'] == 3 ? 'CLOSED' : 'SPAM'));
                $createdAt = \Carbon\Carbon::parse($ticket['created_at']);
                $lastReplyAt = \Carbon\Carbon::parse($ticket['last_reply_at']);
                $duration = $lastReplyAt->diff($createdAt);
            @endphp
            @foreach ($dataArray as $item)
                @php
                    $customField = $item['custom_field'];
                    $options = $customField['options'];
                    $value = $item['value'];
                    $optionValue = null;

                    foreach ($options as $key => $option) {
                        if ($key == $value) {
                            $optionValue = $option;
                            break;
                        }
                    }
                @endphp

                <p style="font-weight: bold;width: 20%;float: left;">SLA REPORT</p>
            @endforeach
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
                <td class="custom-cell">{{isset($optionValue) ? $optionValue : '-'}}</td>
                <td class="custom-cell">{{$ticket->user ? $ticket->user->first_name . ' ' . $ticket->user->last_name : "-"}}</td>
                <td class="custom-cell">network</td>
                <td class="custom-cell">{{$ticket->subject}}</td>
                <td class="custom-cell">{{$duration->format('%h HRS')}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
<style>
   .dm .slatable{
    background-color: #1d1c24;
   }
   .slatable{
    background-color: #eeeeee;
   }
   .custom-cell{
    font-size: 12px;
   }
   .dm button.dt-button, div.dt-button, a.dt-button, input.dt-button{
    color: snow;
   }
   .dm .pagination > li > a, .pagination > li > span{
      background: #1d1c24;
      color: #8bb4dd;
   }
   .dm .report-container{
    background: #1d1c24;
   }
   .report-container{
    background-color: #eeeeee;
    padding: 1.3em;
    width: 90%;
   }
   .dm .input-sm{
    border-radius: 3px;
   }
</style>
@endsection

@section('javascript')
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
<script>
$(document).ready(function() {
    $('.datatable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'csv'
        ]
    });
});
</script>

@endsection
