<div class="conv-customer-hist conv-sidebar-block @if (!empty($mobile)) prev-convs-mobile @else prev-convs-full @endif">
    <div class="panel-group accordion accordion-empty">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" href=".collapse-conv-prev">{{ __("Previous Conversations") }} 
                        <b class="caret"></b>
                    </a>
                </h4>
            </div>
            <div class="collapse-conv-prev panel-collapse collapse @if (!empty($in)) in @endif">
                <div class="panel-body">
                    <div class="prev-convs-header2"><strong>{{ __("Previous Conversations") }}</strong> (<a data-toggle="collapse" href=".collapse-conv-prev">{{ __('close') }}</a>)</div>
                    <ul class="prev-convs">
                        @foreach ($prev_conversations as $prev_conversation)
                            <li>
                                <a href="{{ $prev_conversation->url() }}" taret="_blank" class="help-link"><i class="glyphicon glyphicon-envelope"></i>{{ $prev_conversation->subject }}</a>
                            </li>
                        @endforeach
                    </ul>
                    @if ($prev_conversations->hasMorePages()) 
                        <a href="{{ route('customers.conversations', ['id' => $customer->id])}}" class="link-prev-convs link-blue">{{ __("View all :number", ['number' => $prev_conversations->total()]) }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>