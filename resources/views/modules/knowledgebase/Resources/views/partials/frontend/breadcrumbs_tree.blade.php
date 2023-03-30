@if ($breadcrumb_category->kb_category_id)
 	@include('knowledgebase::partials/frontend/breadcrumbs_tree', ['breadcrumb_category' => \KbCategory::findCached($breadcrumb_category->kb_category_id)])
@endif
 &nbsp;<span class="text-help">»</span>&nbsp; <a href="{{ $breadcrumb_category->urlFrontend($mailbox) }}">{{ $breadcrumb_category->name }}</a>