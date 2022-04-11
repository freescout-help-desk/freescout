@extends('layouts.app')

@section('title_full', __('Satisfaction Ratings').' - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading-noborder">
        {{ __('Satisfaction Ratings') }}
    </div>

    @include('satratings::partials/tabs')

 	<div class="row-container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal margin-top" method="POST" action="">
                    {{ csrf_field() }}

                    <div class="form-group{{ $errors->has('ratings') ? ' has-error' : '' }}">
                        <label for="ratings" class="col-sm-2 control-label">{{ __('Enable Ratings') }}</label>

                        <div class="col-sm-6">
                            <div class="controls">
                                <div class="onoffswitch-wrap">
                                    <div class="onoffswitch">
                                        <input type="checkbox" name="ratings" value="1" id="ratings" class="onoffswitch-checkbox" @if (old('ratings', $mailbox->ratings))checked="checked"@endif >
                                        <label class="onoffswitch-label" for="ratings"></label>
                                    </div>
                                </div>
                            </div>
                            @include('partials/field_error', ['field'=>'ratings'])
                        </div>
                    </div>

					<div class="form-group">
                        <label for="ratings_placement" class="col-sm-2 control-label">{{ __('Placement') }}</label>

                        <div class="col-sm-6">
                            <div class="control-group">
                                <label class="radio" for="ratings_placement_{{ SatRatingsHelper::PLACEMENT_ABOVE }}">
                                    <input type="radio" name="ratings_placement" value="{{ SatRatingsHelper::PLACEMENT_ABOVE }}" id="ratings_placement_{{ SatRatingsHelper::PLACEMENT_ABOVE }}" @if ($mailbox->ratings_placement == SatRatingsHelper::PLACEMENT_ABOVE) checked="checked" @endif> {!! __("Place ratings text :%tag_begin%above:%tag_end% mailbox signature.", ['%tag_begin%' => '<strong>', '%tag_end%' => '</strong>']) !!}
                                </label>
                                <label class="radio" for="ratings_placement_{{ SatRatingsHelper::PLACEMENT_BELOW }}">
                                    <input type="radio" name="ratings_placement" value="{{ SatRatingsHelper::PLACEMENT_BELOW }}" id="ratings_placement_{{ SatRatingsHelper::PLACEMENT_BELOW }}" @if ($mailbox->ratings_placement == SatRatingsHelper::PLACEMENT_BELOW) checked="checked" @endif> {!! __("Place ratings text :%tag_begin%below:%tag_end% mailbox signature.", ['%tag_begin%' => '<strong>', '%tag_end%' => '</strong>']) !!}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ratings_text" class="col-sm-2 control-label">{{ __('Ratings Text') }}</label>

                        <div class="col-sm-9">
                            <textarea id="ratings_text" class="form-control" name="ratings_text" rows="8">{{ old('ratings_text', ($mailbox->ratings_text ?? SatRatingsHelper::DEFAULT_TEXT)) }}</textarea>
                            <textarea id="default_ratings_text" class="hidden">{{ SatRatingsHelper::DEFAULT_TEXT }}</textarea>
                            <div class="{{ $errors->has('ratings_text') ? ' has-error' : '' }}">
                                @include('partials/field_error', ['field'=>'ratings_text'])
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save') }}
                            </button>

                            <a href="#" class="btn btn-link reset-trigger">{{ __('Reset to defaults') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
  
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    initSatRatingsSettings('{{ __('Reset to default values?') }}');
@endsection