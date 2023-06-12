@extends('layouts.app')
@section('content')
<div class="rpt-header">
<form action="{{ route('slafilter') }}" method="GET" class="container slafilter">
    <div class="rpt-filters row">


        <div class="rpt-filter">
            {{ __('Tickets Category') }}
            <select class="form-control ticket_select" name="ticket" >
                <option value="0">All</option>
                @foreach ($categoryValues as $category)
                    <option value="{{$category}}"{{ $filters['ticket'] === $category ? 'selected' : '' }}>{{$category}}</option>
                @endforeach
            </select>
        </div>
        <div class="rpt-filter">
            {{ __('Product') }}
            <select class="form-control product_select" name="product">
                <option value="0">All</option>
                @foreach ($productValues as $product)
                    <option value="{{$product}}" {{ $filters['product'] === $product ? 'selected' : '' }}>{{$product}}</option>
                @endforeach
            </select>
        </div>
        <div class="rpt-filter">
            {{ __('Type') }}
            <select class="form-control type_select" name="type">
                <option value="0">All</option>
                <option value="{{ App\Conversation::TYPE_EMAIL }}" {{ $filters['type'] == App\Conversation::TYPE_EMAIL ? 'selected' : '' }}>{{ __('Email') }}</option>
                <option value="{{ App\Conversation::TYPE_CHAT }}" {{ $filters['type'] == App\Conversation::TYPE_CHAT ? 'selected' : '' }}>{{ __('Chat') }}</option>
                <option value="{{ App\Conversation::TYPE_PHONE }}" {{ $filters['type'] == App\Conversation::TYPE_PHONE ? 'selected' : '' }}>{{ __('Phone') }}</option>
            </select>
        </div>
        <div class="rpt-filter">
            {{ __('Mailbox') }}
            <select class="form-control mailbox_select" name="mailbox">
                <option value="0">All</option>
                @foreach (Auth::user()->mailboxesCanView(true) as $mailbox)
                    <option value="{{ $mailbox->id }}" {{ $filters['mailbox'] == $mailbox->id ? 'selected' : '' }}>{{ $mailbox->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="rpt-filter">
            <nobr><input type="date" name="from" class="form-control rpt-filter-date" value="{{ $filters['from'] }}" />-<input type="date" name="to" class="form-control rpt-filter-date" value="{{ $filters['to'] }}" /></nobr>
        </div>

        <div class="rpt-filter" data-toggle="tooltip" title="{{ __('Refresh') }}">
            <button class="btn btn-primary" id="rpt-btn-loader" onclick="refreshPage()" type="submit"><i class="glyphicon glyphicon-refresh"></i></button>
        </div>

    </div>

</form>
</div>
<div class="container report-container">
    <p style="font-weight: bold;width: 20%;float: left;">SLA REPORT</p>
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
                <th class="custom-cell">Priority</th>
                <th class="custom-cell">ENGINEER</th>
                <th class="custom-cell">CATEGORY</th>
                <th class="custom-cell">SUBJECT</th>
                <th class="custom-cell">Mailbox</th>
                <th class="custom-cell">Escalated</th>
                <th class="custom-cell">Created date</th>
                <th class="custom-cell">RESOLUTION TIME</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
            @php
                $dataArray = json_decode($ticket->conversationCustomField, true);
                $ticketPriorityArray =json_decode($ticket->conversationPriority, true);
                $ticketCategoryArray =json_decode($ticket->conversationCategory, true);
                $ticketEscalated =json_decode($ticket->conversationEscalated, true);
                $status = $ticket['status'] == 1 ? 'ACTIVE' : ($ticket['status'] == 2 ? 'PENDING' : ($ticket['status'] == 3 ? 'CLOSED' : 'SPAM'));
                $createdAt = \Carbon\Carbon::parse($ticket['created_at']);
                $lastReplyAt = \Carbon\Carbon::parse($ticket['last_reply_at']);
                $duration = $lastReplyAt->diff($createdAt);
            @endphp
            @foreach ($dataArray as $item)
                @php
                    $customField = $item['custom_field'];
                    $options = $customField['options'];
                    $name = $customField['name'];
                    $value = $item['value'];
                    $optionValue = null;
                    foreach ($options as $key => $option) {
                        if ($key == $value) {
                            $optionValue = $option;
                            break;
                        }
                    }
                @endphp
            @endforeach



            @foreach ($ticketCategoryArray as $item)
            @php

                $options = $item['options'];
                $ticketCategory = null;
                foreach ($options as $key => $option) {
                    if ($key == $value) {
                        $ticketCategory = $option;
                        break;
                    }
                }
            @endphp

        @endforeach

            @foreach ($ticketPriorityArray as $item)
            @php

                $options = $item['options'];
                $ticketPriority = null;
                foreach ($options as $key => $option) {
                    if ($key == $value) {
                        $ticketPriority = $option;
                        break;
                    }
                }
            @endphp

        @endforeach

        @foreach ($ticketEscalated as $item)
        @php

            $options = $item['options'];
            $ticketEscalate = null;
            foreach ($options as $key => $option) {
                if ($key == $value) {
                    $ticketEscalate = $option;
                    break;
                }
            }
        @endphp

    @endforeach
        @php
            $rtime=$duration->format('%h HRS');
            $restime=null;
            if($rtime==0){
                $restime="N/A";
            }else{
                $restime=$rtime;
            }

        @endphp
        @if ($filters['ticket']==='0')
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
            <td class="custom-cell">{{isset($ticketPriority) ? $ticketPriority : '-'}}</td>
            <td class="custom-cell">{{$ticket->user ? $ticket->user->first_name . ' ' . $ticket->user->last_name : "-"}}</td>
            <td class="custom-cell">{{isset($ticketCategory) ? $ticketCategory : '-'}}</td>
            <td class="custom-cell">{{$ticket->subject}}</td>
            <td class="custom-cell">{{$ticket->user ? $ticket->user->email : "-"}}</td>
            <td class="custom-cell">{{isset($ticketEscalate) ? 'YES' : 'NO'}}</td>
            <td class="custom-cell">{{$ticket->created_at}}</td>
            <td class="custom-cell">{{$restime}}</td>
        </tr>
        @elseif(isset($ticketCategory) && $ticketCategory === $filters['ticket'])
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
                <td class="custom-cell">{{isset($ticketPriority) ? $ticketPriority : '-'}}</td>
                <td class="custom-cell">{{$ticket->user ? $ticket->user->first_name . ' ' . $ticket->user->last_name : "-"}}</td>
                <td class="custom-cell">{{isset($ticketCategory) ? $ticketCategory : '-'}}</td>
                <td class="custom-cell">{{$ticket->subject}}</td>
                <td class="custom-cell">{{$ticket->user ? $ticket->user->email : "-"}}</td>
                <td class="custom-cell">{{isset($ticketEscalate) ? 'YES' : 'NO'}}</td>
                <td class="custom-cell">{{$ticket->created_at}}</td>
                <td class="custom-cell">{{$restime}}</td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

</div>
<style>

.content{
    margin-top: 0;
}
.slafilter{
    display: flex;
    height: 4em;
    align-items: center;
    justify-content: space-evenly;
    background: #deecf9;
}

.dm .top-form{
    background: #005eb4;
}
@media only screen and (max-width: 1500px){
.slafilter {
    margin-bottom: 1em;
}
}

@media only screen and (max-width: 1200px){
    .slafilter{
        margin-top: 1em;
    }
    .ticket_select{
        margin-left: 10px;
    }
    .product_select{
        margin-left: 64px;
    }
    .type_select{
        margin-left: 81px;
    }
    .mailbox_select{
        margin-left: 64px;
    }
    .rpt-filter{
        margin-bottom: 10px;
    }
    .slatable{
    overflow-x: auto;
    display: flow-root;
}

}
@media only screen and (max-width: 800px){
    .slafilter{
        margin-bottom: 1em;
        margin-left: 41px;
    }
    .ticket_select{
        margin-left: 10px;
    }
    .product_select{
        margin-left: 64px;
    }
    .type_select{
        margin-left: 81px;
    }
    .mailbox_select{
        margin-left: 64px;
    }
    .rpt-header {
    background-color: #deecf9;
    padding: 90px 18px;
    line-height: 30px;
    overflow: auto;
}
.slatable{
    overflow-x: auto;
    display: flow-root;
}
}
@media only screen and (min-width: 801px)and (max-width: 1000px){
    .rpt-header {
    background-color: #deecf9;
    padding: 37px 13px;
    line-height: 30px;
    overflow: auto;
}
}
@media only screen and (max-width: 425px){
    .slafilter{
        margin-bottom: 1em;
        margin-left: 20px;
    }
     .ticket_select{
        margin-left: 10px;
    }
    .product_select{
        margin-left: 64px;
    }
    .type_select{
        margin-left: 81px;
    }
    .mailbox_select{
        margin-left: 64px;
    }
}
    .slafilter{
        margin-bottom: 1em;
    }
    .dm .form-control {
    display: inline ;
    width: 140px;
    min-inline-size: max-content;
}

.form-control {
    display: inline ;
    width: 140px;
    min-inline-size: max-content;
}
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
    margin-top: 1em;
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/vfs_fonts.js"></script>
<script>
$(document).ready(function() {
    $('.datatable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'csv',
            {
                extend: 'pdfHtml5',
                text: 'PDF',
                orientation:'landscape',
                exportOptions: {
                    columns: ':visible'
                },
            }
        ]
    });
});
function refreshPage() {
    location.reload();
}
</script>

@endsection
