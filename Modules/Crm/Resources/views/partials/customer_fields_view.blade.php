@if (count($customer_fields))
    @foreach ($customer_fields as $customer_field)
        @if ($customer_field->display && ($customer_field->value != '' || $customer_field_link = $customer_field->getLink($customer_fields)))
            <div class="customer-section">
                {{ $customer_field->name }}: 
                @if ($customer_field->type == CustomerField::TYPE_DATE)
                    {{ App\User::dateFormat($customer_field->value, 'M j, Y', false) }}
                @elseif ($customer_field->type == CustomerField::TYPE_DROPDOWN)
                    @if (is_array($customer_field->options) && isset($customer_field->options[$customer_field->value]))
                        {{ $customer_field->options[$customer_field->value] }}
                    @endif
                @elseif ($customer_field->type == CustomerField::TYPE_LINK)
                    @if ($customer_field_link)<a href="{{ $customer_field_link }}" target="_blank"/>{{ CustomerField::shortenLink($customer_field_link) }}</a>@endif
                @else
                    {{ $customer_field->value }}
                @endif
            </div>
        @endif
    @endforeach
@endif