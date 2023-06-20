@extends('layouts.app')
@section('title', __('SLA Report'))
@section('content')
    <i class="glyphicon glyphicon-filter filter-trigger"></i>
    <div class="rpt-header">
        <form action="{{ route('slafilter') }}" method="GET" class="top-form">
            <div class="rpt-filters">


                <div class="rpt-filter">
                    <label>
                        {{ __('Tickets Category') }}
                    </label>
                    <select class="form-control" name="ticket">
                        <option value="0">All</option>
                        @foreach ($categoryValues as $category)
                            <option value="{{ $category }}" {{ $filters['ticket'] === $category ? 'selected' : '' }}>
                                {{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rpt-filter">
                    <label>
                        {{ __('Product') }}
                    </label>
                    <select class="form-control" name="product">
                        <option value="0">All</option>
                        @foreach ($productValues as $product)
                            <option value="{{ $product }}" {{ $filters['product'] === $product ? 'selected' : '' }}>
                                {{ $product }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rpt-filter">
                    <label>
                        {{ __('Type') }}
                    </label>
                    <select class="form-control" name="type">
                        <option value="0">All</option>
                        <option value="{{ App\Conversation::TYPE_EMAIL }}"
                            {{ $filters['type'] == App\Conversation::TYPE_EMAIL ? 'selected' : '' }}>{{ __('Email') }}
                        </option>
                        <option value="{{ App\Conversation::TYPE_CHAT }}"
                            {{ $filters['type'] == App\Conversation::TYPE_CHAT ? 'selected' : '' }}>{{ __('Chat') }}
                        </option>
                        <option value="{{ App\Conversation::TYPE_PHONE }}"
                            {{ $filters['type'] == App\Conversation::TYPE_PHONE ? 'selected' : '' }}>{{ __('Phone') }}
                        </option>
                    </select>
                </div>
                <div class="rpt-filter">
                    <label>
                        {{ __('Mailbox') }}
                    </label>
                    <select class="form-control" name="mailbox">
                        <option value="0">All</option>
                        @foreach (Auth::user()->mailboxesCanView(true) as $mailbox)
                            <option value="{{ $mailbox->id }}"
                                {{ $filters['mailbox'] == $mailbox->id ? 'selected' : '' }}>{{ $mailbox->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="rpt-filter">
                    <label class="mobile-label">{{ __('Date Range') }}</label>
                    <nobr><input type="date" name="from" class="form-control rpt-filter-date"
                            value="{{ $filters['from'] }}" />-<input type="date" name="to"
                            class="form-control rpt-filter-date" value="{{ $filters['to'] }}" /></nobr>
                </div>

                <div class="rpt-filter" data-toggle="tooltip" title="{{ __('Refresh') }}">
                    <button class="btn btn-primary" id="rpt-btn-loader" onclick="refreshPage()" type="submit"><i
                            class="glyphicon glyphicon-refresh"></i></button>
                </div>

            </div>

        </form>
    </div>
    <div class="container report-container">
        <p style="font-weight: bold;width: 20%;float: left;">SLA REPORT</p>
        <table class="table datatable table-borderless slatable">
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
                @foreach ($tickets as $ticket)
                    @php
                        $dataArray = json_decode($ticket->conversationCustomField, true);
                        $ticketPriorityArray = json_decode($ticket->conversationPriority, true);
                        $ticketCategoryArray = json_decode($ticket->conversationCategory, true);
                        $ticketEscalated = json_decode($ticket->conversationEscalated, true);
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

                    @php
                        $MailboxId = $ticket['mailbox_id'];
                        $MailboxName = App\Mailbox::find($MailboxId);
                    @endphp


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
                        $rtime = $duration->format('%h HRS');
                        $restime = null;
                        if ($rtime == 0) {
                            $restime = 'N/A';
                        } else {
                            $restime = $rtime;
                        }

                    @endphp
                    <tr>
                        <td class="custom-cell">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="">
                                <label class="form-check-label" for="defaultCheck1">
                                </label>
                            </div>
                        </td>
                        <td class="custom-cell">#{{ $ticket->number }}</td>
                        <td class="custom-cell"><span class="tag tag-{{ $status }}">{{ $status }}</span>
                        </td>
                        <td class="custom-cell">{{ isset($ticketPriority) ? $ticketPriority : '-' }}</td>
                        <td class="custom-cell">
                            {{ $ticket->user ? $ticket->user->first_name . ' ' . $ticket->user->last_name : '-' }}</td>
                        <td class="custom-cell">{{ isset($ticketCategory) ? $ticketCategory : '-' }}</td>
                        <td class="custom-cell">{{ $ticket->subject }}</td>
                        <td class="custom-cell">{{ $MailboxName->name ? $MailboxName->name : '-' }}</td>
                        <td class="custom-cell">{{ isset($ticketEscalate) ? 'YES' : 'NO' }}</td>
                        <td class="custom-cell">{{ $ticket->created_at }}</td>
                        <td class="custom-cell">{{ $restime }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    <style>
        .content {
            margin-top: 0;
        }

        .dm .rpt-filter-date {
            color: #fff
        }

        .top-form {
            display: flex;
            height: auto;
            align-items: center;
            justify-content: space-evenly;
            background: #deecf9;
        }

        .dm .top-form {
            background: transparent;
        }

        .dm .rpt-header {
            background-color: transparent;
        }

        .rpt-header {
            box-shadow: -3px 13px 19px -2px #00000057;
        }

        .opn-menu {
            padding: 12px 18px;
            width: 75%;
            left: 25%;
        }

        .filter-trigger {
            display: none;
        }

        @media (max-width: 600px) {
            .rpt-filter {
                display: flex;
                flex-direction: column;
                margin-bottom: 1.5em;
            }

            .rpt-header {
                position: absolute;
                z-index: 9;
                height: 100vh;
                width: 0;
                transition: 200ms;
                padding: 0;
                left: 100%;
                box-shadow: -3px 13px 19px -2px #00000057;
            }

            .opn-menu {
                padding: 12px 18px;
                width: 75%;
                left: 25%;
            }

            .mobile-label {
                display: block;
            }


            .dm .bar-container {
                display: flex;
                flex-direction: column;
            }

            .bar-container {
                display: flex;
                flex-direction: column;
            }


            .row-text-center1 {
                display: flex;
                flex-direction: row;
                margin-left: 0px;
            }


            .stat-options {
                font-size: 15px;
                max-width: 100%;
                margin-top: 20px;
            }

            .stat-values {
                font-size: 35px;
                max-width: 100%;
            }

            .filter-trigger {
                display: block;
                color: #0078d7;
                font-size: 15px;
                position: absolute;
                right: 0;
                background: white;
                padding: 0.5em;
                top: 50px;
                box-shadow: 4px 2px 8px 1px #0000008f;
                border: 1px solid #0078d7;
                transition: 200ms;
            }

            .opn-filter {
                right: 75%;
            }

            .slatable {
                overflow-x: auto;
                display: flow-root;
            }

        }

        @media only screen and (max-width: 1200px) {
            .slatable {
                overflow-x: auto;
                display: flow-root;
            }

        }

        @media only screen and (max-width: 800px) {
            .slatable {
                overflow-x: auto;
                display: flow-root;
            }
        }

        @media screen and (max-width: 640px) {
            div.dt-buttons {
            float: right !important;
            text-align: center !important;
        }
    }

        */ .dm .form-control {
            display: inline;
            width: 140px;
            min-inline-size: max-content;
        }

        .form-control {
            display: inline;
            width: 140px;
            min-inline-size: max-content;
        }

        .dm .slatable {
            background-color: #1d1c24;
        }

        .slatable {
            background-color: #eeeeee;
        }

        .custom-cell {
            font-size: 12px;
        }

        .dm button.dt-button,
        div.dt-button,
        a.dt-button,
        input.dt-button {
            color: snow;
        }

        .dm .pagination>li>a,
        .pagination>li>span {
            background: #1d1c24;
            color: #8bb4dd;
        }

        .dm .report-container {
            background: #1d1c24;
        }

        .report-container {
            background-color: #eeeeee;
            padding: 1.3em;
            width: 93%;
            margin-top: 2em;
            border-radius: 7px;
        }

        .dm .input-sm {
            border-radius: 3px;
        }

        .dt-button-collection {
            left: -4em !important;
            width: 8em !important;
        }

        .dm .dt-button-collection {
            left: -4em !important;
            width: 8em !important;
            background: #363636 !important;
        }

        .dt-button {
            border: none !important;
            background: transparent !important;
        }

        .dt-button.buttons-csv:hover {
            border: none !important;
            background: #0078d7 !important;
            border-radius: 6px !important;
        }

        .dt-button.buttons-pdf:hover {
            border: none !important;
            background: #0078d7 !important;
            border-radius: 6px !important;
        }

        .dm .dt-button.buttons-csv:hover {
            border: none !important;
            background: #131414 !important;
            border-radius: 6px !important;
        }

        .dm .dt-button.buttons-pdf:hover {
            border: none !important;
            background: #131414 !important;
            border-radius: 6px !important;
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
        $(document).on('click', '.filter-trigger', function() {
            $('.rpt-header').toggleClass('opn-menu');
            $('.filter-trigger').toggleClass('opn-filter');
            if ($('.filter-trigger').hasClass('glyphicon-remove')) {
                $('.filter-trigger').removeClass('glyphicon-remove');
                $('.filter-trigger').addClass('glyphicon-filter');
            } else {
                $('.filter-trigger').addClass('glyphicon-remove');
                $('.filter-trigger').removeClass('glyphicon-filter');
            }
        });
        $(document).ready(function() {
            $('.datatable').DataTable({
                dom: 'Bfrtip',
                buttons: [{
                    extend: 'collection',
                    text: '<span class="glyphicon glyphicon-download-alt"></span>',
                    buttons: [
                        'csv',
                        {
                            extend: 'pdfHtml5',
                            text: 'PDF',
                            orientation: 'landscape',
                            exportOptions: {
                                columns: ':visible'
                            },
                        }
                    ]
                }]
            });
        });

        function refreshPage() {
            location.reload();
        }
    </script>
@endsection
