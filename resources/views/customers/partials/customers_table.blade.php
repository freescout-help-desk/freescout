@if (count($customers))
    <div class="table-customers" data-page="{{ (int)request()->get('page', 1) }}">
        <div class="container">
            @if (\Helper::getCustomersView() == 'table')
                <div class="customers-list margin-top" role="list">
                    <div class="customers-list-head" aria-hidden="true">
                        <div class="customers-list-cell customers-list-col-name">{{ __('Name') }}</div>
                        <div class="customers-list-cell customers-list-col-email">{{ __('Email') }}</div>
                        <div class="customers-list-cell customers-list-col-phone">{{ __('Phone') }}</div>
                        <div class="customers-list-cell customers-list-col-company">{{ __('Company') }}</div>
                    </div>
                    @foreach ($customers as $customer)
                        <a href="{{ Eventy::filter('customer.card.url', route('customers.update', ['id' => $customer->id]), $customer) }}" class="customers-list-row" role="listitem" @action('customer.card.link', $customer) title="{{ $customer->first_name }} {{ $customer->last_name }}">
                            <div class="customers-list-cell customers-list-col-name">
                                <img src="{{ $customer->getPhotoUrl() }}" class="customers-list-photo" alt="" />
                                <span class="customers-list-name">{{ $customer->first_name }} {{ $customer->last_name }}</span>
                            </div>
                            <div class="customers-list-cell customers-list-col-email" title="{{ $customer->getMainEmail() }}">{{ $customer->getMainEmail() }}</div>
                            <div class="customers-list-cell customers-list-col-phone" title="{{ $customer->getMainPhoneNumber() }}">{{ $customer->getMainPhoneNumber() }}</div>
                            <div class="customers-list-cell customers-list-col-company" title="{{ $customer->company }}">
                                @if ($customer->company)
                                    <span class="customers-list-company">{{ $customer->company }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="card-list margin-top">
                    @foreach ($customers as $customer)
                        <a href="{{ Eventy::filter('customer.card.url', route('customers.update', ['id' => $customer->id]), $customer) }}" class="card hover-shade" @action('customer.card.link', $customer) >
                            <img src="{{ $customer->getPhotoUrl() }}" />
                            <h4 title="{{ $customer->first_name }} {{ $customer->last_name }}">{{ $customer->first_name }} {{ $customer->last_name }}</h4>
                            <p class="text-truncate"><small>{{ $customer->getEmailOrPhone() }}</small></p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($customers->lastPage() > 1)
            <div class="customers-pager">
                {{ $customers->links('conversations/conversations_pagination') }}
            </div>
        @endif
    </div>
@else
    @include('partials/empty', ['empty_text' => __('No customers found')])
@endif