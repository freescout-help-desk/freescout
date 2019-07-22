<div class="module-card col-sm-10 col-md-8 @if (!empty($module['active'])) active @endif" id="module-{{ $module['alias'] }}" data-alias="{{ $module['alias'] }}">
	@if (!empty($module['img']))
		<img src="{{ $module['img'] }}" />
	@else
		<img src="{{ App\Module::IMG_DEFAULT }}" />
	@endif
	<div class="module-wrap">
	    <h4>{{ preg_replace("/ Module$/", '', $module['name']) }}@if (empty($module['installed'])) <span class="text-help">({{ __('not installed') }})</span>@elseif (empty($module['active'])) <span class="text-help">({{ __('inactive') }})</span>@endif</h4>
	    <p>
	    	{{ $module['description'] }}
	    </p>
	    <div class="module-details">
		    <span>{{ __('Version') }}: {{ $module['version'] }}</span>
			@if (!empty($module['license']))
		    	<span>| {{ __('License') }}: <small>{{ $module['license'] }}</small></span>
		    @endif
		    @if (!empty($module['requiredAppVersion']) && !\Helper::checkAppVersion($module['requiredAppVersion']))
		    	@php
		    		$wrong_app_verion = true;
		    	@endphp
		    	| <span class="text-danger nowrap">{{ __('Required :app_name version', ['app_name' => \Config::get('app.name')]) }}: <strong>{{ $module['requiredAppVersion'] }}</strong></span>
		    @endif
		    @if (!empty($module['requiredPhpExtensionsMissing']))
		    	| <span class="text-danger nowrap">{{ __('Required PHP extensions') }}: <strong>{{ implode(', ', $module['requiredPhpExtensionsMissing']) }}</strong></span>
		    @endif
		    @if (!empty($module['detailsUrl']))
		    	| <a href="{{ $module['detailsUrl'] }}" target="_blank">{{ __('View details') }}</a>
		    @endif
		    @if (!empty($module['installed']) && empty($module['active']) && empty($module['activated']))
				| <a href="javascript" class="text-danger delete-module-trigger" data-loading-text="{{ __('Deleting') }}…">{{ __('Delete') }}</a>
			@endif
		</div>
		<div class="module-actions form-horizontal">
			
			@if ((empty($wrong_app_verion) && empty($module['requiredPhpExtensionsMissing'])) || !empty($module['active']))
				@if (!empty($module['active']))
					<button type="submit" class="btn btn-default deactivate-trigger" data-loading-text="{{ __('Deactivating') }}…">{{ __('Deactivate') }}</button>
				@elseif (!empty($module['activated']))
					<button type="submit" class="btn btn-primary activate-trigger" data-loading-text="{{ __('Activating') }}…">{{ __('Activate') }}</button>
				@else
					<form action="javascript:installModule('{{ $module['alias'] }}');">
					<div class="input-group">
						<input type="text" class="form-control license-key" placeholder="{{ __('License Key') }}" value="{{ App\Module::getLicense($module['alias']) }}" required="required">
						<span class="input-group-btn">
							<button class="btn btn-primary install-trigger" type="submit" @if (!empty($module['installed']))data-action="{{ 'activate_license' }}" data-loading-text="{{ __('Activating license') }}…" @else data-action="{{ 'install' }}" data-loading-text="{{ __('Installing') }}…" @endif >@if (!empty($module['installed'])){{ __('Activate License') }}@else{{ __('Install Module') }}@endif</button>
						</span>
				    </div>
				    </form>
				    <small><a href="{{ $module['detailsUrl'] }}" target="_blank">{{ __('Get license key') }}</a></small>
				@endif
			@endif

			@if (!empty($module['installed']) && empty($module['active']) && !empty($module['activated']))
				<a href="javascript" class="btn btn-link text-danger delete-module-trigger" data-loading-text="{{ __('Deleting') }}…">{{ __('Delete') }}</a>
			@endif
		</div>
		@if (!empty($module['new_version']))
			<div class="alert alert-warning alert-module-update">
				{{ __('A new version is available') }}: <strong>{{ $module['new_version'] }}</strong> (<a href="{{ $module['detailsUrl'] }}?changelog=1" target="_blank">{{ __('View details') }}</a>) 
				<a href="" class="btn btn-default btn-sm update-module-trigger margin-left-10" data-loading-text="{{ __('Updating') }}…"><i class="glyphicon glyphicon-refresh"></i> {{ __('Update Now') }}</a>
			</div>
		@endif
	</div>
</div>