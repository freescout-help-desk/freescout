<li><a href="{{ route('crm.ajax_html', ['action' => 'delete_customer', 'param' => $customer->id ]) }}" data-trigger="modal" data-modal-title="{{ __('Delete Customer') }}" data-modal-no-footer="true" data-modal-on-show="crmInitDeleteCustomer">{{ __('Delete') }}</a></li>