{{ __('Hi :user, an account has been created for you at :app_url', ['user' => $user->getFullName(), 'app_url' => '['.\Config::get('app.url').']']) }}

{{ __('Create a Password') }}
-------------------------------------------------
{{ $user->urlSetup() }}


{{ __('Welcome to the team!') }}
-------------------------------------------------
{{ __('Someone on your team created an account for you.') }}