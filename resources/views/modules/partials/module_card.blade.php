<div class="module-card col-sm-10 col-md-8 @if (!empty($module['active'])) active @endif" id="module-{{ $module['alias'] }}" data-alias="{{ $module['alias'] }}">
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
			@if (!empty($module['license']))
		    	<span>| {{ __('License') }}: <small>{{ $module['license'] }}</small></span>
		    @endif
		    @if (!\Helper::checkAppVersion($module['version']))
		    	| <span class="text-danger">{{ __('Required app version') }}: {{ $module['requiredAppVersion'] }}</span>
		    @endif
		    @if (!empty($module['detailsUrl']))
		    	| <a href="{{ $module['detailsUrl'] }}" target="_blank">{{ __('View details') }}</a>
		    @endif
		    @if (!empty($module['installed']) && empty($module['active']) && empty($module['activated']))
				| <a href="javascript" class="text-danger delete-module-trigger" data-loading-text="{{ __('Deleting') }}…">{{ __('Delete') }}</a>
			@endif
		</div>
		@if (\Helper::checkAppVersion($module['version']) || !empty($module['active']))
			<div class="module-actions form-horizontal">
				
				@if (!empty($module['active']))
					<button type="submit" class="btn btn-default deactivate-trigger" data-loading-text="{{ __('Deactivating') }}…">{{ __('Deactivate') }}</button>
				@elseif (!empty($module['activated']))
					<button type="submit" class="btn btn-primary activate-trigger" data-loading-text="{{ __('Activating') }}…">{{ __('Activate') }}</button>
				@else
					<form action="javascript:installModule('{{ $module['alias'] }}');">
					<div class="input-group">
						<input type="text" class="form-control license-key" placeholder="{{ __('License Key') }}" required="required">
						<span class="input-group-btn">
							<button class="btn btn-primary install-trigger" type="submit" @if (!empty($module['installed']))data-action="{{ 'activate_license' }}" data-loading-text="{{ __('Activating license') }}…" @else data-action="{{ 'install' }}" data-loading-text="{{ __('Installing') }}…" @endif >@if (!empty($module['installed'])){{ __('Activate License') }}@else{{ __('Install Module') }}@endif</button>
						</span>
				    </div>
				    </form>
				    <small><a href="{{ $module['detailsUrl'] }}" target="_blank">{{ __('Get license key') }}</a></small>
				@endif

				@if (!empty($module['installed']) && empty($module['active']) && !empty($module['activated']))
					<a href="javascript" class="btn btn-link text-danger delete-module-trigger" data-loading-text="{{ __('Deleting') }}…">{{ __('Delete') }}</a>
				@endif
				
			</div>
		@endif
	</div>
</div>