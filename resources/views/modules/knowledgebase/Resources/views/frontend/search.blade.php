@extends('knowledgebase::layouts.portal')

@section('title', __('Search'))

@section('content')

	<div class="row kb-frontend-wrapper">
		<div class="col-sm-4">
			@include('knowledgebase::partials/frontend/search')
		</div>
		<div class="col-sm-8 kb-category-content-column">
			<div class="kb-category-content">
				@include('knowledgebase::partials/frontend/breadcrumbs')
				<h1 class="kb-title">{{ __('Search results for ":query"', ['query' => $q]) }}</h1>
				<div class="margin-top">
					@if (count($articles))
						@include('knowledgebase::partials/frontend/articles', ['articles' => $articles])
					@else
						<div class="text-help text-large">{{ __('Nothing found') }}</div>
					@endif
				</div>
			</div>
		</div>
	</div>

@endsection