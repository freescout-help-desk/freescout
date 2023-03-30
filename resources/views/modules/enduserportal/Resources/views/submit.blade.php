@extends('enduserportal::layouts.portal')

@section('title', \EndUserPortal::getMailboxParam($mailbox, 'text_submit'))

@section('body_attrs')@parent data-mailbox_id_encoded="{{ request()->mailbox_id }}"@endsection

@section('content')

	<div class="row">
		<div class="col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3 col-lg-4 col-lg-offset-4">
		    <div class="panel panel-default panel-wizard">
				<div class="panel-body margin-top-0s">
					<div class="wizard-header padding-top-0">
					<h1>{{ \EndUserPortal::getMailboxParam($mailbox, 'text_submit') }}</h1>
				</div>
				<div class="wizard-body">
					<div class="row">
						<div class="col-xs-12">
							
							@include('enduserportal::partials/submit_form', ['mailbox_id' => $mailbox->id])

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('eup_javascript')
    @parent
    eupInitSubmit();
@endsection