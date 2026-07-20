{{-- Shared report page body: filter bar + stat cards + section tables.
     Expects: $data (cards, sections), $filters, $mailboxes, $users, $title --}}

<div class="section-heading">{{ $title }}</div>

<div class="container">
    <form method="GET" class="form-inline" style="margin: 15px 0;">
        <div class="form-group">
            <label>{{ __('Mailbox') }}&nbsp;</label>
            <select name="mailbox_id" class="form-control input-sm">
                <option value="">{{ __('All') }}</option>
                @foreach ($mailboxes as $mailbox)
                    <option value="{{ $mailbox->id }}" @if ($filters->mailbox_id == $mailbox->id) selected @endif>{{ $mailbox->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-left: 10px;">
            <label>{{ __('Assignee') }}&nbsp;</label>
            <select name="user_id" class="form-control input-sm">
                <option value="">{{ __('All') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @if ($filters->user_id == $user->id) selected @endif>{{ $user->getFullName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-left: 10px;">
            <label>{{ __('From') }}&nbsp;</label>
            <input type="date" name="from" class="form-control input-sm" value="{{ $filters->from->format('Y-m-d') }}" />
        </div>
        <div class="form-group" style="margin-left: 10px;">
            <label>{{ __('To') }}&nbsp;</label>
            <input type="date" name="to" class="form-control input-sm" value="{{ $filters->to->format('Y-m-d') }}" />
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="margin-left: 10px;">{{ __('Apply') }}</button>
        <button type="submit" name="format" value="csv" class="btn btn-default btn-sm" style="margin-left: 10px;">{{ __('CSV') }}</button>
        <button type="submit" name="format" value="pdf" class="btn btn-default btn-sm">{{ __('PDF') }}</button>
    </form>

    @if (!empty($data['cards']))
        <div class="row" style="margin-bottom: 10px;">
            @foreach ($data['cards'] as $card)
                <div class="col-xs-6 col-sm-3" style="margin-bottom: 15px;">
                    <div class="panel panel-default" style="margin-bottom: 0;">
                        <div class="panel-body text-center">
                            <div style="font-size: 22px; font-weight: bold;">{{ $card['value'] }}</div>
                            <div class="text-help">{{ $card['label'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="row">
        @foreach ($data['sections'] as $section)
            <div class="col-md-6" style="margin-bottom: 20px;">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>{{ $section['title'] }}</strong></div>
                    <table class="table table-striped table-condensed" style="margin-bottom: 0;">
                        <thead>
                            <tr>
                                @foreach ($section['headers'] as $header)
                                    <th>{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($section['rows'] as $row)
                                <tr>
                                    @if (!empty($section['bar']))
                                        <td style="width: 30%;">{{ $row[0] }}</td>
                                        <td>
                                            <div style="display: inline-block; vertical-align: middle; height: 12px; width: {{ $row[2] }}%; min-width: 2px; background: #6ac27b;"></div>
                                            <span style="margin-left: 6px;">{{ $row[1] }}</span>
                                        </td>
                                    @else
                                        @foreach ($row as $cell)
                                            <td>{{ $cell }}</td>
                                        @endforeach
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($section['headers']) }}" class="text-help">{{ __('No data for the selected filters.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-help">
        {{ __('Created-by-date graph lives in the standard Conversations report.') }}
        {{ __('Range') }}: {{ $filters->from->format('Y-m-d') }} — {{ $filters->to->format('Y-m-d') }}
    </p>
</div>
