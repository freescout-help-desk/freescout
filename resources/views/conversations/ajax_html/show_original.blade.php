@php
	// If tabs ids will same, when tabs loaded second time they will not work
	$tabs_unique = time();
@endphp
<ul class="nav nav-tabs nav-tabs-no-bottom">
	<li role="presentation" class="active"><a data-toggle="tab" href="#tab_headers_{{ $tabs_unique }}">{{ __('Headers') }}</a></li>
	<li role="presentation"><a data-toggle="tab" href="#tab_body_{{ $tabs_unique }}">{{ __('Body') }}</a></li>
</ul>

<div class="tab-content">
	<div id="tab_headers_{{ $tabs_unique }}" class="tab-pane fade in active">
		<pre class="pre-wrap">{{ $thread->headers }}</pre>
	</div>
	<div id="tab_body_{{ $tabs_unique }}" class="tab-pane fade">
		<pre class="pre-wrap">{{ $thread->getBodyOriginal() }}</pre>
	</div>
</div>