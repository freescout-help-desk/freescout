<div class="module-card @if (!empty($module['active'])) active @endif">
	@if (!empty($module['img']))
		<img src="{{ $module['img'] }}" />
	@else
		<img src="{{ App\Module::IMG_DEFAULT }}" />
	@endif
	<div class="module-wrap">
	    <h4>{{ preg_replace("/ Module$/", '', $module['name']) }}</h4>
	    <p>
	    	{{ $module['description'] }}
	    </p>
	    <div class="module-details">
		    <span>{{ __('Version') }}: {{ $module['version'] }}</span>
		    @if (!\Helper::checkAppVersion($module['version']))
		    	| <span class="text-danger">{{ __('Required app version') }}: {{ $module['requiredAppVersion'] }}</span>
		    @endif
		    @if (!empty($module['detailsUrl']))
		    	| <a href="{{ $module['detailsUrl'] }}" target="_blank">{{ __('View details') }}</a>
		    @endif
		</div>
		@if (\Helper::checkAppVersion($module['version']) || !empty($module['active']))
			<div class="module-actions">
				
				@if (!empty($module['activated']))
					@if (empty($module['active']))
						<button type="submit" class="btn btn-primary">{{ __('Activate') }}</button>
					@else
						<button type="submit" class="btn btn-default">{{ __('Deactivate') }}</button>
					@endif
				@else
					<div class="input-group">
						<input type="text" class="form-control" placeholder="{{ __('License Key') }}">
						<span class="input-group-btn">
							<button class="btn btn-default" type="button">@if (!empty($module['installed'])){{ __('Activate License') }}@else{{ __('Install Module') }}@endif</button>
						</span>
				    </div>
				@endif

				@if (empty($module['active']))
					<a href="#" data-trigger="modal" data-modal-body="#delete_mailbox_modal" data-modal-no-footer="true" data-modal-title="{{ __('Delete Module') }}" data-modal-on-show="deleteModuleModal" class="btn btn-link text-danger">{{ __('Delete') }}</a>
				@endif
				
			</div>
		@endif
	</div>
</div>