@if (!empty($person))
	@if (!empty($person->role))
		@if ($person->photo_url)
	        <img class="person-photo" src="{{ $person->getPhotoUrl() }}" />@else
	        <i class="person-photo person-photo-auto" data-initial="{{ strtoupper(mb_substr($person->first_name, 0, 1)) }}{{ strtoupper(mb_substr($person->last_name, 0, 1)) }}"></i>@endif{{ '' }}@else<img class="person-photo" src="{{ $person->getPhotoUrl() }}" alt="">@endif{{ '' }}@endif