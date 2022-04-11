 <div class="row-container">
    <form class="form-horizontal tag-update-form" method="POST" action="" data-tag-id="{{ $tag->id }}" data-confirm-delete="{{ __('Are you sure you want to delete this tag?') }}">

        <div class="form-group">
            <label class="col-sm-2 control-label">{{ __('Name') }}</label>

            <div class="col-sm-10">
                <input class="form-control" name="name" value="{{ $tag->name }}" maxlength="191" required />
            </div>
        </div>

        <div class="form-group">
            <div class="col-sm-10 col-sm-offset-2">
                <a href="{{ route('conversations.search') }}?f[tag]={{ $tag->name }}" target="_blank">{{ __('View conversations') }} <small class="glyphicon glyphicon-new-window"></small></a>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label">{{ __('Color') }}</label>

            <div class="col-sm-10">
                <input type="hidden" name="color" value="{{ $tag->getColor() }}" />
                <div class="btn-group tag-colors">
                    @foreach(Tag::$colors as $color_id)
                        <a class="btn btn-default tag-c-{{ $color_id }} @if ($tag->getColor() == $color_id) active @endif" data-color="{{ $color_id }}">&nbsp;</a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="form-group margin-top margin-bottom-10">
            <div class="col-sm-10 col-sm-offset-2">
                <button type="submit" class="btn btn-primary" data-loading-text="{{ __('Saving') }}…">{{ __('Save') }}</button>

                <a href="" class="btn btn-link text-danger tag-delete-forever" data-loading-text="{{ __('Delete') }}…">{{ __('Delete') }}</a>
            </div>
        </div>
    </form>
</div>

