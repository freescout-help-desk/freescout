@if (request()->category_id)
    @php
        $parend_category = $category->parent();
    @endphp
    @if (!empty($category))
        @if ($parend_category)
            <a href="{{ \Kb::insideWidgetUrl($decoded_mailbox_id) }}">{{ __('Home') }}</a> » <a href="{{ \Kb::insideWidgetUrl($decoded_mailbox_id, ['category_id' => $parend_category->id ]) }}">{{ $parend_category->name }}</a> » {{ $category->name }}
        @else
            <a href="{{ $home_url }}">{{ __('Home') }}</a> » {{ $category->name }}
        @endif
    @else
        <a href="javascript:kbBack();void(0);">« {{ __('Back') }}</a>
    @endif
    @if ($categories)
        <ul style="margin-top: 5px">
            @include('knowledgebase::partials/widget/categories')
        </ul>
        <hr/>
    @endif
@elseif (request()->from_search)
    <a href="javascript:kbBackSearch('{{ request()->from_search }}');void(0);">« {{ __('Back') }}</a>
@else
    <a href="javascript:kbBack();void(0);">« {{ __('Back') }}</a>
@endif