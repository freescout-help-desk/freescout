@foreach($categories as $category)
    <li><a href="{{ \Kb::insideWidgetUrl($category->mailbox_id, ['category_id' => $category->id]) }}">{{ $category->name }}</a></li>
@endforeach