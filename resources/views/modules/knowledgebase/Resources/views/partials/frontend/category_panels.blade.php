@if (count($categories))
    @php
        $list_open = false;
    @endphp    
    @foreach($categories as $category)
        @if (!$category->expand || (empty($category->categories) && !$category->getArticlesCount(true)) || !empty($no_expand))
            @if (!$list_open)
                @php
                    $list_open = true;
                @endphp
                <div class="kb-category-panels">
            @endif
            <a href="{{ $category->urlFrontend($mailbox) }}" class="kb-category-panel">
                <div class="kb-category-panel-title">{{ $category->name }}</div>
                @if ($category->description)
                    <div class="kb-category-panel-descr">{{ $category->description }}</div>
                @endif
                <div class="kb-category-panel-info"><i class="glyphicon glyphicon-list-alt"></i> {{ $category->getArticlesCount(true) ?: count($category->categories) ?: '' }}</div>
            </a>
            @if ($loop->last && $list_open)
                @php
                    $list_open = false;
                @endphp
                </div>
            @endif
        @else
            @if ($list_open)
                @php
                    $list_open = false;
                @endphp
                </div>
            @endif
            <div class="kb-sub-heading">{{ $category->name }}</div>
            @if ($category->description)
                <div class="kb-sub-heading-descr">{{ $category->description }}</div>
            @endif
            @if (count($category->categories))
                @include('knowledgebase::partials/frontend/category_panels', ['categories' => $category->categories, 'no_expand' => true])
            @else
                <div class="margin-top">
                    @include('knowledgebase::partials/frontend/articles', ['articles' => $category->articles_published, 'category_id' => $category->id])
                </div>
            @endif
        @endif
    @endforeach
@endif