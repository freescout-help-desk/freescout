<ul class="hidden" id="sr-dropdown-list">
	@include('savedreplies::partials/editor_dropdown_tree', ['saved_replies' => \SavedReply::listToTree($saved_replies)])
	<li class="divider"></li>
	<li><a href="#" data-value="">{{ __('Save This Reply') }}…</a></li>
</ul>