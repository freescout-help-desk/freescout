<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@if (trim($__env->yieldContent('template_title')))@yield('template_title') | @endif {{ trans('installer_messages.title') }}</title>
        <link href="{{ \Helper::getSubdirectory(true, true) }}css/fonts.css" rel="stylesheet"/>
        <link href="{{ \Helper::getSubdirectory(true, true) }}installer/css/fontawesome.css" rel="stylesheet"/>
        <link href="{{ \Helper::getSubdirectory(true, true) }}installer/css/style.min.css" rel="stylesheet"/>
        @yield('style')
        <script>
            window.Laravel = <?php echo json_encode([
                'csrfToken' => csrf_token(),
            ]); ?>
        </script>
    </head>
    <body>
        <div class="master">
            <div class="box">
                <div class="header">
                    <h1 class="header__title">@yield('title')</h1>
                </div>
                <ul class="step">
                    <li class="step__divider"></li>
                    <li class="step__item {{ isActive('LaravelInstaller::final') }}">
                        <i class="step__icon fa fa-flag-checkered" aria-hidden="true" title="Finish Installation"></i>
                    </li>
                    <li class="step__divider"></li>
                    <li class="step__item {{ isActive('LaravelInstaller::environment')}} {{ isActive('LaravelInstaller::environmentWizard')}} {{ isActive('LaravelInstaller::environmentClassic')}}">
                        <i class="step__icon fa fa-cog" aria-hidden="true" title="Settings"></i>
                    </li>
                    <li class="step__divider"></li>
                    <li class="step__item {{ isActive('LaravelInstaller::permissions') }}">
                        @if(\Helper::isRoute(['LaravelInstaller::environment', 'LaravelInstaller::environmentWizard', 'LaravelInstaller::environmentClassic']))
                            <a href="{{ route('LaravelInstaller::permissions') }}">
                                <i class="step__icon fa fa-key" aria-hidden="true" title="Permissions"></i>
                            </a>
                        @else
                            <i class="step__icon fa fa-key" aria-hidden="true" title="Permissions"></i>
                        @endif
                    </li>
                    <li class="step__divider"></li>
                    <li class="step__item {{ isActive('LaravelInstaller::requirements') }}">
                        @if(\Helper::isRoute(['LaravelInstaller::permissions', 'LaravelInstaller::environment', 'LaravelInstaller::environmentWizard', 'LaravelInstaller::environmentClassic']))
                            <a href="{{ route('LaravelInstaller::requirements') }}">
                                <i class="step__icon fa fa-check" aria-hidden="true" title="Server Requirements"></i>
                            </a>
                        @else
                            <i class="step__icon fa fa-check" aria-hidden="true" title="Server Requirements"></i>
                        @endif
                    </li>
                    <li class="step__divider"></li>
                    <li class="step__item {{ isActive('LaravelInstaller::welcome') }}">
                        @if (\Helper::isRoute(['LaravelInstaller::requirements', 'LaravelInstaller::permissions', 'LaravelInstaller::environment', 'LaravelInstaller::environmentWizard', 'LaravelInstaller::environmentClassic']))
                            <a href="{{ route('LaravelInstaller::welcome') }}">
                                <i class="step__icon fa fa-home" aria-hidden="true" title="Start"></i>
                            </a>
                        @else
                            <i class="step__icon fa fa-home" aria-hidden="true" title="Start"></i>
                        @endif
                    </li>
                    <li class="step__divider"></li>
                </ul>
                <div class="main">
                    @if (session('message'))
                        <p class="alert text-center">
                            <strong>
                                @if(is_array(session('message')))
                                    {{ session('message')['message'] }}
                                @else
                                    {{ session('message') }}
                                @endif
                            </strong>
                        </p>
                    @endif
                    @if(session()->has('errors') || !empty($errors) && count($errors))
                        <div class="alert alert-danger" id="error_alert">
                            <button type="button" class="close" id="close_alert" data-dismiss="alert" aria-hidden="true">
                                 <i class="fa fa-close" aria-hidden="true"></i>
                            </button>
                            <h4>
                                <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                                {{ trans('installer_messages.forms.errorTitle') }}
                            </h4>
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @yield('container')
                </div>
            </div>
        </div>
        @yield('scripts')
        <script type="text/javascript">
            var x = document.getElementById('error_alert');
            var y = document.getElementById('close_alert');
            if (x && y) {
                y.onclick = function() {
                    x.style.display = "none";
                };
            }
        </script>
    </body>
</html>
