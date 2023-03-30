@extends('knowledgebase::layouts.portal')

@section('title', \Kb::getKbName($mailbox))

@section('content')

	<div class="heading kb-front-heading">
		{{ \Kb::getKbName($mailbox) }}

		<div class="row">
			<form class="col-sm-6 col-sm-offset-3" action="{{ \Kb::route('knowledgebase.frontend.search', ['mailbox_id'=>\Kb::encodeMailboxId($mailbox->id)], $mailbox) }}">
				<div class="input-group input-group-lg margin-top">
					<input type="text" class="form-control" name="q">
						<span class="input-group-btn">
						<button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i></button>
					</span>
				</div>
			</form>
		</div>
	</div>

	@if (count($categories))
		@include('knowledgebase::partials/frontend/category_panels', ['categories' => $categories])
	@elseif (count($articles))
		@include('knowledgebase::partials/frontend/articles', ['articles' => $articles])
	@else
		@include('partials/empty', ['icon' => 'book'])
	@endif

@endsection