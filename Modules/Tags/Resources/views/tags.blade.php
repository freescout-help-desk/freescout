@extends('layouts.app')

@section('title', __('Tags'))
@section('content_class', 'content-full')

@section('content')
	<div class="container">
	    <div class="main-heading">
	    	{{ __('Tags') }}

	    	{{--<div class="in-heading">
	    		<div class="in-heading-item">
					{{ __('Mailbox') }} 
					<select class="form-control" name="mailbox">
						<option value=""></option>
						@foreach (Auth::user()->mailboxesCanView(true) as $mailbox)
							<option value="{{ $mailbox->id }}">{{ $mailbox->name }}</option>
						@endforeach
					</select>
				</div>
	    	</div>--}}
	    </div>

	    <div class="tags-list">
		    @if (count($tags))
			    @foreach($tags as $tag)
			    	<a class="btn btn-primary tag-c-{{ $tag->getColor() }}" href="{{ route('tags.ajax_html', ['action' => 'update', 'param' => $tag->id]) }}" data-trigger="modal" data-modal-title="{{ __('Edit Tag') }}" data-modal-no-footer="true" data-modal-on-show="initEditTag">
					  {{ $tag->name }} <span class="badge">{{ (int)$tag->counter }}</span>
					</a>
			    @endforeach
			@else
				@include('partials/empty')
			@endif
		</div>
	    
	</div>
@endsection