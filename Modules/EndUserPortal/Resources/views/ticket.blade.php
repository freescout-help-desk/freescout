@extends('enduserportal::layouts.portal')

@section('title', $conversation->getSubject())

@section('body_attrs')@parent data-mailbox_id_encoded="{{ \EndUserPortal::encodeMailboxId($conversation->mailbox_id) }}"@endsection

@section('content')

	<div id="eup-container">

		<div class="eup-container-padded clearfix">
			<p>
				<a href="{{ route('enduserportal.tickets', ['id' => \EndUserPortal::encodeMailboxId($mailbox->id)]) }}">« {{ __('My Tickets') }}</a>
			</p>

			<div class="heading eup-ticket-header">{{ $conversation->getSubject() }} <span class="pull-right label label-default">{{ \EndUserPortal::getStatusName($conversation) }}</span></div>
		</div>
		
		<hr/>

		@foreach($threads as $thread)
			<div class="thread thread-type-{{ $thread->getTypeName() }}">
				<div class="thread-photo">
					<img class="person-photo" src="{{ asset('/img/default-avatar.png') }}" alt=""> 
				</div>
				<div class="thread-message">
					<div class="thread-header">
					<div class="thread-title">
					    <div class="thread-person">
							<strong>
								@if ($thread->type == App\Thread::TYPE_CUSTOMER)
									{{ \EndUserPortal::authCustomer()->getMainEmail() }}
								@else
									{{ __(':mailbox.name Support', ['mailbox.name' => $mailbox->name]) }}
								@endif
							</strong>
					    </div>
					</div>
					
					<div class="thread-info">
						{{  \EndUserPortal::dateFormat($thread->created_at) }}
					</div>

					</div>
					<div class="thread-body">
						<div class="thread-content" dir="auto">
		                    {!! $thread->getBodyWithFormatedLinks() !!}
		                </div>
					 
						@if ($thread->has_attachments)
						    <div class="thread-attachments">
						        <i class="glyphicon glyphicon-paperclip"></i>
						        <ul>
						            @foreach ($thread->attachments as $attachment)
						                <li>
						                    <a href="{{ $attachment->url() }}" class="break-words" target="_blank">{{ $attachment->file_name }}</a>
						                    <span class="text-help">({{ $attachment->getSizeName() }})</span>
						                    <a href="{{ $attachment->url() }}" download><i class="glyphicon glyphicon-download-alt small"></i></a>
						                </li>
						            @endforeach
						        </ul>
						    </div>
						@endif
					</div>

				</div>
				
			</div>
		@endforeach

		<div class="eup-container-padded margin-top">
			@include('enduserportal::partials/submit_form')
		</div>

    </div>

@endsection

@section('eup_javascript')
    @parent
    eupInitSubmit();
@endsection