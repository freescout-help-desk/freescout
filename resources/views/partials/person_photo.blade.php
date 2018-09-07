@if (!empty($person->role))
	@if ($person->photo_url)
        <img class="person-photo" src="{{ $person->getPhotoUrl() }}" />
    @else
        <i class="person-photo person-photo-auto" data-initial="{{ strtoupper($person->first_name[0]) }}{{ strtoupper($person->last_name[0]) }}"></i>
    @endif
@else
	<img class="person-photo" src="{{ $person->getPhotoUrl() }}" alt="">
@endif