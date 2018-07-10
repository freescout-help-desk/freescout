<div class="customer-snippet">
	<div class="customer-photo-container">
    	<img src="/img/default-avatar.png" alt="" class="customer-photo">
    </div>
    <div class="customer-data">
		<h3>{{ $customer->getFullName() }}</h3>
		<ul class="customer-contacts">
			<li>todo</li>
		</ul>
		<div class="customer-divider"></div>
		<ul class="customer-contacts">
			@foreach ($customer->emails as $email)
	            <li><a href="#" title="{{ __('Email customer') }}"><i class="glyphicon glyphicon-envelope"></i> {{ $email->email }}</a></li>
	        @endforeach
		</ul>
	</div>
</div>