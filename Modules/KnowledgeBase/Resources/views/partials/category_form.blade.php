@php
    if (empty($mode)) {
        $mode = 'update';
    }
    if (empty($category)) {
        $category = new \KbCategory();
    }
@endphp

{{ csrf_field() }}

<input type="hidden" name="action" value="{{ $mode }}" />
<input type="hidden" name="category_id" value="{{ $category->id }}" />
<input type="hidden" name="mailbox_id" value="{{ $mailbox->id }}" />

@if ($mode == 'update')
    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('Articles') }}</label>

        <div class="col-sm-10">
            <label class="control-label text-help">
                <strong class="text-help text-large">{{ $category->getArticlesCount() }}</strong> &nbsp;&nbsp;<a href="{{ route('mailboxes.knowledgebase.articles', ['id'=>$mailbox->id, 'category_id' => $category->id]) }}" ><i class="glyphicon glyphicon-list-alt"></i> {{ __('Category Articles') }}</a> &nbsp; <a href="{{ $category->urlFrontend($mailbox) }}" target="_blank"><i class="glyphicon glyphicon-new-window"></i> {{ __('View Category') }}</a>
            </label>
        </div>
    </div>
@endif

@if (count(\Kb::getLocales($mailbox)) && $mode == 'update')
    <div class="col-sm-offset-2 col-sm-10 margin-bottom-10">
        <ul class="nav nav-tabs nav-tabs-main">
            @foreach (\Kb::getLocales($mailbox) as $i => $locale)
                <li @if ($i == 0)class="active"@endif><a href="#kb-category-{{ $category->id }}locale-{{ $locale }}" data-toggle="tab"><span @if (!$category->translatedInLocale($locale)) class="text-danger" @endif>[{{ strtoupper($locale) }}] {{ \Helper::getLocaleData($locale)['name'] }}</span></a></li>
            @endforeach
        </ul>
    </div>
    <div class="tab-content">
        @foreach (\Kb::getLocales($mailbox) as $i => $locale)
            <div id="kb-category-{{ $category->id }}locale-{{ $locale }}" class="tab-pane @if ($i == 0) active @endif">
                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __('Name') }}</label>

                    <div class="col-sm-10">
                        <input class="form-control" name="name[{{ $locale }}]" value="{{ $category->getAttributeInLocale('name', $locale) }}" maxlength="191" @if ($i == 0) required @endif />
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __('Description') }}</label>

                    <div class="col-sm-10">
                        <input class="form-control" name="description[{{ $locale }}]" value="{{ $category->getAttributeInLocale('description', $locale) }}" maxlength="191" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('Name') }}</label>

        <div class="col-sm-10">
            <input class="form-control" name="name" value="{{ $category->name }}" maxlength="191" required />
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('Description') }}</label>

        <div class="col-sm-10">
            <input class="form-control" name="description" value="{{ $category->description }}" maxlength="191" />
        </div>
    </div>
@endif

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Parent Category') }}</label>

    <div class="col-sm-10">
        <select name="kb_category_id" class="form-control" @if (!\KbCategory::getList($mailbox->id) || (count(\KbCategory::getList($mailbox->id)) == 1 && $mode == 'update')) disabled @endif>
            <option value=""></option>
            @foreach (\KbCategory::getTreeAsList($mailbox->id) as $list_category)
                @if ($category->id != $list_category->id)
                    <option value="{{ $list_category->id }}" @if ($list_category->id == $category->kb_category_id) selected @endif>{{ $list_category->name }}</option>
                @endif
            @endforeach
        </select>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Visibility') }}</label>

    <div class="col-sm-10">
        <select name="visibility" class="form-control">
            <option value="{{ \KbCategory::VISIBILITY_PUBLIC }}" @if ($category->visibility == \KbCategory::VISIBILITY_PUBLIC) selected @endif>{{ __('Visible to all') }}</option>
            <option value="{{ \KbCategory::VISIBILITY_PRIVATE }}" @if ($category->visibility == \KbCategory::VISIBILITY_PRIVATE) selected @endif>{{ __('Visible to support agents only') }}</option>
        </select>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Articles Sort Order') }}</label>

    <div class="col-sm-10">
        <select name="articles_order" class="form-control">
            <option value="{{ \KbCategory::ARTICLES_ORDER_ALPHABETICALLY }}" @if ($category->articles_order == \KbCategory::ARTICLES_ORDER_ALPHABETICALLY) selected @endif>{{ __('A-Z') }}</option>
            <option value="{{ \KbCategory::ARTICLES_ORDER_LAST_UPDATED }}" @if ($category->articles_order == \KbCategory::ARTICLES_ORDER_LAST_UPDATED) selected @endif>{{ __('Last updated first') }}</option>
            <option value="{{ \KbCategory::ARTICLES_ORDER_CUSTOM }}" @if ($category->articles_order == \KbCategory::ARTICLES_ORDER_CUSTOM) selected @endif>{{ __('Custom order') }}</option>
        </select>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Expand on Homepage') }}</label>

    <div class="col-sm-10">
        <div class="controls">
            <div class="onoffswitch-wrap">
                <div class="onoffswitch">
                    <input type="checkbox" name="expand" value="1" id="expand_{{ $category->id }}" class="onoffswitch-checkbox" @if (!empty($category->expand))checked="checked"@endif>
                    <label class="onoffswitch-label" for="expand_{{ $category->id }}"></label>
                </div>
            </div>
        </div>
        <p class="form-help">{{ __('Expand category on the homepage and show subcategories or articles if there are no subcategories.') }}</p>
    </div>
</div>