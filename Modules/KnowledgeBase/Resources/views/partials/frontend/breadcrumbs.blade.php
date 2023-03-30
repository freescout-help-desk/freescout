<div class="kb-breadcrumbs text-larger">
	 <a href="{{ \Kb::getKbUrl($mailbox) }}">{{ __('Home') }}</a>@if (isset($category)) @include('knowledgebase::partials/frontend/breadcrumbs_tree', ['breadcrumb_category' => $category])@endif
</div>