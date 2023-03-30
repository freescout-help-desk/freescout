@extends('layouts.app')

@section('title_full', __('Knowledge Base').' - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading margin-bottom">
        {{ __('Knowledge Base') }}
    </div>

    <div class="col-xs-12">
        @include('knowledgebase::partials/settings_tab')

        <div class="input-group margin-top">
            <span class="input-group-addon">{{ __('Category') }}</span>
            <select class="form-control" id="kb-categories-select" data-category-id="{{ $category_id }}">
                <option value="{{ route('mailboxes.knowledgebase.articles', ['id'=>$mailbox->id, 'category_id' => 0]) }}">{{ __('All') }} ({{ \KbArticle::where('mailbox_id', $mailbox->id)->count() }})</option>
                <option value="{{ route('mailboxes.knowledgebase.articles', ['id'=>$mailbox->id, 'category_id' => -1]) }}" @if ($category_id == -1) selected @endif>{{ __('Uncategorized') }} ({{ \KbCategory::countUncategorizedArticles($mailbox->id) }})</option>
                @include('knowledgebase::partials/category_select_item', ['categories' => \KbCategory::getTree($mailbox->id), 'category_id' => $category_id])
            </select>
        </div>

        <div class="margin-top margin-bottom">
            <a href="{{ route('mailboxes.knowledgebase.new_article', ['id'=>$mailbox->id, 'category_id' => $category_id]) }}" class="btn btn-primary">
                {{ __('New Article') }}
            </a>
            @if ($articles && $category_id)
                <span class="pull-right">
                    <span class="text-help">{{ __('Sorting') }}: </span>
                    <select id="kb-category-sorting">
                        <option value="{{ \KbCategory::ARTICLES_ORDER_ALPHABETICALLY }}" @if ($category->articles_order == \KbCategory::ARTICLES_ORDER_ALPHABETICALLY) selected @endif>{{ __('A-Z') }}</option>
                        <option value="{{ \KbCategory::ARTICLES_ORDER_LAST_UPDATED }}" @if ($category->articles_order == \KbCategory::ARTICLES_ORDER_LAST_UPDATED) selected @endif>{{ __('Last updated first') }}</option>
                        <option value="{{ \KbCategory::ARTICLES_ORDER_CUSTOM }}" @if ($category->articles_order == \KbCategory::ARTICLES_ORDER_CUSTOM) selected @endif>{{ __('Custom order') }}</option>
                    </select>
                </span>
            @endif
        </div>

        @if (count($articles))
            <div class="panel-group accordion accordion-disabled" id="kb-articles-list">
                @foreach($articles as $article)
                    <div class="panel panel-default panel-sortable" data-article-id="{{ $article->id }}">
                        <div class="panel-heading">
                            <span class="handle"><i class="glyphicon glyphicon-menu-hamburger"></i></span>
                            <h4 class="panel-title">
                                <div>
                                    <span>
                                        {{--@if ($article->isPublished()) &nbsp;
                                            <a href="{{ route('mailboxes.knowledgebase.article', ['id'=>$mailbox->id, 'article_id' => $article->id]) }}" data-toggle="tooltip" title="{{ __('View') }}"><i class="glyphicon glyphicon-new-window"></i></a> 
                                        @endif
                                        &nbsp;--}}
                                        @if (!$article->isPublished())
                                            <small class="glyphicon glyphicon-eye-close text-help" data-toggle="tooltip" title="{{ __('Draft') }}"></small> 
                                        @endif
                                        <a href="{{ route('mailboxes.knowledgebase.article', ['id'=>$mailbox->id, 'article_id' => $article->id]) }}" data-toggle="tooltip" title="{{ __('Edit') }}">{{ $article->title }}</a>
                                        @foreach(\Kb::getLocales($mailbox) as $locale)
                                            @if (!$article->translatedInLocale($locale))
                                                 · <span class="text-danger">{{ strtoupper($locale) }}</span>
                                            @endif
                                        @endforeach
                                    </span>
                                </div>
                            </h4>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            @include('partials/empty', ['icon' => 'list', 'empty_text' => __('No articles found')])
        @endif
    </div>
@endsection

@section('javascript')
    @parent
    kbInitArticles();
@endsection