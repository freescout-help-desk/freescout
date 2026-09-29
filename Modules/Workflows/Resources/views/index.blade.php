@extends('layouts.app')

@section('title_full', __('workflows::messages.workflows').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('workflows::messages.workflows') }}
        <a class="btn btn-primary btn-xs" href="{{ route('mailboxes.workflows.create', ['id' => $mailbox->id]) }}">{{ __('workflows::messages.create') }}</a>
    </div>

    @include('partials/flash_messages')

    <div class="row-container">
        <div class="row">
            <div class="col-xs-12">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('workflows::messages.name') }}</th>
                            <th>{{ __('workflows::messages.type') }}</th>
                            <th>{{ __('workflows::messages.active') }}</th>
                            <th>{{ __('workflows::messages.order') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($workflows ?? [] as $workflow)
                            <tr>
                                <td>
                                    <a href="{{ route('mailboxes.workflows.edit', ['id' => $mailbox->id, 'workflow' => $workflow->id]) }}">{{ $workflow->name }}</a>
                                </td>
                                <td>
                                    @if (($workflow->type ?? '') === 'manual')
                                        {{ __('workflows::messages.manual') }}
                                    @else
                                        {{ __('workflows::messages.automatic') }}
                                    @endif
                                </td>
                                <td>
                                    @if (!empty($workflow->active))
                                        {{ __('workflows::messages.active') }}
                                    @else
                                        {{ __('workflows::messages.inactive') }}
                                    @endif
                                </td>
                                <td>{{ $workflow->sort_order }}</td>
                                <td>
                                    <form class="form-horizontal" method="POST" action="{{ route('mailboxes.workflows.sort', ['id' => $mailbox->id]) }}">
                                        {{ csrf_field() }}
                                        <input type="hidden" name="workflow" value="{{ $workflow->id }}">
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="btn btn-default btn-xs">{{ __('workflows::messages.up') }}</button>
                                    </form>
                                    <form class="form-horizontal" method="POST" action="{{ route('mailboxes.workflows.sort', ['id' => $mailbox->id]) }}">
                                        {{ csrf_field() }}
                                        <input type="hidden" name="workflow" value="{{ $workflow->id }}">
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="btn btn-default btn-xs">{{ __('workflows::messages.down') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
