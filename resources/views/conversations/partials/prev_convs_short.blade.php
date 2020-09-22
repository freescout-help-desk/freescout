<div class="conv-sidebar-block">
    <div class="panel-group accordion accordion-empty">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" href=".collapse-conv-prev">{{ __("Previous Conversations") }} 
                        <b class="caret"></b>
                    </a>
                </h4>
            </div>
            <div class="collapse-conv-prev panel-collapse collapse in">
                <div class="panel-body">
                    <div class="sidebar-block-header2"><strong>{{ __("Previous Conversations") }}</strong> (<a data-toggle="collapse" href=".collapse-conv-prev">{{ __('close') }}</a>)</div>
                    <ul class="sidebar-block-list">
                        @foreach ($prev_conversations as $prev_conversation)
                            <li>
                                <a href="{{ $prev_conversation->url() }}" target="_blank" class="help-link"><i class="glyphicon @if ($prev_conversation->isPhone()) glyphicon-earphone @else glyphicon-envelope @endif"></i>{{ $prev_conversation->getSubject() }}</a>
                            </li>
                        @endforeach
                    </ul>
                    @if ($prev_conversations->hasMorePages()) 
                        <a href="{{ route('customers.conversations', ['id' => $customer->id])}}" class="sidebar-block-link link-blue">{{ __("View all :number", ['number' => $prev_conversations->total()]) }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>