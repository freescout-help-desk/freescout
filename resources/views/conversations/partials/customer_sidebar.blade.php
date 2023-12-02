@if (!empty($customer))
    <div class="conv-customer-header"></div>
    <div class="conv-customer-block conv-sidebar-block">
        @include('customers/profile_snippet', ['customer' => $customer, 'main_email' => $conversation->customer_email ?? '', 'conversation' => $conversation ?? null])
        @if (isset($conversation))
	        <div class="dropdown customer-trigger" data-toggle="tooltip" title="{{ __("Settings") }}">
	            <a href="#" class="dropdown-toggle glyphicon glyphicon-cog" data-toggle="dropdown" ></a>
	            <ul class="dropdown-menu dropdown-menu-right" role="menu">
	                <li role="presentation"><a href="{{ route('customers.update', ['id' => $customer->id]) }}" tabindex="-1" role="menuitem">{{ __("Edit Profile") }}</a></li>
	                @if (!$conversation->isChat())
	                    <li role="presentation"><a href="{{ route('conversations.ajax_html', array_merge(['action' =>
	            'change_customer'], \Request::all(), ['conversation_id' => $conversation->id]) ) }}" data-trigger="modal" data-modal-title="{{ __("Change Customer") }}" data-modal-no-footer="true" data-modal-on-show="changeCustomerInit" tabindex="-1" role="menuitem">{{ __("Change Customer") }}</a></li>
	                @endif
	                @if (count($prev_conversations))
	                    <li role="presentation" class="col3-hidden"><a data-toggle="collapse" href=".collapse-conv-prev" tabindex="-1" role="menuitem">{{ __("Previous Conversations") }}</a></li>
	                @endif
	                {{ \Eventy::action('conversation.customer.menu', $customer, $conversation) }}
	                {{-- No need to use this --}}
	                {{ \Eventy::action('customer_profile.menu', $customer, $conversation) }}
	            </ul>
	        </div>
	    @endif
        {{--<div data-toggle="collapse" href="#collapse-conv-prev" class="customer-hist-trigger">
            <div class="glyphicon glyphicon-list-alt" data-toggle="tooltip" title="{{ __("Previous Conversations") }}"></div>
        </div>--}}
    </div>
    @if (isset($conversation) && isset($mailbox))
    	@action('conversation.before_prev_convs', $customer, $conversation, $mailbox)
    @endif
    @if (count($prev_conversations))
        @include('conversations/partials/prev_convs_short')
    @endif
    @if (isset($conversation) && isset($mailbox))
    	@action('conversation.after_prev_convs', $customer, $conversation, $mailbox)
    @endif
@endif