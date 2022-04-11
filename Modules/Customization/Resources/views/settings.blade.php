<form class="form-horizontal margin-top margin-bottom" method="POST" action="" enctype="multipart/form-data">
    {{ csrf_field() }}

    <div class="form-group">
        <label for="" class="col-sm-2 control-label">{{ __('Header Logo') }}</label>

        <div class="col-sm-6 cust-img-wrapper">
            <input type="file" name="customization_logo" accept=".jpg,.png,.gif,.jpeg,.svg" />
            <input type="hidden" class="cust-img-remove-input" name="customization_logo_remove" value="" />
            
            <p class="form-help">(22 x 22) JPG, GIF, PNG, SVG</p>

            <div class="panel cust-chess margin-bottom-0">
                <div class="panel-body">
                    @if (!empty($settings['customization_logo']))
                        <img src="{{ \Helper::uploadedFileUrl($settings['customization_logo'])}}" class="cust-img-custom"/>
                    @endif
                    <img src="{{ asset('img/logo-brand.svg') }}" class="cust-img-default @if (!empty($settings['customization_logo'])) hidden @endif" width="21" heght="21"/>
                </div>
            </div>
            @if (!empty($settings['customization_logo']))
                <a href="" class="cust-img-remove">{{ __('Remove') }}</a>
            @endif

            @include('partials/field_error', ['field'=>'customization_logo'])
        </div>
    </div>

    <div class="form-group">
        <label for="" class="col-sm-2 control-label">{{ __('Login Page Banner') }}</label>

        <div class="col-sm-6 cust-img-wrapper">
            <input type="file" name="customization_banner" accept=".jpg,.png,.gif,.jpeg,.svg"/>
            <input type="hidden" class="cust-img-remove-input" name="customization_banner_remove" value="" />
            
            <p class="form-help">(184 x 36) JPG, GIF, PNG, SVG</p>

            <div class="panel cust-chess margin-bottom-0">
                <div class="panel-body">
                    @if (!empty($settings['customization_banner']))
                        <img src="{{ \Helper::uploadedFileUrl($settings['customization_banner'])}}" class="cust-img-custom"/>
                    @endif
                    <img src="{{ asset('img/banner.png') }}" class="cust-img-default @if (!empty($settings['customization_banner'])) hidden @endif" />
                </div>
            </div>
            @if (!empty($settings['customization_banner']))
                <a href="" class="cust-img-remove">{{ __('Remove') }}</a>
            @endif

            @include('partials/field_error', ['field'=>'customization_banner'])
        </div>
    </div>

    <div class="form-group">
        <label for="" class="col-sm-2 control-label">{{ __('Favicon') }}</label>

        <div class="col-sm-6 cust-img-wrapper">
            <input type="file" name="customization_favicon" accept=".ico"/>
            <input type="hidden" class="cust-img-remove-input" name="customization_favicon_remove" value="" />
            
            <p class="form-help">(16 x 16) ICO</p>

            <div class="panel cust-chess margin-bottom-0">
                <div class="panel-body">
                    @if (!empty($settings['customization_favicon']))
                        <img src="{{ \Helper::uploadedFileUrl($settings['customization_favicon'])}}" class="cust-img-custom"/>
                    @endif
                    <img src="{{ asset('favicon.ico') }}" class="cust-img-default @if (!empty($settings['customization_favicon'])) hidden @endif" />
                </div>
            </div>
            @if (!empty($settings['customization_favicon']))
                <a href="" class="cust-img-remove">{{ __('Remove') }}</a>
            
                <p class="form-help">{{ __('You may need to clear your browser cache in order to see updated favicon.') }}</p>
            @endif
            @include('partials/field_error', ['field'=>'customization_favicon'])
        </div>
    </div>

    <div class="form-group">
        <label for="" class="col-sm-2 control-label">{{ __('Footer') }}</label>

        <div class="col-sm-6">
            <textarea id="customization_footer" class="form-control" name="settings[customization_footer]" rows="8">{{ old('settings[customization_footer]', $settings['customization_footer']) }}</textarea>
            @include('partials/field_error', ['field'=>'customization_footer'])
        </div>
    </div>

    <div class="form-group">
        <label for="" class="col-sm-2 control-label">{{ __('Custom CSS') }}</label>

        <div class="col-sm-6">
            <textarea class="form-control" name="settings[customization_css]" rows="5">{{ old('settings[customization_css]', $settings['customization_css']) }}</textarea>
        </div>
    </div>

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>

@include('partials/editor')

@section('javascript')
    @parent
    initCustomization();
@endsection