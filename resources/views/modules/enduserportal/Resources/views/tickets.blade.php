@extends('enduserportal::layouts.portal')

@section('title', __('My Tickets'))

@section('content')

	<div id="eup-container">

		<div class="eup-container-padded">
			<div class="heading margin-bottom text-center">{{ __('My Tickets') }}</div>

			@if (count($tickets))
				<a href="{{ route('enduserportal.submit', ['id' => \EndUserPortal::encodeMailboxId($mailbox->id)]) }}" class="btn btn-primary margin-bottom eup-btn-create">{{ \EndUserPortal::getMailboxParam($mailbox, 'text_submit') }}</a>
			@endif
		</div>

    	@include('enduserportal::partials/tickets_table', ['conversations' => $tickets])
    </div>

@endsection