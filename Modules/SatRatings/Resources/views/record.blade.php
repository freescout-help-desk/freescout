@extends('satratings::layouts.landing')

@section('title', $trans['title'])

@section('content')

    @if (!empty($error))
        <h2>{{ $trans['title'] }}</h2>
        <p>{{ $error }}</p>
    @else
        <h1>{{ $trans['success_title'] }}</h1>

        <form class="margin-top" method="POST" action="">
        	{{ csrf_field() }}

        	<div class="btn-group level-buttons" data-toggle="buttons">
			    <label class="btn btn-default level-great @if ($rating == \SatRatingsHelper::RATING_GREAT) active @endif">
			    	<i class="level-icon"></i><br/>
			        <input type="radio" name="rating" value="{{ \SatRatingsHelper::RATING_GREAT }}"> {{ $trans['level_great'] }}<br/>
			    </label>
			    <label class="btn btn-default level-okay @if ($rating == \SatRatingsHelper::RATING_OKAY) active @endif">
			    	<i class="level-icon"></i><br/>
			        <input type="radio" name="rating" value="{{ \SatRatingsHelper::RATING_OKAY }}"> {{ $trans['level_okay'] }}<br/>
			    </label>
			    <label class="btn btn-default level-bad @if ($rating == \SatRatingsHelper::RATING_BAD) active @endif">
			    	<i class="level-icon"></i><br/>
			        <input type="radio" name="rating" value="{{ \SatRatingsHelper::RATING_BAD }}"> {{ $trans['level_bad'] }}<br/>
			    </label>
			</div>
			{{-- Robots test --}}
			<input type="text" name="first_name" id="satr-first-name">

        	<p>{{ $trans['comment'] }}</p>
        	<textarea class="form-control comment-area" name="comment" cols="30" rows="8" maxlength="500" placeholder="{{ $trans['comment_placeholder'] }}"></textarea>
        	<br/>
        	<p class="text-right comment-counter">
        		500
        	</p>
        	<p>
        		<button type="submit" class="btn btn-primary btn-lg btn-submit-feedback">
	                {{ $trans['submit'] }}
	            </button>
        	</p>
        </form>
    @endif
  
@endsection