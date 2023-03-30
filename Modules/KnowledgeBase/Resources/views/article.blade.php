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

        <form class="form-horizontal margin-top margin-bottom-30 kb-article-container" method="POST" action="" autocomplete="off">
            {{ csrf_field() }}

            <input type="hidden" name="mailbox_id" value="{{ $mailbox->id }}" />

            <div class="margin-top margin-bottom-10 kb-article-toolbar">
                <a href="{{ route('mailboxes.knowledgebase.articles', ['id'=>$mailbox->id, 'category_id' => $category_id]) }}">« {{ __('Articles') }}</a>
                
                <button class="btn @if ($article->status == \KbArticle::STATUS_DRAFT){{ 'btn-default' }}@else{{ 'btn-primary' }}@endif kb-article-right" name="action" value="save">@if ($mode == 'create'){{ __('Save') }}@else{{ __('Save') }}@endif</button>
                {{--<button class="btn btn-default kb-article-right" name="action" value="preview" target="_blank"><small class="glyphicon glyphicon-eye-open"></small> {{ __('Preview') }}</button>--}}
                @if ($article->status == \KbArticle::STATUS_DRAFT)
                    <button class="btn btn-primary kb-article-right" name="action" value="publish">{{ __('Publish') }}</button>
                @else
                    <button class="btn btn-default kb-article-right" name="action" value="unpublish">{{ __('Unpublish') }}</button>
                @endif
                <span class="kb-article-status kb-article-right">@if ($article->status == \KbArticle::STATUS_DRAFT) <span class="text-help">{{ __('Status') }}:</span> <strong>{{ $article->getStatusName() }}</strong>@else <a href="{{ $article->urlFrontend($mailbox) }}" target="_blank"><small class="glyphicon glyphicon-new-window"></small> {{ __('View') }}</a>@endif&nbsp;</span>
            </div>

            @php
                $categories_tree = \KbCategory::getTree($mailbox->id);
            @endphp
            @if (count($categories_tree))
                <div class="margin-bottom-10">
                    <select class="form-control" id="kb-article-categories" name="categories[]" multiple>
                        <option value=""></option>
                        @include('knowledgebase::partials/category_select2_item', ['categories' => $categories_tree, 'selected' => $categories])
                    </select>
                </div>
            @endif

            @php
                \Kb::$use_primary_if_empty = false;
            @endphp

            @if (count(\Kb::getLocales($mailbox)) && $mode != 'create')
                <div class="margin-bottom-10">
                    <ul class="nav nav-tabs">
                        @foreach (\Kb::getLocales($mailbox) as $locale)
                            <li @if ($locale == \Kb::backendLocale($mailbox))class="active"@endif><a href="{{ route('mailboxes.knowledgebase.article', ['mailbox_id'=>$mailbox->id, 'article_id'=>$article->id, 'kb_locale'=>$locale]) }}"><span @if (!$article->translatedInLocale($locale)) class="text-danger" @endif>[{{ strtoupper($locale) }}] {{ \Helper::getLocaleData($locale)['name'] }}</span></a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @php
                $show_primary_values = \Kb::isMultilingual($mailbox) 
                    && request()->kb_locale
                    && request()->kb_locale != \Kb::defaultLocale($mailbox)
                    && $mode != 'create';
            @endphp

            <div class="input-group input-group-lg @if (!$show_primary_values) margin-bottom-5 @endif">
                <input type="text" name="title" value="{{ $article->title }}" class="form-control" placeholder="{{ __('Title') }}" required maxlength="255" />
                <span class="input-group-addon" id="kb-article-delete" data-article_id="{{ $article->id }}"><small class="glyphicon glyphicon-trash"></small></span>
            </div>
            @if ($show_primary_values)
                <input type="text" value="{{ $article->getAttributeInLocale('title', \Kb::defaultLocale($mailbox)) }}" class="form-control margin-bottom-5 disabled" readonly />
            @endif
            <input type="text" name="slug" value="{{ $article->slug }}" class="form-control margin-bottom-10" placeholder="{{ __('URL Slug') }}" maxlength="120" />

            <textarea name="text" class="form-control" id="kb-article-text">{!! htmlspecialchars($article->text ?? '') !!}</textarea>
            @if ($show_primary_values)
                <textarea class="form-control disabled" id="kb-article-text-primary" readonly>{{ $article->getAttributeInLocale('text', \Kb::defaultLocale($mailbox)) }}</textarea>
            @endif
        </form>
    </div>
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    kbInitArticle("  {{ __('Category') }}", "{{ __('Are you sure you want to delete this article?') }}", "{{ route('mailboxes.knowledgebase.articles', ['id'=>$mailbox->id]) }}");
@endsection