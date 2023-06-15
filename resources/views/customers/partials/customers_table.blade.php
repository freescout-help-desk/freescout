@if (count($customers))
    <div class="table-customers" data-page="{{ (int)request()->get('page', 1) }}">
        <div class="container">
            <div class="card-list margin-top row">
                @foreach ($customers as $customer)
                    <a href="{{ Eventy::filter('customer.card.url', route('customers.update', ['id' => $customer->id]), $customer) }}" class="card hover-shade  col-lg-3 col-md-3 col-sm-5 col-12" @action('customer.card.link', $customer) >
                        <img src="{{ $customer->getPhotoUrl() }}" />
                        <h4>{{ $customer->first_name }} {{ $customer->last_name }}</h4>
                        <p class="text-truncate"><small>{{ $customer->getEmailOrPhone() }}</small></p>
                    </a>
                @endforeach
            </div>
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
