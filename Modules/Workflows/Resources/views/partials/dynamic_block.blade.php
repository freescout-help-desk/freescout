@php
    if (empty($data)) {
        $data[] = [
            ['type' => null]
        ];
    }
    if (empty($mode)) {
        $mode = 'conditions';
    }

    $user = auth()->user();
@endphp

<div class="wf-and-blocks" data-max-index="{{ (int)max(array_keys($data)) }}">
@foreach ($data as $and_i => $ands)
    <div class="wf-and-block">
        <div class="panel panel-default panel-grey wf-panel">
            <div class="panel-body">
                <div class="wf-or-blocks" data-max-index="{{ (int)max(array_keys($ands)) }}">
                    @foreach ($ands as $row_i => $row)
                        <div class="wf-or-block wf-block @if (isset($workflow->errors()[$mode]) && isset($workflow->errors()[$mode][$and_i.'_'.$row_i])) has-error @endif">

                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button class="btn btn-default wf-or-block-remove" type="button">–</button>
                                </span>

                                <select class="form-control wf-block-type" name="{{ $mode }}[{{ $and_i }}][{{ $row_i }}][type]" >
                                    <option value="">-- @if ($mode == 'conditions'){{ __('Select a condition') }}@else{{ __('Select an action') }}@endif --</option>
                                    @foreach ($row_config as $row_info)
                                        @if (!empty($row_info['title']))
                                            <optgroup label="{{ $row_info['title'] }}">
                                        @endif
                                            @foreach ($row_info['items'] as $row_item_key => $row_item)
                                                <option value="{{ $row_item_key }}" @if (isset($row['type']) && $row_item_key == $row['type']) selected @endif>{{ $row_item['title'] }}</option>
                                            @endforeach
                                        @if (!empty($row_info['title']))
                                            </optgroup>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <span class="wf-block-operators">
                                @foreach ($row_config as $row_info)
                                    @foreach ($row_info['items'] as $row_item_key => $row_item)
                                        <span class="wf-block-operator wf-block-operator-{{ $row_item_key }} hidden">
                                            @if (!empty($row_item['operators']))
                                                <select class="form-control" name="{{ $mode }}[{{ $and_i }}][{{ $row_i }}][operator]" disabled>
                                                    @foreach ($row_item['operators'] as $value => $title)
                                                        <option value="{{ $value }}" @if (isset($row['operator']) && $value == $row['operator']) selected @endif>{{ $title }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </span>
                                    @endforeach
                                @endforeach
                            </span>
                            <span class="wf-block-values">
                                @foreach ($row_config as $row_info)
                                    @foreach ($row_info['items'] as $row_item_key => $row_item)
                                        <span class="wf-block-value wf-block-value-{{ $row_item_key }} hidden" @if (!empty($row_item['values_visible_if'])) data-values-visible-if="{{ implode(',', $row_item['values_visible_if'])}}" @endif>
                                            @php
                                                $row_value = $row['value'] ?? '';
                                                if ($row['type'] != $row_item_key) {
                                                    $row_value = '';
                                                }
                                            @endphp
                                            @if ($row_item_key == 'user_action')
                                                <select class="form-control" name="{{ $mode }}[{{ $and_i }}][{{ $row_i }}][value]" disabled>
                                                    <option value="{{ App\Conversation::USER_UNASSIGNED }}" @if (isset($row_value) && $row_value == App\Conversation::USER_UNASSIGNED) selected @endif>{{ __('Any User') }}</option>
                                                    @foreach ($mailbox->usersHavingAccess() as $user_item)
                                                        <option value="{{ $user_item->id }}" @if (isset($row_value) && $row_value == $user_item->id) selected @endif>@if ($user_item->id == $user->id){{ __('Me') }}@else {{ $user_item->getFullName() }}@endif</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($row_item_key == 'user' || $row_item_key == 'assign')
                                                <select class="form-control" name="{{ $mode }}[{{ $and_i }}][{{ $row_i }}][value]" disabled>
                                                    <option value="{{ App\Conversation::USER_UNASSIGNED }}" @if (isset($row_value) && $row_value == App\Conversation::USER_UNASSIGNED) selected @endif>{{ __('Unassigned') }}</option>
                                                    @if ($row_item_key == 'assign')
                                                        <option value="{{ \Workflow::ASSIGNEE_CURRENT }}" class="wf-type wf-type-manual @if ($workflow->isAutomatic()) hidden @endif" @if (isset($row_value) && $row_value == \Workflow::ASSIGNEE_CURRENT) selected @endif>{{ __('User running the Workflow') }}</option>
                                                    @endif
                                                    @foreach ($mailbox->usersHavingAccess() as $user_item)
                                                        <option value="{{ $user_item->id }}" @if (isset($row_value) && $row_value == $user_item->id) selected @endif>@if ($user_item->id == $user->id){{ __('Me') }}@else {{ $user_item->getFullName() }}@endif</option>
                                                    @endforeach
                                                </select>
                                            @elseif (in_array($row_item_key, ['created', 'waiting', 'user_reply', 'customer_reply']))
                                                <input class="form-control" type="number" value="{{ $row_value['number'] ?? '' }}" name="{{ $mode }}[{{ $and_i }}][{{ $row_i }}][value][number]" disabled /> 

                                                <select class="form-control" name="{{ $mode }}[{{ $and_i }}][{{ $row_i }}][value][metric]" disabled>
                                                    <option value="d" @if (isset($row_value['metric']) && $row_value['metric'] == 'd') selected @endif>{{ __('Days') }}</option>
                                                    <option value="h" @if (isset($row_value['metric']) && $row_value['metric'] == 'h') selected @endif>{{ __('Hours') }}</option>
                                                    {{--<option value="i" @if (isset($row_value['metric']) && $row_value['metric'] == 'i') selected @endif>{{ __('Minutes') }}</option>--}}
                                                </select>
                                            @elseif ($row_item_key == 'move')
                                                <select class="form-control" name="{{ $mode }}[{{ $and_i }}][{{ $row_i }}][value]" disabled>
                                                    <option value=""></option>
                                                    @foreach (App\Mailbox::getActiveMailboxes() as $mailbox_item)
                                                        <option value="{{ $mailbox_item->id }}" @if (isset($row_value) && $row_value == $mailbox_item->id) selected @endif>{{ $mailbox_item->name }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($row_item_key == 'notification')
                                                <select class="form-control wf-multiselect" name="{{ $mode }}[{{ $and_i }}][{{ $row_i }}][value][]" disabled multiple>
                                                    <option value="assignee" @if (isset($row_value) && is_array($row_value) && in_array('assignee', $row_value)) selected @endif>{{ __('Current Assignee') }}</option>
                                                    <option value="last_user" @if (isset($row_value) && is_array($row_value) && in_array('last_user', $row_value)) selected @endif>{{ __('Last User to Reply') }}</option>
                                                    @foreach ($mailbox->usersHavingAccess() as $user_item)
                                                        <option value="{{ $user_item->id }}" @if (isset($row_value) && is_array($row_value) && in_array($user_item->id, $row_value)) selected @endif>@if ($user_item->id == $user->id){{ __('Me') }}@else{{ $user_item->getFullName() }}@endif</option>
                                                    @endforeach
                                                </select>
                                            @elseif (!empty($row_item['values_custom']))
                                                @action('workflows.values_custom', $row_item_key, $row_value, $mode, $and_i, $row_i, [
                                                    'row_item' => $row_item,
                                                    'row_info' => $row_info,
                                                    'mailbox' => $mailbox,
                                                    'workflow' => $workflow,
                                                ])
                                            @elseif (isset($row_item['values']))
                                                @if (!empty($row_item['values']))
                                                    <select class="form-control" name="{{ $mode }}[{{ $and_i }}][{{ $row_i }}][value]" disabled>
                                                        @foreach ($row_item['values'] as $value => $title)
                                                            <option value="{{ $value }}" @if (isset($row_value) && $value == $row_value) selected @endif>{{ $title }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            @elseif (in_array($row_item_key, ['email_customer', 'note', 'forward']))
                                                &nbsp; <a href="{{ route('mailboxes.workflows.ajax_html', ['action' => $row_item_key, 'mailbox_id' => $mailbox->id]) }}" class="wf-email-modal" data-modal-title="@if ($row_item_key == 'forward'){{ __('Edit Forward') }}@elseif ($row_item_key == 'note'){{ __('Edit Note') }}@else{{ __('Edit Email') }}@endif" data-modal-no-footer="true" data-modal-on-show="initWfEmailForm">{{ __('Customize') }}…</a>

                                                <input class="form-control" type="hidden" value="{{ $row_value ?? '' }}" name="{{ $mode }}[{{ $and_i }}][{{ $row_i }}][value]" disabled />
                                            @elseif (!isset($row_item['values']) || !is_array($row_value))
                                                <input class="form-control {{ $row_item['values_classes'] ?? '' }}" type="{{ $row_item['values_type'] ?? 'text' }}" value="{{ $row_value ?? '' }}" name="{{ $mode }}[{{ $and_i }}][{{ $row_i }}][value]" disabled/>
                                            @endif
                                        </span>
                                    @endforeach
                                @endforeach
                            </span>
                            <div class="wf-block-or-text">
                                - {{ __('OR') }} -
                            </div>
                        </div>
                    @endforeach
                </div>
                @if ($mode != 'actions')
                    <div class="wf-block-or-trigger-wrapper">
                        <button class="btn btn-bordered btn-xs wf-block-or-trigger" type="button">+ {{ __('OR') }}</button>
                    </div>
                @endif
            </div>
        </div>
        <div class="wf-block-and-text">
            {{ __('AND') }}
        </div>
    </div>
@endforeach
</div>
<div class="text-center">
    <button class="btn btn-default btn-xs wf-block-and-trigger" type="button">+ {{ __('AND') }}</button>
</div>
