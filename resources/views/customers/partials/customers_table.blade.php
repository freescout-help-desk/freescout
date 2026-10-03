@if (count($customers))
    <div class="table-customers" data-page="{{ (int)request()->get('page', 1) }}">
        <div class="container">
            @if (\Helper::getCustomersView() == 'table')
                <table class="table table-striped table-hover customers-table margin-top">
                    <thead>
                        <tr>
                            <th class="customers-table-col-name">{{ __('Name') }}</th>
                            <th class="customers-table-col-email">{{ __('Email') }}</th>
                            <th class="customers-table-col-phone">{{ __('Phone') }}</th>
                            <th class="customers-table-col-company">{{ __('Company') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td class="customers-table-name">
                                    <a href="{{ Eventy::filter('customer.card.url', route('customers.update', ['id' => $customer->id]), $customer) }}" @action('customer.card.link', $customer) title="{{ $customer->first_name }} {{ $customer->last_name }}"><img src="{{ $customer->getPhotoUrl() }}" class="customers-table-photo" /> {{ $customer->first_name }} {{ $customer->last_name }}</a>
                                </td>
                                <td title="{{ $customer->getMainEmail() }}">{{ $customer->getMainEmail() }}</td>
                                <td title="{{ $customer->getMainPhoneNumber() }}">{{ $customer->getMainPhoneNumber() }}</td>
                                <td title="{{ $customer->company }}">{{ $customer->company }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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