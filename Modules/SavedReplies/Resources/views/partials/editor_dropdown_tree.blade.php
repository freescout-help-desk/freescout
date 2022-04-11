@php
	if (!isset($parents)) {
		$parents = [];
	}
@endphp
@foreach ($saved_replies as $saved_reply)
	<li @if ($parents)data-parent-id="{{ $saved_reply->parent_saved_reply_id }}" data-parents="{{ implode(',', $parents) }}" class="hidden"@endif><a href="#" data-value="{{ $saved_reply->id }}">@for ($i=0; $i < count($parents); $i++)&nbsp;&nbsp;&nbsp;@endfor{{ $saved_reply->name }}@if (!empty($saved_reply->saved_replies)) <span class="caret"></span>@endif</a></li>
	@if (!empty($saved_reply->saved_replies))
		@include('savedreplies::partials/editor_dropdown_tree', ['saved_replies' => $saved_reply->saved_replies, 'parents' => array_merge($parents, [$saved_reply->id])])
	@endif
@endforeach