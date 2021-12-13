@extends('layouts.app')

@section('title', ($q ? $q.' - ' : '').strip_tags(Eventy::filter('search.title', __('Search'))))
@section('body_class', 'body-search')

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    <div class="sidebar-title">
		{!! Eventy::filter('search.title', __('Search')) !!}
	</div>
    <ul class="sidebar-menu sidebar-menu-noicons">
    	@if (!$recent || (count($recent) == 1 && $recent[0] == $q))
    	@else
	        <li class="no-link"><span class="text-help">{{ __('Recent') }}</span></li>
			@foreach ($recent as $recent_query)
				@if ($recent_query != $q)
	            	<li class="menu-link menu-padded"><a href="{{ route('conversations.search', ['q' => $recent_query, 'mode' => $mode])}}">{{ $recent_query }}</a></li>
	            @endif
	        @endforeach
	    @endif
        {{--<li class="menu-padded"><a href="javascript: alert('todo: implement recent search');void(0);" class="help-link">{{ __('more') }}…</a></li>--}}
        <li class="no-link"><span class="text-help">{{ __('Filters') }}</span></li>
		@foreach ($filters_list as $filter)
            <li class="menu-link menu-padded">
            	<a href="#" data-filter="{{ $filter }}" @if (isset($filters[$filter]))class="active"@endif>{{ __($filter) }}:</a>
            </li>
        @endforeach
    </ul>
@endsection

@section('content')

	<div class="section-heading section-search">
		<form action="{{ route('conversations.search') }}">

			@if (request()->x_embed)
				<input type="hidden" name="x_embed" value="{{ request()->x_embed }}" />
			@endif

			@if (!empty($filters['custom']))
				<input type="hidden" name="f[custom]" value="{{ $filters['custom'] }}" />
			@endif

			@if ($mode != App\Conversation::SEARCH_MODE_CONV)
				<input type="hidden" name="mode" value="{{ $mode }}" />
			@endif

			<div class="row" id="search-filters">
				<div class="col-sm-6 form-group @if (isset($filters['assigned'])) active @endif" data-filter="assigned">
		            <label>{{ __('Assigned') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <select name="f[assigned]" class="form-control" @if (empty($filters['assigned'])) disabled @endif>
		            	<option value=""></option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @if (!empty($filters['assigned']) && $filters['assigned'] == $user->id)selected="selected"@endif>{{ $user->getFullName() }}</option>
                        @endforeach
                    </select>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['customer'])) active @endif" data-filter="customer">
		            <label>{{ __('Customer') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <div class="controls">
		            	<select class="form-control" name="f[customer]" id="search-filter-customer" @if (empty($filters['customer'])) disabled @endif/>
		            	 	@if (!empty($filters['customer']) && !empty($filters_data['customer']))
		            			<option value="{{ $filters_data['customer']->id }}" selected="selected">{{ $filters_data['customer']->getEmailAndName() }}</option>
		            		@endif
		            	</select>
		            </div>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['mailbox'])) active @endif" data-filter="mailbox">
		            <label>{{ __('Mailbox') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <select name="f[mailbox]" class="form-control" @if (empty($filters['mailbox'])) disabled @endif>
		            	<option value=""></option>
                        @foreach ($mailboxes as $mailbox)
                            <option value="{{ $mailbox->id }}" @if (!empty($filters['mailbox']) && $filters['mailbox'] == $mailbox->id)selected="selected"@endif>{{ $mailbox->name }}</option>
                        @endforeach
                    </select>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['status'])) active @endif" data-filter="status">
		            <label>{{ __('Status') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <select name="f[status][]" class="form-control filter-multiple" multiple @if (empty($filters['status'])) disabled @endif>
		            	{{--<option value=""></option>--}}
                        @foreach (App\Conversation::$statuses as $status_id => $dummy)
                            <option value="{{ $status_id }}" @if (!empty($filters['status']) && in_array($status_id, $filters['status']))selected="selected"@endif>{{ App\Conversation::statusCodeToName($status_id) }}</option>
                        @endforeach
                    </select>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['state'])) active @endif" data-filter="state">
		            <label>{{ __('State') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <select name="f[state][]" class="form-control filter-multiple" multiple @if (empty($filters['state'])) disabled @endif>
		            	{{--<option value=""></option>--}}
                        @foreach (App\Conversation::$states as $state_id => $dummy)
                            <option value="{{ $state_id }}" @if (!empty($filters['state']) && in_array($state_id, $filters['state']))selected="selected"@endif>{{ App\Conversation::stateCodeToName($state_id) }}</option>
                        @endforeach
                    </select>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['subject'])) active @endif" data-filter="subject">
		            <label>{{ __('Subject') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <input type="text" name="f[subject]" value="{{ $filters['subject'] ?? ''}}" class="form-control" @if (empty($filters['subject'])) disabled @endif>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['attachments'])) active @endif" data-filter="attachments">
		            <label>{{ __('Attachments') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <select name="f[attachments]" class="form-control" @if (empty($filters['attachments'])) disabled @endif>
		            	<option value=""></option>
                        <option value="yes" @if (!empty($filters['attachments']) && $filters['attachments'] == 'yes')selected="selected"@endif>{{ __('Yes') }}</option>
                        <option value="no" @if (!empty($filters['attachments']) && $filters['attachments'] == 'no')selected="selected"@endif>{{ __('No') }}</option>
                    </select>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['type'])) active @endif" data-filter="type">
		            <label>{{ __('Type') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <select name="f[type]" class="form-control" @if (empty($filters['type'])) disabled @endif>
		            	<option value=""></option>
                        @foreach (App\Conversation::$types as $type_id => $dummy)
                            <option value="{{ $type_id }}" @if (!empty($filters['type']) && $filters['type'] == $type_id)selected="selected"@endif>{{ App\Conversation::typeToName($type_id) }}</option>
                        @endforeach
                    </select>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['body'])) active @endif" data-filter="body">
		            <label>{{ __('Body') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <input type="text" name="f[body]" value="{{ $filters['body'] ?? ''}}" class="form-control" @if (empty($filters['body'])) disabled @endif>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['number'])) active @endif" data-filter="number">
		            <label>{{ __('Number') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <input type="text" name="f[number]" value="{{ $filters['number'] ?? ''}}" class="form-control" @if (empty($filters['number'])) disabled @endif>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['following'])) active @endif" data-filter="following">
		            <label>{{ __('Following') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <select name="f[following]" class="form-control" @if (empty($filters['following'])) disabled @endif>
		            	<option value=""></option>
                        <option value="yes" @if (!empty($filters['following']) && $filters['following'] == 'yes')selected="selected"@endif>{{ __('Yes') }}</option>
                    </select>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['id'])) active @endif" data-filter="id">
		            <label>{{ __('ID') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <input type="text" name="f[id]" value="{{ $filters['id'] ?? ''}}" class="form-control" @if (empty($filters['id'])) disabled @endif>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['after'])) active @endif" data-filter="after">
		            <label>{{ __('After') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <input type="text" name="f[after]" value="{{ $filters['after'] ?? ''}}" class="form-control input-date" @if (empty($filters['after'])) disabled @endif>
		        </div>
				<div class="col-sm-6 form-group @if (isset($filters['before'])) active @endif" data-filter="before">
		            <label>{{ __('Before') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>
		            <input type="text" name="f[before]" value="{{ $filters['before'] ?? ''}}" class="form-control input-date" @if (empty($filters['before'])) disabled @endif>
		        </div>
		        @action('search.display_filters', $filters, $filters_data, $mode)
		    </div>

	        <div class="input-group input-group-lg1">
	            <input type="text" class="form-control" name="q" value="{{ $q }}">
	            <span class="input-group-btn">
	                <button class="btn btn-default" type="submit">{{ __('Search') }}</button>
	            </span>
	        </div>
	    </form>
	</div>

	<div class="search-results">
		<ul class="nav nav-tabs nav-tabs-main margin-top">
			@if (Eventy::filter('search.is_tab_visible', true, App\Conversation::SEARCH_MODE_CONV))
		    	<li @if ($mode == App\Conversation::SEARCH_MODE_CONV)class="active search-tab-conv"@endif><a href="{{ \Helper::fixProtocol(request()->fullUrlWithQuery(['mode' => App\Conversation::SEARCH_MODE_CONV])) }}">{{ __('Conversations') }} <b>({{ $conversations->total() }})</b></a></li>
		    @endif
		    <li @if ($mode == App\Conversation::SEARCH_MODE_CUSTOMERS)class="active"@endif><a href="{{ \Helper::fixProtocol(request()->fullUrlWithQuery(['mode' => App\Conversation::SEARCH_MODE_CUSTOMERS])) }}">{{ __('Customers') }} <b>({{ $customers->total() }})</b></a></li>
		</ul>
		@if ($mode == App\Conversation::SEARCH_MODE_CONV)
	    	@include('conversations/conversations_table', ['params' => ['target_blank' => true, 'show_mailbox' => (count(Auth::user()->mailboxesCanView(true)) > 1)]])
	    @else
	    	@include('customers/partials/customers_table')
	    @endif
	</div>
@endsection

@include('partials/include_datepicker')

@section('javascript')
    @parent
    searchInit();
@endsection
