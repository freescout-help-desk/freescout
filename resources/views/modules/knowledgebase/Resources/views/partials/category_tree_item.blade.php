@if (count($categories))

    <div class="panel-group accordion kb-category-tree panel-tree @if ($categories[0]->kb_category_id) panel-nested @endif">
        @foreach ($categories as $category)
            <div class="panel panel-default panel-sortable" data-category-id="{{ $category->id }}">
                <div class="panel-heading">
                    <span class="handle"><i class="glyphicon glyphicon-menu-hamburger"></i></span>
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion" href="#collapse-{{ $category->id }}">
                            <span><small class="text-help">{{ $category->getArticlesCount() }}</small> &nbsp;@if ($category->visibility == \KbCategory::VISIBILITY_PRIVATE)<small class="glyphicon glyphicon-user text-help" data-toggle="tooltip" title="{{ __('Visible to support agents only') }}"></small> @endif{{ '' }}@if ($category->expand)<small class="glyphicon glyphicon-resize-vertical text-help" data-toggle="tooltip" title="{{ __('Expand Category') }}"></small> @endif{{ $category->name }}
                                @foreach(\Kb::getLocales($mailbox) as $locale)
                                    @if (!$category->translatedInLocale($locale))
                                         · <span class="text-danger">{{ strtoupper($locale) }}</span>
                                    @endif
                                @endforeach
                            </span>
                        </a>
                    </h4>
                </div>
                <div id="collapse-{{ $category->id }}" class="panel-collapse collapse">
                    <div class="panel-body">
                        <form class="form-horizontal" method="POST" action="">

                            @include('knowledgebase::partials/category_form', ['mode' => 'update'])

                            <div class="form-group margin-top margin-bottom-10">
                                <div class="col-sm-10 col-sm-offset-2">
                                    <button class="btn btn-primary">{{ __('Save') }}</button> 
                                    <a href="#" class="btn btn-link text-danger kb-category-delete" data-loading-text="{{ __('Deleting') }}…" data-category_id="{{ $category->id }}">{{ __('Delete') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @if (!empty($category->categories))
                    @include('knowledgebase::partials/category_tree_item', ['categories' => $category->categories])
                @endif
            </div>
        @endforeach
    </div>

@endif