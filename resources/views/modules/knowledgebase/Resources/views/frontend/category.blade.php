@extends('knowledgebase::layouts.portal')

@if ($category)
	@section('title', $category->name)
@else
	@section('title', \Kb::getKbName($mailbox))
@endif

@section('content')

	@if (!empty($category))

		<div class="row kb-frontend-wrapper">
			<div class="col-sm-4">
				@include('knowledgebase::partials/frontend/search')
				<a class="btn btn-default margin-bottom visible-xs" href="javascript: $('#kb-category-nav').toggleClass('hidden-xs');">{{ __('Toggle Navigation') }} <span class="caret"></span></a>
				<div class="hidden-xs" id="kb-category-nav">
					@include('knowledgebase::partials/frontend/category_nav', ['categories' => \KbCategory::getTree($mailbox->id), 'selected_category_id' => $category->id])
				</div>
			</div>
			<div class="col-sm-8 kb-category-content-column">
				<div class="kb-category-content">
					@include('knowledgebase::partials/frontend/breadcrumbs')
					<h1 class="kb-title">{{ $category->name }}</h1>
					@if ($category->description)
						<p class="text-help">{{ $category->description }}</p>
					@endif
					<div class="margin-top">
						@include('knowledgebase::partials/frontend/articles', ['articles' => $articles])
					</div>
				</div>
			</div>
		</div>
	@else
		@include('knowledgebase::partials/frontend/unavailable')
	@endif

@endsection