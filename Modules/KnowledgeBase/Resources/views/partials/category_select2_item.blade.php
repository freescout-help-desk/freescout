@php
    if (empty($level)) {
        $level = 0;
    }
@endphp
@if (count($categories))
    @foreach ($categories as $category)
        <option value="{{ $category->id }}" @if (in_array($category->id, $selected)) selected="selected" @endif>@for ($i = 0; $i < $level; $i++)– @endfor {{ $category->name }} ({{ $category->getArticlesCount() }})</option>
        @if (!empty($category->categories))
            @include('knowledgebase::partials/category_select2_item', ['categories' => $category->categories, 'level' => ($level+1), 'selected' => $selected])
        @endif
    @endforeach
@endif