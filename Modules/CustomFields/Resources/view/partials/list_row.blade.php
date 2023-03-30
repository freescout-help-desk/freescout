<tr class="cf-conv-row conv-row @if ($conversation->isActive()) conv-active @endif">
    <td colspan="{{ $col_counter }}">
        <div class="cf-row-wrapper @if (in_array('cb', $columns)) cf-has-cb @endif @if (in_array('customer', $columns)) cf-has-customer @endif">
            @foreach ($conversation->custom_fields as $custom_field)
                <div class="cf-row-field">
                    @if ($custom_field->value != '')
                        <span  data-toggle="tooltip" title="{{ $custom_field->name }}"><i class="glyphicon glyphicon-list"></i> <i>{{ $custom_field->getAsText() }}</i></span>
                    @else
                        <span  data-toggle="tooltip" title="{{ $custom_field->name }}">-</span>
                    @endif
                </div>
            @endforeach
        </div>
    </td>
</tr>