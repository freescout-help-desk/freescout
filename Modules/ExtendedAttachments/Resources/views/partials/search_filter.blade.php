<div class="col-sm-6 form-group @if (isset($filters[\ExtendedAttachment::searchFilterName()])) active @endif" data-filter="attachment name">
    <label>{{ __('Attachment Name') }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>

    <input name="f[{{ \ExtendedAttachment::searchFilterName() }}]" value="{{ $filters[\ExtendedAttachment::searchFilterName()] ?? '' }}" class="form-control" @if (empty($filters[\ExtendedAttachment::searchFilterName()])) disabled @endif />
</div>
