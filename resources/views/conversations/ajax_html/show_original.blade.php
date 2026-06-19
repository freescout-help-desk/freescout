@php
	// If tabs ids will be same, when tabs loaded second time they will not work
	$tabs_unique = time();
@endphp
<ul class="nav nav-tabs nav-tabs-no-bottom">
	<li role="presentation" class="active"><a data-toggle="tab" href="#tab_preview_{{ $tabs_unique }}">{{ __('Preview') }}</a></li>
	<li role="presentation"><a data-toggle="tab" href="#tab_body_{{ $tabs_unique }}">{{ __('Body') }}</a></li>
	@if ($thread->headers)<li role="presentation"><a data-toggle="tab" href="#tab_headers_{{ $tabs_unique }}">{{ __('Email Headers') }}</a></li>@endif
	@if ($previous_thread)<li role="presentation"><a data-toggle="tab" href="#tab_previous_{{ $tabs_unique }}">{{ __('Previous Message') }}</a></li>@endif
</ul>

<div class="tab-content">
	<div id="tab_preview_{{ $tabs_unique }}" class="tab-pane fade in active">
		@if (!$fetched)
			<div class="alert alert-info alert-narrow margin-bottom-0">{{ __('The original message could not be loaded from mail server, below is the latest truncated copy stored in database.') }} (<a href="{{ config('app.freescout_repo') }}/wiki/FAQ#why-does-show-original-window-shows-truncated-message-without-previous-history" target="_blank">{{ __('read more') }}</a>)</div>
		@endif
		<iframe sandbox="" srcdoc="{!! safe_raw_html(str_replace('"', '&quot;', $body_preview)) !!}" frameborder="0" class="preview-iframe tab-body"></iframe>
	</div>
	<div id="tab_body_{{ $tabs_unique }}" class="tab-pane fade">
		<pre class="pre-wrap">{{ $thread->getBodyOriginal() }}</pre>
	</div>
	@if ($thread->headers)<div id="tab_headers_{{ $tabs_unique }}" class="tab-pane fade">
		<pre class="pre-wrap">{{ $thread->headers }}</pre>
	</div>@endif
	@if ($previous_thread)
	<div id="tab_previous_{{ $tabs_unique }}" class="tab-pane fade">
		<div style="color:#888;font-size:12px;padding:6px 0 8px;border-bottom:1px solid #eee;margin-bottom:10px;">
			<strong>{{ $previous_thread->type == \App\Thread::TYPE_CUSTOMER ? __('Customer') : __('Agent') }}</strong>
			&mdash;
			{{ $previous_thread->created_at ? $previous_thread->created_at->format('d/m/Y H:i') : '' }}
		</div>
		<iframe sandbox=""
			srcdoc="{!! htmlspecialchars($previous_thread->getBodyOriginal(), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') !!}"
			frameborder="0"
			class="preview-iframe tab-body"></iframe>
	</div>
	@endif
</div>