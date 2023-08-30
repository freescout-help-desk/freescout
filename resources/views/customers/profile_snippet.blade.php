<div class="customer-snippet">
	<div class="customer-photo-container">
    	<img src="{{ $customer->getPhotoUrl() }}" alt="" class="customer-photo">
    </div>
    <div class="customer-data">
    	@if ($customer->getFullName(true, true))
			<a href="{{ route('customers.update', ['id' => $customer->id]) }}" class="customer-name">{{ $customer->getFullName(true, true) }}</a>
		@endif
		@php
			$channels = $customer->getChannels();
		@endphp
		@if (count($channels))
			<div class="customer-tags">@foreach ($channels as $channel)<span class="fs-tag"><span class="fs-tag-name">{{ $channel->getChannelName() }}</span></span>@endforeach{{ '' }}@action('customer.tags', $customer, $conversation ?? null)</div>
		@endif
		<ul class="customer-contacts customer-section">
			@if (!empty($main_email))
		    	@foreach ($customer->emails as $email)
		    		@if ($email->email == $main_email)
		    		    <li class="customer-email">
		    		        <a href="javascript:copyToClipboard('{{ $email->email }}')" class="contact-main" data-toggle="tooltip" title="{{ __('Copy') }}">{{ $email->email }}</a>
		           	    </li>
		           	@endif
		        @endforeach
		    @endif
		    @foreach ($customer->emails as $email)
		    	@if (empty($main_email) || $email->email != $main_email)
	            	<li class="customer-email">
	                    <a href="javascript:copyToClipboard('{{ $email->email }}')" class="contact-main" data-toggle="tooltip" title="{{ __('Copy') }}">{{ $email->email }}</a>
	                </li>
	            @endif
	        @endforeach
			@foreach ($customer->getPhones() as $phone)
	            <li class="customer-phone"><a href="tel:{{ $phone['value'] }}" title="{{ __('Click to Call') }}">{{ $phone['value'] }}
                        @if (!\App\Customer::isDefaultPhoneType($phone['type']) || $customer->getPhones() > 1)<small class="text-muted">({{ \App\Customer::getPhoneTypeName($phone['type']) }})</small>@endif</a></li>
	        @endforeach
		</ul>
		<div class="customer-extra">
			@if ($customer->getSocialProfiles() || $customer->getWebsites())
				<div class="customer-social-profiles">
					@foreach ($customer->getWebsites() as $website)
			            <a href="{{ $website }}" target="_blank" title="{{ parse_url($website, PHP_URL_HOST) }}" data-toggle="tooltip" class="glyphicon glyphicon-globe"></a>
			        @endforeach
					@foreach ($customer->getSocialProfiles() as $sp)
			            <a href="{{ App\Customer::formatSocialProfile($sp)['value_url'] }}" target="_blank" data-toggle="tooltip" title="{{ App\Customer::formatSocialProfile($sp)['type_name'] }}" class="glyphicon glyphicon-user"></a>
			        @endforeach
				</div>
			@endif
			@php
				$location = array_filter([$customer->city, $customer->state, $customer->getCountryName()]);
			@endphp
			@if ($customer->company || $customer->job_title || $location || $customer->address)
				<div class="customer-section">
					@if ($customer->company)<div>{{ $customer->company }}</div>@endif
					@if ($customer->job_title)<div>{{ $customer->job_title }}</div>@endif
					@if ($location)<div>{{ implode(', ', $location) }}</div>@endif
					@if ($customer->address)<div>{{ $customer->address }}</div>@endif
				</div>
			@endif
			@if ($customer->notes)
				<div class="customer-section">
					<i>{{ $customer->notes }}</i>
				</div>
			@endif
			@action('customer.profile.extra', $customer, $conversation ?? '')
		</div>
		@action('customer.profile_data', $customer, $conversation ?? '')
	</div>
</div>
