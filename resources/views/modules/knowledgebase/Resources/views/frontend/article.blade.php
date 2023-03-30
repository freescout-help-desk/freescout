@extends('knowledgebase::layouts.portal')

@if ($article)
	@section('title', $article->title)
@else
	@section('title', \Kb::getKbName($mailbox))
@endif

@section('content')

	@if (!empty($article))
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
					<h1 class="kb-title margin-bottom">{{ $article->title }}</h1>
					{!! $article->text !!}

					@if ($related_articles)
						<br/>
						<div class="text-help text-large margin-top-30">{{ __('Related Articles') }}</div>
						<div class="margin-top">
		                    @include('knowledgebase::partials/frontend/articles', ['articles' => $related_articles, 'category_id' => $category->id])
		                </div>
					@endif
				</div>
			</div>
		</div>
	@else
		@include('knowledgebase::partials/frontend/unavailable')
	@endif

@endsection