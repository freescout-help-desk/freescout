@php
	// If tabs ids will be same, when tabs loaded second time they will not work
	$tabs_unique = time();
@endphp
<ul class="nav nav-tabs nav-tabs-no-bottom">
	<li role="presentation" class="active"><a data-toggle="tab" href="#tab_preview_{{ $tabs_unique }}">{{ __('Preview') }}</a></li>
	<li role="presentation"><a data-toggle="tab" href="#tab_body_{{ $tabs_unique }}">{{ __('Body') }}</a></li>
	@if ($thread->headers)<li role="presentation"><a data-toggle="tab" href="#tab_headers_{{ $tabs_unique }}">{{ __('Headers') }}</a></li>@endif
</ul>

<div class="tab-content">
	<div id="tab_preview_{{ $tabs_unique }}" class="tab-pane fade in active">
		<iframe sandbox="" srcdoc="{!! str_replace('"', '&quot;', $body_preview) !!}" frameborder="0" class="preview-iframe tab-body"></iframe>
	</div>
	<div id="tab_body_{{ $tabs_unique }}" class="tab-pane fade">
		<pre class="pre-wrap">{{ $thread->getBodyOriginal() }}</pre>
	</div>
	@if ($thread->headers)<div id="tab_headers_{{ $tabs_unique }}" class="tab-pane fade">
		<pre class="pre-wrap">{{ $thread->headers }}</pre>
	</div>@endif
</div>