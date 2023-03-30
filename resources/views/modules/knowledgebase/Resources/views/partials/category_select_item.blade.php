@php
    if (empty($level)) {
        $level = 0;
    }
@endphp
@if (count($categories))
    @foreach ($categories as $category)
        <option value="{{ route('mailboxes.knowledgebase.articles', ['id'=>$mailbox->id, 'category_id' => $category->id, 't' => time()]) }}" @if ($category_id == $category->id) selected @endif>@for ($i = 0; $i < $level; $i++)– @endfor {{ $category->name }} ({{ $category->getArticlesCount() }})</option>
        @include('knowledgebase::partials/category_select_item', ['categories' => $category->categories, 'level' => ($level+1), 'category_id' => $category_id])
    @endforeach
@endif