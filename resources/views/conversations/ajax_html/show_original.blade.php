@php
	// If tabs ids will same, when tabs loaded second time they will not work
	$tabs_unique = time();
@endphp
<ul class="nav nav-tabs nav-tabs-no-bottom">
	@if ($thread->headers)<li role="presentation" class="active"><a data-toggle="tab" href="#tab_headers_{{ $tabs_unique }}">{{ __('Headers') }}</a></li>@endif
	<li role="presentation" @if (!$thread->headers)class="active"@endif><a data-toggle="tab" href="#tab_body_{{ $tabs_unique }}">{{ __('Body') }}</a></li>
</ul>

<div class="tab-content">
	@if ($thread->headers)<div id="tab_headers_{{ $tabs_unique }}" class="tab-pane fade in active">
		<pre class="pre-wrap">{{ $thread->headers }}</pre>
	</div>@endif
	<div id="tab_body_{{ $tabs_unique }}" class="tab-pane fade @if (!$thread->headers) in active @endif">
		<pre class="pre-wrap">{{ $thread->getBodyOriginal() }}</pre>
	</div>
</div>