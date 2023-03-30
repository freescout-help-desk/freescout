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

        <button type="button" class="btn btn-primary margin-top margin-bottom" data-toggle="collapse" data-target="#kb-new-category">
            {{ __('New Category') }}
        </button>

        <div id="kb-new-category" class="collapse margin-top">
            <form class="form-horizontal" method="POST" action="" autocomplete="off">
                @include('knowledgebase::partials/category_form', ['mode' => 'create'])
                <div class="form-group">
                    <div class="col-sm-6 col-sm-offset-2">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Save') }}
                        </button> 
                        <a href="#" class="btn btn-link" data-toggle="collapse" data-target="#kb-new-category">{{ __('Cancel') }}</a>
                    </div>
                </div>
            </form>
            <hr/>
        </div>

        @include('knowledgebase::partials/category_tree_item', ['categories' => \KbCategory::getTree($mailbox->id)])
    </div>
@endsection

@section('javascript')
    @parent
    kbInitCategories("{{ __('Are you sure you want to delete this category?') }}");
@endsection