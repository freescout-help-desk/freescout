@extends('layouts.app')

@section('title_full', __('workflows::messages.workflows').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    @php
        $workflowId = $workflow->id ?? null;
        $formAction = $workflowId
            ? route('mailboxes.workflows.update', ['id' => $mailbox->id, 'workflow' => $workflowId])
            : route('mailboxes.workflows.store', ['id' => $mailbox->id]);
        $conditionRows = $workflow->conditions ?? [];
        if ($conditionRows instanceof \Illuminate\Support\Collection) {
            $conditionRows = $conditionRows->all();
        }
        $actionRows = $workflow->actions ?? [];
        if ($actionRows instanceof \Illuminate\Support\Collection) {
            $actionRows = $actionRows->all();
        }
        $groups = $conditionGroups ?? [];
        $types = $actionTypes ?? [];
        $editorUsers = $users ?? [];
        $editorUserOptions = [];
        foreach ($editorUsers as $editorUser) {
            if (is_object($editorUser)) {
                $editorUserId = $editorUser->id;
                $editorUserName = trim(($editorUser->first_name ?? '').' '.($editorUser->last_name ?? ''));
                if ($editorUserName === '' && isset($editorUser->name)) {
                    $editorUserName = $editorUser->name;
                }
            } else {
                $editorUserId = $editorUser['id'] ?? '';
                $editorUserName = $editorUser['name'] ?? trim(($editorUser['first_name'] ?? '').' '.($editorUser['last_name'] ?? ''));
            }
            $editorUserOptions[] = ['id' => $editorUserId, 'name' => $editorUserName];
        }
        $operatorMap = [];
        foreach ($groups as $group) {
            if (!is_array($group) || empty($group['items']) || !is_array($group['items'])) {
                continue;
            }
            foreach ($group['items'] as $itemType => $item) {
                if (!is_array($item) || empty($item['operators']) || !is_array($item['operators'])) {
                    continue;
                }
                $operatorMap[$itemType] = $item['operators'];
            }
        }
        $rowSets = [
            'conditions' => $conditionRows,
            'actions' => $actionRows,
        ];
    @endphp

    <div class="section-heading">
        @if ($workflowId)
            {{ __('workflows::messages.edit') }}
        @else
            {{ __('workflows::messages.create') }}
        @endif
    </div>

    @include('partials/flash_messages')

    <div class="row-container form-container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal margin-top" method="POST" action="{{ $formAction }}">
                    {{ csrf_field() }}

                    <div class="form-group">
                        <label for="workflow-name" class="col-sm-2 control-label">{{ __('workflows::messages.name') }}</label>
                        <div class="col-sm-6">
                            <input id="workflow-name" type="text" class="form-control input-sized" name="name" value="{{ $workflow->name ?? '' }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="workflow-type" class="col-sm-2 control-label">{{ __('workflows::messages.type') }}</label>
                        <div class="col-sm-6">
                            <select id="workflow-type" class="form-control input-sized" name="type">
                                <option value="automatic" @if (($workflow->type ?? 'automatic') === 'automatic') selected="selected" @endif>{{ __('workflows::messages.automatic') }}</option>
                                <option value="manual" @if (($workflow->type ?? '') === 'manual') selected="selected" @endif>{{ __('workflows::messages.manual') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="workflow-active" class="col-sm-2 control-label">{{ __('workflows::messages.active') }}</label>
                        <div class="col-sm-6">
                            <input id="workflow-active" type="checkbox" name="active" value="1" @if (!empty($workflow->active)) checked="checked" @endif>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="workflow-apply-to-previous" class="col-sm-2 control-label">{{ __('workflows::messages.apply_to_previous') }}</label>
                        <div class="col-sm-6">
                            <input id="workflow-apply-to-previous" type="checkbox" name="apply_to_previous" value="1" @if (!empty($workflow->apply_to_previous)) checked="checked" @endif>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="workflow-max-executions" class="col-sm-2 control-label">{{ __('workflows::messages.max_executions') }}</label>
                        <div class="col-sm-6">
                            <input id="workflow-max-executions" type="number" class="form-control input-sized" name="max_executions" min="1" value="{{ $workflow->max_executions ?? 1 }}">
                            <p id="workflow-max-executions-warning" class="help-block @if ((int) ($workflow->max_executions ?? 1) <= 1) hidden @endif">{{ __('workflows::messages.max_executions_warning') }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="workflow-match" class="col-sm-2 control-label">{{ __('workflows::messages.match') }}</label>
                        <div class="col-sm-6">
                            <select id="workflow-match" class="form-control input-sized" name="match">
                                <option value="all" @if (($workflow->match ?? 'all') !== 'any') selected="selected" @endif>{{ __('workflows::messages.all') }}</option>
                                <option value="any" @if (($workflow->match ?? '') === 'any') selected="selected" @endif>{{ __('workflows::messages.any') }}</option>
                            </select>
                        </div>
                    </div>

                    @foreach ($rowSets as $field => $rows)
                        <div class="form-group">
                            <div class="col-sm-12">
                                <strong>{{ __('workflows::messages.'.$field) }}</strong>
                            </div>
                        </div>
                        @foreach ($rows as $index => $row)
                            @php
                                $rowType = is_object($row) ? ($row->type ?? '') : ($row['type'] ?? '');
                                $rowOperator = is_object($row) ? ($row->operator ?? '') : ($row['operator'] ?? '');
                                $rowValue = is_object($row) ? ($row->value ?? '') : ($row['value'] ?? '');
                                $operatorOptions = $operatorMap[$rowType] ?? [];
                                $widget = 'text';
                                if ($field === 'actions' && $rowType === 'assign') {
                                    $widget = 'assign';
                                } elseif ($field === 'actions' && $rowType === 'notification') {
                                    $widget = 'notification';
                                } elseif ($rowType === 'assignee') {
                                    $widget = 'user';
                                } elseif (in_array($rowType, ['status', 'change_status'], true)) {
                                    $widget = 'status';
                                } elseif (in_array($rowType, ['waiting_since', 'last_user_reply', 'last_customer_reply', 'date_created'], true)) {
                                    $widget = 'age';
                                }
                                $textValue = is_scalar($rowValue) ? $rowValue : '';
                                $choiceValue = is_scalar($rowValue) ? (string) $rowValue : '';
                                $ageNumber = is_array($rowValue) ? ($rowValue['number'] ?? '') : '';
                                $ageUnit = is_array($rowValue) && isset($rowValue['unit']) ? $rowValue['unit'] : 'days';
                                $assignUserId = is_array($rowValue) ? ($rowValue['user_id'] ?? '') : '';
                                $onlyFlag = is_array($rowValue) ? ($rowValue['only_if_available'] ?? false) : false;
                                $onlyIfAvailable = $onlyFlag === true || $onlyFlag === 1 || $onlyFlag === '1';
                            @endphp
                            <div class="form-group" data-workflow-row>
                                <label class="col-sm-2 control-label">{{ __('workflows::messages.'.$field) }}</label>
                                <div class="col-sm-3">
                                    <select class="form-control" name="{{ $field }}[{{ $index }}][type]" data-workflow-type>
                                        @if ($field === 'conditions')
                                            @foreach ($groups as $groupKey => $group)
                                                @if (is_array($group) && !empty($group['items']) && is_array($group['items']))
                                                    <optgroup label="{{ $group['title'] ?? $groupKey }}">
                                                        @foreach ($group['items'] as $itemType => $item)
                                                            <option value="{{ $itemType }}" @if ((string) $itemType === (string) $rowType) selected="selected" @endif>{{ is_array($item) ? ($item['title'] ?? $itemType) : $itemType }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                            @endforeach
                                        @else
                                            @foreach ($types as $actionType)
                                                @php
                                                    $actionKey = is_array($actionType) ? ($actionType['type'] ?? '') : $actionType;
                                                @endphp
                                                <option value="{{ $actionKey }}" @if ((string) $actionKey === (string) $rowType) selected="selected" @endif>{{ __('workflows::messages.action_'.$actionKey) }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @if ($field === 'conditions')
                                    <div class="col-sm-3">
                                        <select class="form-control" name="conditions[{{ $index }}][operator]" data-workflow-operator>
                                            @foreach ($operatorOptions as $operatorKey => $operatorLabel)
                                                <option value="{{ $operatorKey }}" @if ((string) $operatorKey === (string) $rowOperator) selected="selected" @endif>{{ $operatorLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-sm-4">
                                    <div data-widget="text" @if ($widget !== 'text') style="display:none" @endif>
                                        <input type="text" class="form-control" name="{{ $field }}[{{ $index }}][value]" value="{{ $textValue }}" @if ($widget !== 'text') disabled="disabled" @endif>
                                    </div>
                                    <div data-widget="user" @if ($widget !== 'user') style="display:none" @endif>
                                        <select class="form-control" name="{{ $field }}[{{ $index }}][value]" @if ($widget !== 'user') disabled="disabled" @endif>
                                            <option value="anybody" @if ($choiceValue === 'anybody') selected="selected" @endif>{{ __('workflows::messages.anybody') }}</option>
                                            <option value="nobody" @if ($choiceValue === 'nobody') selected="selected" @endif>{{ __('workflows::messages.nobody') }}</option>
                                            @foreach ($editorUserOptions as $editorUserOption)
                                                <option value="{{ $editorUserOption['id'] }}" @if ($choiceValue === (string) $editorUserOption['id']) selected="selected" @endif>{{ $editorUserOption['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div data-widget="assign" @if ($widget !== 'assign') style="display:none" @endif>
                                        <select class="form-control" name="{{ $field }}[{{ $index }}][value][user_id]" @if ($widget !== 'assign') disabled="disabled" @endif>
                                            @foreach ($editorUserOptions as $editorUserOption)
                                                <option value="{{ $editorUserOption['id'] }}" @if ((string) $assignUserId === (string) $editorUserOption['id']) selected="selected" @endif>{{ $editorUserOption['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <label class="checkbox">
                                            <input type="checkbox" name="{{ $field }}[{{ $index }}][value][only_if_available]" value="1" @if ($onlyIfAvailable) checked="checked" @endif @if ($widget !== 'assign') disabled="disabled" @endif>
                                            {{ __('workflows::messages.only_if_available') }}
                                        </label>
                                    </div>
                                    <div data-widget="notification" @if ($widget !== 'notification') style="display:none" @endif>
                                        <select class="form-control" name="{{ $field }}[{{ $index }}][value]" @if ($widget !== 'notification') disabled="disabled" @endif>
                                            <option value="assignee" @if ($choiceValue === 'assignee') selected="selected" @endif>{{ __('workflows::messages.assignee') }}</option>
                                            <option value="last_user" @if ($choiceValue === 'last_user') selected="selected" @endif>{{ __('workflows::messages.last_user') }}</option>
                                            @foreach ($editorUserOptions as $editorUserOption)
                                                <option value="{{ $editorUserOption['id'] }}" @if ($choiceValue === (string) $editorUserOption['id']) selected="selected" @endif>{{ $editorUserOption['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div data-widget="status" @if ($widget !== 'status') style="display:none" @endif>
                                        <select class="form-control" name="{{ $field }}[{{ $index }}][value]" @if ($widget !== 'status') disabled="disabled" @endif>
                                            @foreach (['active', 'pending', 'closed', 'spam'] as $statusSlug)
                                                <option value="{{ $statusSlug }}" @if ($choiceValue === $statusSlug) selected="selected" @endif>{{ __('workflows::messages.status_'.$statusSlug) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div data-widget="age" @if ($widget !== 'age') style="display:none" @endif>
                                        <input type="number" class="form-control" name="{{ $field }}[{{ $index }}][value][number]" value="{{ $ageNumber }}" @if ($widget !== 'age') disabled="disabled" @endif>
                                        <select class="form-control" name="{{ $field }}[{{ $index }}][value][unit]" @if ($widget !== 'age') disabled="disabled" @endif>
                                            <option value="days" @if ($ageUnit === 'days') selected="selected" @endif>{{ __('workflows::messages.days') }}</option>
                                            <option value="hours" @if ($ageUnit === 'hours') selected="selected" @endif>{{ __('workflows::messages.hours') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach

                    <div class="hidden" data-workflow-widgets>
                        <input type="text" value="">
                        <select>
                            <option value="anybody">{{ __('workflows::messages.anybody') }}</option>
                            <option value="nobody">{{ __('workflows::messages.nobody') }}</option>
                            @foreach ($editorUserOptions as $editorUserOption)
                                <option value="{{ $editorUserOption['id'] }}">{{ $editorUserOption['name'] }}</option>
                            @endforeach
                        </select>
                        <select>
                            <option value="active">{{ __('workflows::messages.status_active') }}</option>
                            <option value="pending">{{ __('workflows::messages.status_pending') }}</option>
                            <option value="closed">{{ __('workflows::messages.status_closed') }}</option>
                            <option value="spam">{{ __('workflows::messages.status_spam') }}</option>
                        </select>
                        <input type="number" value="">
                        <select>
                            <option value="days">{{ __('workflows::messages.days') }}</option>
                            <option value="hours">{{ __('workflows::messages.hours') }}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-6">
                            <button type="submit" class="btn btn-primary">{{ __('workflows::messages.save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    (function () {
        var workflowOperators = {!! json_encode($operatorMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!};
        var statusTypes = {status: true, change_status: true};
        var ageTypes = {waiting_since: true, last_user_reply: true, last_customer_reply: true, date_created: true};

        function widgetName(type) {
            if (type === 'assign') {
                return 'assign';
            }
            if (type === 'notification') {
                return 'notification';
            }
            if (type === 'assignee') {
                return 'user';
            }
            if (statusTypes[type]) {
                return 'status';
            }
            if (ageTypes[type]) {
                return 'age';
            }
            return 'text';
        }

        function rebuildOperators(row) {
            var typeSelect = row.querySelector('[data-workflow-type]');
            var operatorSelect = row.querySelector('[data-workflow-operator]');
            if (!typeSelect || !operatorSelect) {
                return;
            }
            var operators = workflowOperators[typeSelect.value] || {};
            var current = operatorSelect.value;
            while (operatorSelect.firstChild) {
                operatorSelect.removeChild(operatorSelect.firstChild);
            }
            var keys = Object.keys(operators);
            for (var i = 0; i < keys.length; i++) {
                var option = document.createElement('option');
                option.value = keys[i];
                option.text = operators[keys[i]];
                if (keys[i] === current) {
                    option.selected = true;
                }
                operatorSelect.appendChild(option);
            }
        }

        function applyRow(row) {
            var select = row.querySelector('[data-workflow-type]');
            if (!select) {
                return;
            }
            var name = widgetName(select.value);
            var widgets = row.querySelectorAll('[data-widget]');
            for (var i = 0; i < widgets.length; i++) {
                var on = widgets[i].getAttribute('data-widget') === name;
                widgets[i].style.display = on ? '' : 'none';
                var fields = widgets[i].querySelectorAll('input, select, textarea');
                for (var j = 0; j < fields.length; j++) {
                    fields[j].disabled = !on;
                }
            }
        }

        var rows = document.querySelectorAll('[data-workflow-row]');
        for (var i = 0; i < rows.length; i++) {
            (function (row) {
                var select = row.querySelector('[data-workflow-type]');
                if (!select) {
                    return;
                }
                applyRow(row);
                select.addEventListener('change', function () {
                    rebuildOperators(row);
                    applyRow(row);
                });
            })(rows[i]);
        }

        var maxInput = document.getElementById('workflow-max-executions');
        var warning = document.getElementById('workflow-max-executions-warning');
        if (maxInput && warning) {
            maxInput.addEventListener('input', function () {
                if (parseInt(maxInput.value, 10) > 1) {
                    warning.classList.remove('hidden');
                } else {
                    warning.classList.add('hidden');
                }
            });
        }
    })();
@endsection
