@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.environment.wizard.templateTitle') }}
@endsection

@section('title')
    <i class="fa fa-cog fa-fw" aria-hidden="true"></i>
    Settings
@endsection

@section('container')
    <div class="tabs tabs-full">

        <input id="tab1" type="radio" name="tabs" class="tab-input" checked />
        <label for="tab1" class="tab-label">
            <i class="fa fa-cog fa-2x fa-fw" aria-hidden="true"></i>
            <br />
            {{ trans('installer_messages.environment.wizard.tabs.environment') }}
        </label>

        <input id="tab2" type="radio" name="tabs" class="tab-input" />
        <label for="tab2" class="tab-label">
            <i class="fa fa-database fa-2x fa-fw" aria-hidden="true"></i>
            <br />
            {{ trans('installer_messages.environment.wizard.tabs.database') }}
        </label>

        <input id="tab3" type="radio" name="tabs" class="tab-input" />
        <label for="tab3" class="tab-label">
            <i class="fa fa-cogs fa-2x fa-fw" aria-hidden="true"></i>
            <br />
            {{ trans('installer_messages.environment.wizard.tabs.application') }}
        </label>

        <input id="tab4" type="radio" name="tabs" class="tab-input" />
        <label for="tab4" class="tab-label">
            <i class="fa fa-user fa-2x fa-fw" aria-hidden="true"></i>
            <br />
            Admin
        </label>

        <form method="post" action="{{ route('LaravelInstaller::environmentSaveWizard', [], false) }}" class="tabs-wrap">
            <div class="tab" id="tab1content">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <div class="form-group {{ $errors->has('app_url') ? ' has-error ' : '' }}">
                    <label for="app_url">
                        App URL
                    </label>
                    <input type="url" name="app_url" id="app_url" value="{{ old('app_url', trim(Request::root().\Helper::getSubdirectory(false, true), '/')) }}" placeholder="{{ trans('installer_messages.environment.wizard.form.app_url_placeholder') }}" />
                    @if ($errors->has('app_url'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('app_url') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('app_force_https') ? ' has-error ' : '' }}">
                    <label for="app_force_https">
                        Use HTTPS protocol
                    </label>
                    @php
                        $force_https = false;
                        if (old('app_force_https') == 'true' || \Config::get('app.force_https')) {
                            $force_https = true;
                        }
                    @endphp
                    <label for="app_force_https_true">
                        <input type="radio" name="app_force_https" id="app_force_https_true" value="true" @if ($force_https) checked @endif />
                        Yes
                    </label>
                    <label for="app_force_https_false">
                        <input type="radio" name="app_force_https" id="app_force_https_false" value="false" @if (!$force_https) checked @endif />
                        No
                    </label>
                    @if ($errors->has('app_force_https'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('app_force_https') }}
                        </span>
                    @endif
                </div>

                <div class="buttons">
                    <button class="button" onclick="showDatabaseSettings();return false">
                        {{ trans('installer_messages.environment.wizard.form.buttons.setup_database') }}
                        <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="tab" id="tab2content">

                <div class="form-group {{ $errors->has('database_connection') ? ' has-error ' : '' }}">
                    <label for="database_connection">
                        {{ trans('installer_messages.environment.wizard.form.db_connection_label') }}
                    </label>
                    <select name="database_connection" id="database_connection">
                        <option value="mysql" @if (old('database_connection', env('DB_CONNECTION', 'mysql')) == 'mysql') selected @endif>MySQL</option>
                        <option value="pgsql" @if (old('database_connection', env('DB_CONNECTION')) == 'pgsql') selected @endif>PostgreSQL</option>
                        <option value="sqlite" @if (old('database_connection', env('DB_CONNECTION')) == 'sqlite') selected @endif>SQLite</option>
                        <option value="sqlsrv" @if (old('database_connection', env('DB_CONNECTION')) == 'sqlsrv') selected @endif>SQL Server</option>
                    </select>
                    @if ($errors->has('database_connection'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_connection') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('database_hostname') ? ' has-error ' : '' }}">
                    <label for="database_hostname">
                        {{ trans('installer_messages.environment.wizard.form.db_host_label') }}
                    </label>
                    <input type="text" name="database_hostname" id="database_hostname" value="{{ old('database_hostname', env('DB_HOST', '127.0.0.1')) }}" />
                    @if ($errors->has('database_hostname'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_hostname') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('database_port') ? ' has-error ' : '' }}">
                    <label for="database_port">
                        Port
                    </label>
                    <input type="number" name="database_port" id="database_port" value="{{ old('database_port', env('DB_PORT', '3306')) }}" />
                    @if ($errors->has('database_port'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_port') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('database_name') ? ' has-error ' : '' }}">
                    <label for="database_name">
                        {{ trans('installer_messages.environment.wizard.form.db_name_label') }}
                    </label>
                    <input type="text" name="database_name" id="database_name" value="{{ old('database_name', env('DB_DATABASE')) }}" />
                    @if ($errors->has('database_name'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_name') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('database_username') ? ' has-error ' : '' }}">
                    <label for="database_username">
                        Username
                    </label>
                    <input type="text" name="database_username" id="database_username" value="{{ old('database_username', env('DB_USERNAME')) }}" />
                    @if ($errors->has('database_username'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_username') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('database_password') ? ' has-error ' : '' }}">
                    <label for="database_password">
                        Password
                    </label>
                    <input type="text" name="database_password" id="database_password" value="{{ old('database_password', env('DB_PASSWORD')) }}" />
                    @if ($errors->has('database_password'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_password') }}
                        </span>
                    @endif
                </div>

                <div class="buttons">
                    <button class="button" onclick="showApplicationSettings();return false">
                        {{ trans('installer_messages.environment.wizard.form.buttons.setup_application') }}
                        <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="tab" id="tab3content">

                <div class="form-group {{ $errors->has('app_locale') ? ' has-error ' : '' }}">
                    <label for="app_locale">
                        Language
                    </label>
                    <select name="app_locale" id="app_locale">
                        @include('partials/locale_options', ['selected' => old('app_locale', \Config::get('app.locale')), 'no_custom_locales' => true])
                    </select>
                    @if ($errors->has('app_locale'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('app_locale') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('app_timezone') ? ' has-error ' : '' }}">
                    <label for="app_timezone">
                        Timezone
                    </label>
                    <select name="app_timezone" id="app_timezone">
                        @include('partials/timezone_options', ['current_timezone' => old('app_timezone', \Config::get('app.timezone'))])
                    </select>
                    @if ($errors->has('app_timezone'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('app_timezone') }}
                        </span>
                    @endif
                </div>

                <div class="buttons">
                    <button class="button" onclick="showAdminSettings();return false">
                        Setup Admin
                        <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="tab" id="tab4content">
                
                <div class="form-group {{ $errors->has('admin_email') ? ' has-error ' : '' }}">
                    <label for="admin_email">
                        Email
                    </label>
                    <input type="text" name="admin_email" id="admin_email" value="{{ old('admin_email') }}" />
                    @if ($errors->has('admin_email'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('admin_email') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('admin_first_name') ? ' has-error ' : '' }}">
                    <label for="admin_first_name">
                        First Name
                    </label>
                    <input type="text" name="admin_first_name" id="admin_first_name" value="{{ old('admin_first_name') }}" />
                    @if ($errors->has('admin_first_name'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('admin_first_name') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('admin_last_name') ? ' has-error ' : '' }}">
                    <label for="admin_last_name">
                        Last Name
                    </label>
                    <input type="text" name="admin_last_name" id="admin_last_name" value="{{ old('admin_last_name') }}" />
                    @if ($errors->has('admin_last_name'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('admin_last_name') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('admin_password') ? ' has-error ' : '' }}">
                    <label for="admin_password">
                        Password
                    </label>
                    <input type="password" name="admin_password" id="admin_password" value="{{ old('admin_password') }}" />
                    @if ($errors->has('admin_password'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('admin_password') }}
                        </span>
                    @endif
                </div>

                <div class="buttons">
                    <button class="button" type="submit">
                        {{ trans('installer_messages.environment.wizard.form.buttons.install') }}
                        <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </form>

    </div>
@endsection

@section('scripts')
    <script type="text/javascript">
        function checkEnvironment(val) {
            var element=document.getElementById('environment_text_input');
            if(val=='other') {
                element.style.display='block';
            } else {
                element.style.display='none';
            }
        }
        function showDatabaseSettings() {
            document.getElementById('tab2').checked = true;
        }
        function showApplicationSettings() {
            document.getElementById('tab3').checked = true;
        }
        function showAdminSettings() {
            document.getElementById('tab4').checked = true;
        }
    </script>
@endsection
