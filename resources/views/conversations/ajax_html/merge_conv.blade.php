<div class="alert alert-warning margin-bottom">
    <div>
        {{ __('Selected conversation will be merged into the current conversation behind the popup.') }}
    </div>
    <div class="margin-top-10">
        <i class="glyphicon glyphicon-exclamation-sign text-larger"></i> &nbsp; <strong>{{ __("Merged conversations can not be unmerged.") }}</strong>
    </div>
</div>


<div class="form-inline">
    <div class="form-group">
        <label>{{ __('Search Conversation by Number') }} (#)</label>

        <div class="input-group">
            <input type="number" class="form-control merge-conv-number" >
            <span class="input-group-btn">
                <button class="btn btn-default btn-merge-search"  data-loading-text="{{ __('Search') }}…" type="button">{{ __('Search') }}</button>
            </span>
        </div>
    </div>
</div>

<div class="conv-merge-search-result hidden margin-top">
    <table class="table table-striped">
        <tr>
            <td>
                
            </td>
        </tr>
    </table>
</div>

@if (count($prev_conversations))
    <div class="conv-merge-list margin-top">
        <label>{{ __('Previous Conversations') }}</label>

        <table class="table table-striped">
            @foreach ($prev_conversations as $prev_conversation)
                <tr>
                    <td>
                        <div class="radio"><input type="radio" class="conv-merge-id" name="conv_merge" value="{{ $prev_conversation->id }}" /><a href="{{ $prev_conversation->url() }}" target="_blank" data-toggle="tooltip" title="{{ __('Click to view') }}"><strong>#{{ $prev_conversation->number }}</strong> {{ $prev_conversation->getSubject() }}</a></div>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endif

<div class="form-group margin-top">
	<button class="btn btn-primary btn-merge-conv" data-loading-text="{{ __('Merge') }}…" type="submit" disabled>{{ __('Merge') }}</button>
</div>