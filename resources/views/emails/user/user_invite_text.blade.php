{{ __(':user created an account for you at :app_url', ['user' => $user->getFullName(), 'app_url' => '['.\Config::get('app.url').']']) }}

{{ __('Create a Password') }}
-------------------------------------------------
{{ $user->urlSetup() }}


{{ __('Welcome to the team!') }}
-------------------------------------------------
{{ __(':app is for companies that need to share an inbox, support customers and stay in sync as a team. Someone on your team created an account for you.', ['app' => \Config::get('app.name')]) }}