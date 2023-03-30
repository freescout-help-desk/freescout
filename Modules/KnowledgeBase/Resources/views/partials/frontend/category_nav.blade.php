@if (count($categories))
    @foreach ($categories as $category)
    	<div class="kb-category-nav-item">
    		@php
                $subcategories = $category->categories;
    			$collapsable_category = ($subcategories && !$category->getArticlesCount(true));
    			$has_selected_child = $category->hasChildWithId($selected_category_id);
    		@endphp
    		@if ($collapsable_category)
        		<a href="#kb-category-nav-collapse-{{ $category->id }}" data-toggle="collapse">{{ $category->name }} <span class="caret"></span></a>
        	@else
        		<a href="{{ $category->urlFrontend($mailbox) }}" class="@if ($selected_category_id == $category->id) kb-selected @endif">{{ $category->name }}</a>
        	@endif
        	@if ($category->id == $selected_category_id 
        		|| $has_selected_child
        		|| $collapsable_category
        	)
        		@if ($collapsable_category)<div id="kb-category-nav-collapse-{{ $category->id }}" @if (!$has_selected_child) class="collapse @if ($selected_category_id == $category->id) in @endif" @endif>@endif
        			@include('knowledgebase::partials/frontend/category_nav', ['categories' => $subcategories])
        		@if ($collapsable_category)</div>@endif
        	@endif
        </div>
    @endforeach
@endif