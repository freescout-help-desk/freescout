@extends('knowledgebase::layouts.widget_form')

@section('title', __('Help'))

@section('body_attrs')@parent data-mailbox_id_encoded="{{ request()->mailbox_id }}"@endsection

@section('content')
	
    <form method="post" action="{{ $form_action ?? '' }}" id="kb-ticket-form" autocomplete="off" @if (!empty($contact_form_url)) class="kb-has-contact" @endif>
 
        {{ csrf_field() }}

        <div class="form-group" style="background-color: {{ Request::get('color') }};" id="kb-search">
        	<div class="input-group">
				<span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
				<input type="text" name="q" value="{{ request()->q }}" class="form-control" placeholder="{{ __('How can we help?') }}" />
			</div>
        </div>

        @if (request()->q && Request::isMethod('post'))
            @if ($results)
                <div id="kb-results">
                    <div class="kb-content">
                        <a href="{{ $home_url }}">« {{ __('Home') }}</a>
                        <ul class="kb-articles">
                            @foreach($results as $article)
                                <li><i class="glyphicon glyphicon-list-alt"></i>&nbsp; <a href="{{ $article['url'] }}">{{ $article['title'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    {{--@if ($categories)
                        <hr/>
                        <ul>
                            @include('knowledgebase::partials/widget/categories')
                        </ul>
                    @endif--}}
                </div>
            @else
                <div class="kb-middle">
                    <p>
                        <strong>{{ __('Nothing found') }}</strong><br/>
                        {{ __('Try seaching for something else.') }}
                    </p>
                </div>
                @if ($categories)
                    <div id="kb-results">
                        <br/>
                        <ul>
                            @include('knowledgebase::partials/widget/categories')
                        </ul>
                    </div>
                @endif
            @endif
        @else
            @if (!empty($article))
                <div id="kb-results">
                    <div class="kb-content">
                        
                        @include('knowledgebase::partials/widget/categories_breadcrumbs')

                        <h3 class="kb-article-title">{{ $article->title }}</h3>
                        <div class="kb-article-text">
                            {!! $article->text !!}
                        </div>
                    </div>
                </div>
            @elseif (!empty($category))
                <div id="kb-results">
                    <div class="kb-content">
                        @include('knowledgebase::partials/widget/categories_breadcrumbs')
                        
                        @if ($results)
                            <ul class="kb-articles">
                                @foreach($results as $article)
                                    <li><i class="glyphicon glyphicon-list-alt"></i>&nbsp; <a href="{{ $article['url'] }}">{{ $article['title'] }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @else
                @if (empty($categories))
                    <div class="kb-middle">
                        <i class="glyphicon glyphicon-question-sign"></i>
                    </div>
                @else
                    @if ($categories)
                        <div id="kb-results">
                            <ul>
                                @include('knowledgebase::partials/widget/categories')
                            </ul>
                        </div>
                    @endif
                @endif
            @endif
        @endif

        <div id="kb-submit-form-bottom">
            @if ($contact_form_url)
                <div class="form-group">
                    <a href="{{ $contact_form_url }}" class="btn btn-block btn-primary btn-md kb-btn-ticket-submit" style="background-color: {{ Request::get('color') }}; border-color: {{ Request::get('color') }}">{{ __('Contact us') }}</a>
                </div>
            @endif
            <p id="kb-powered">@filter('knowledgebase.powered_by', 'Powered by <a href="https://freescout.net" target="_blank" title="Free open source helpdesk &amp; shared mailbox">FreeScout</a>')</p>
        </div>

    </form>

@endsection