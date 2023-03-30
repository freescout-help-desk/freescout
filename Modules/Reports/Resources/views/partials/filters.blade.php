<div class="rpt-filters">

	{{--<div class="rpt-views-trigger">
		@include('reports::partials/views')
	</div>--}}

	<div class="rpt-filter">
		{{ __('Type') }} 
		<select class="form-control" name="type">
			<option value=""></option>
			<option value="{{ App\Conversation::TYPE_EMAIL }}">{{ __('Email') }}</option>
			<option value="{{ App\Conversation::TYPE_CHAT }}">{{ __('Chat') }}</option>
			<option value="{{ App\Conversation::TYPE_PHONE }}">{{ __('Phone') }}</option>
		</select>
	</div>
	<div class="rpt-filter">
		{{ __('Mailbox') }} 
		<select class="form-control" name="mailbox">
			<option value=""></option>
			@foreach (Auth::user()->mailboxesCanView(true) as $mailbox)
				<option value="{{ $mailbox->id }}">{{ $mailbox->name }}</option>
			@endforeach
		</select>
	</div>
	@if (\Module::isActive('tags'))
		<div class="rpt-filter">
    		{{ __('Tag') }} 
    		<select class="form-control" name="tag">
    			<option value=""></option>
    			@foreach (Modules\Tags\Entities\Tag::orderBy('name')->get() as $tag)
    				<option value="{{ $tag->id }}">{{ $tag->name }}</option>
    			@endforeach
    		</select>
    	</div>
    @endif

	<div class="rpt-filter">
		<nobr><input type="text" name="from" class="form-control rpt-filter-date" value="{{ $filters['from'] }}" />-<input type="text" name="to" class="form-control rpt-filter-date" value="{{ $filters['to'] }}" /></nobr>
		{{--<button class="btn btn-primary" name="period">Oct 1, 2017 - Nov 1, 2017 <span class="caret"></span></button>--}}
	</div>
	{{--<div class="rpt-filter" data-toggle="tooltip" title="{{ __('Export') }}">
		<button class="btn btn-primary"><i class="glyphicon glyphicon-download-alt"></i></button>
	</div>--}}

	<div class="rpt-filter" data-toggle="tooltip" title="{{ __('Refresh') }}">
		<button class="btn btn-primary" id="rpt-btn-loader"><i class="glyphicon glyphicon-refresh"></i></button>
	</div>

	@action('reports.filters_button_append')
</div>

@php
	$custom_fields = Reports::getCustomFieldFilters();
@endphp
@if (count($custom_fields))
    <div class="rpt-filters">
        @foreach($custom_fields as $custom_field)
            <div class="rpt-filter rpt-cf-mailbox rpt-cf-mailbox-{{ $custom_field->mailbox_id }} hidden">
                <span class="rpt-cf-name">{{ $custom_field->name }}</span>

                @if ($custom_field->type == CustomField::TYPE_DROPDOWN)
                    <select name="custom_field[{{ $custom_field->id }}]" class="form-control">
                        <option value=""></option>
                        @if (is_array($custom_field->options))
                            @foreach ($custom_field->options as $option_id => $option_name)
                                <option value="{{ $option_id }}">{{ $option_name }}</option>
                            @endforeach
                        @endif
                    </select>
                @else
                    <input name="custom_field[{{ $custom_field->id }}]" value="{{ $filters[$custom_field->name] ?? '' }}" class="form-control @if ($custom_field->type == CustomField::TYPE_DATE) input-date @endif" />
                @endif
            </div>
        @endforeach
    </div>
@endif