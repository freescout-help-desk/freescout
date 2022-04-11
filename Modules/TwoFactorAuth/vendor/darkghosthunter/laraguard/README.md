![Zachary Lisko - Unsplash (UL) #JEBeXUHm1c4](https://images.unsplash.com/flagged/photo-1570343271132-8949dd284a04?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=400&q=80)

# Laraguard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/laraguard.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/laraguard) [![License](https://poser.pugx.org/darkghosthunter/laraguard/license)](https://packagist.org/packages/darkghosthunter/laraguard)
![](https://img.shields.io/packagist/php-v/darkghosthunter/laraguard.svg)
 ![](https://github.com/DarkGhostHunter/Laraguard/workflows/PHP%20Composer/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/Laraguard/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/Laraguard?branch=master)

Two Factor Authentication via TOTP for all your Users out-of-the-box.

This package _silently_ enables authentication using 6 digits codes, without Internet or external providers.

## Requirements

* Laravel [6.15](https://blog.laravel.com/laravel-v6-15-0-released) or Laravel 7.
* PHP 7.2+

## Table of Contents

* [Installation](#installation)
    + [How this works](#how-this-works)
* [Usage](#usage)
    + [Enabling Two Factor Authentication](#enabling-two-factor-authentication)
    + [Recovery Codes](#recovery-codes)
    + [Logging in](#logging-in)
    + [Deactivation](#deactivation)
* [Events](#events)
* [Middleware](#middleware)
* [Validation](#validation)
* [Translations](#translations)
* [Protecting the Login](#protecting-the-login)
* [Configuration](#configuration)
    + [Listener](#listener)
    + [Eloquent Model](#eloquent-model)
    + [Input name](#input-name)
    + [Cache Store](#cache-store)
    + [Recovery](#recovery)
    + [Safe devices](#safe-devices)
    + [Confirmation Middleware](#confirmation-middleware)
    + [Secret length](#secret-length)
    + [TOTP configuration](#totp-configuration)
    + [QR Code Configuration](#qr-code-configuration)
    + [Custom view](#custom-view)
* [Security](#security)
* [License](#license)

## Installation

Fire up Composer and require this package in your project.

    composer require darkghosthunter/laraguard

That's it.

### How this works

This packages adds a **Contract** to detect in a **per-user basis** if it should use Two Factor Authentication. It includes a custom **view** and a **listener** to handle the Two Factor authentication itself during login attempts.

This package was made to be the less invasive possible, but you can go full manual if you want.

## Usage

First, create the `two_factor_authentications` table by running the migration:

    php artisan migrate

This will create a table to handle the Two Factor Authentication information for each model you want to attach to 2FA.

> If you need to modify the migration from this package, you can publish it to override whatever you need.
>
>     php artisan vendor:publish --provider="DarkGhostHunter\Laraguard\LaraguardServiceProvider" --tag="migrations"

Add the `TwoFactorAuthenticatable` _contract_ and the `TwoFactorAuthentication` trait to the User model, or any other model you want to make Two Factor Authentication available. 

```php
<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use DarkGhostHunter\Laraguard\TwoFactorAuthentication;
use DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable;

class User extends Authenticatable implements TwoFactorAuthenticatable
{
    use TwoFactorAuthentication;
    
    // ...
}
```

The contract is used to identify the model using Two Factor Authentication, while the trait conveniently implements the methods required to handle it.

### Enabling Two Factor Authentication

To enable Two Factor Authentication successfully, the User must sync the Shared Secret between its Authenticator app and the application. 

> Some free Authenticator Apps are [FreeOTP](https://freeotp.github.io/), [Authy](https://authy.com/), [andOTP](https://github.com/andOTP/andOTP), [Google Authenticator](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en), and [Microsoft Authenticator](https://www.microsoft.com/en-us/account/authenticator), to name a few.

To start, generate the needed data using the `createTwoFactorAuth()` method. Once you do, you can show the Shared Secret to the User as a string or QR Code (encoded as SVG) in your view.

```php
public function prepareTwoFactor(Request $request)
{
    $secret = $request->user()->createTwoFactorAuth();
    
    return view('user.2fa', [
        'as_qr_code' => $secret->toQr(),     // As QR Code
        'as_uri'     => $secret->toUri(),    // As "otpauth://" URI.
        'as_string'  => $secret->toString(), // As a string
    ]);
}
```

> When you use `createTwoFactorAuth()` on someone with Two Factor Authentication already enabled, the previous data becomes permanently invalid. This ensures a User **never** has two Shared Secrets enabled at any given time.

Then, the User must confirm the Shared Secret with a Code generated by their Authenticator app. The `confirmTwoFactorAuth()` method will automatically enable it if the code is valid.

```php
public function confirmTwoFactor(Request $request)
{
    $activated = $request->user()->confirmTwoFactorAuth(
        $request->input('2fa_code')
    );
    
    // ...
}
```

If the User doesn't issue the correct Code, the method will return `false`. You can tell the User to double-check its device's timezone, or create another Shared Secret with `createTwoFactorAuth()`.

### Recovery Codes

Recovery Codes are automatically generated each time the Two Factor Authentication is enabled. By default, a Collection of ten one-use 8-characters codes are created.

You can show them using `getRecoveryCodes()`.

```php
public function confirmTwoFactor(Request $request)
{
    if ($request->user()->confirmTwoFactorAuth($request->code)) {
        return $request->user()->getRecoveryCodes();
    } else {
        return 'Try again!';
    }
}
```

You're free on how to show these codes to the User, but **ensure** you show them one time after a successfully enabling Two Factor Authentication, and ask him to print them somewhere.

> These Recovery Codes are handled automatically when the User validates one. If it's a recovery code, the package will use and mark it as invalid.

The User can generate a fresh batch of codes using `generateRecoveryCodes()`, which automatically invalidates the previous batch.

```php
public function showRecoveryCodes(Request $request)
{
    return $request->user()->generateRecoveryCodes();
}
```

> If the User depletes his recovery codes without disabling Two Factor Authentication, or Recovery Codes are deactivated, **he may be locked out forever without his Authenticator app**. Ensure you have countermeasures in these cases.

### Logging in

This package hooks into the `Attempting` and `Validated` events to check the User's Two Factor Authentication configuration preemptively.

1. If the User has set up Two Factor Authentication, it will be prompted for a 2FA Code, otherwise authentication will proceed as normal.
2. If the Login attempt contains a `2fa_code` with the 2FA Code inside the Request, it will be used to check if its valid and proceed as normal.

This is done transparently without intervening your application with guards, routes, controllers or middleware.

Additionally, **ensure you [protect your login route](#protecting-the-login)**.

> If you're using a custom Authentication Guard that doesn't fire events, this package won't work, like the `TokenGuard` and the `RequestGuard`.

### Deactivation

You can deactivate Two Factor Authentication for a given User using the `disableTwoFactorAuth()` method. This will automatically invalidate the authentication data, allowing the User to log in with just his credentials.

```php
public function disableTwoFactorAuth(Request $request)
{
    $request->user()->disableTwoFactorAuth();
    
    return 'Two Factor Authentication has been disabled!';
}
```

## Events

The following events are fired in addition to the default Authentication events.

* `TwoFactorEnabled`: An User has enabled Two Factor Authentication.
* `TwoFactorRecoveryCodesDepleted`: An User has used his last Recovery Code.
* `TwoFactorRecoveryCodesGenerated`: An User has generated a new set of Recovery Codes.
* `TwoFactorDisabled`: An User has disabled Two Factor Authentication.

> You can use `TwoFactorRecoveryCodesDepleted` to tell the User to create more Recovery Codes.

## Middleware

Laraguard comes with two middleware for your routes: `2fa.require` and `2fa.confirm`.

> To avoid unexpected results, these middleware only act on your users models with `TwoFactorAuthenticatable`. If a user model doesn't implements it, the middleware bypass any 2FA logic.

### Require 2FA

If you need to ensure the User has Two Factor Authentication enabled before entering a given route, you can use the `2fa.require` middleware.

```php
Route::get('system/settings')
    ->uses('SystemSettingsController@show')
    ->middleware('2fa.require');
```

This middleware works much like the `verified` middleware: if the User has not enabled Two Factor Authentication, it will be redirected to a route name containing the warning, which is `2fa.notice` by default. 

You can implement the view easily with the one included in this package:

```php
Route::view('2fa-required', 'laraguard::notice')->name('2fa.notice');
```

Alternatively, you can use a custom controller action to also include a link to where he can enable Two Factor Authentication.

```php
public function notice()
{
    return view('2fa.notice', [
        'url' => url('account/settings')
    ]);
}
```

### Confirm 2FA

Much like the [`password.confirm` middleware](https://laravel.com/docs/authentication#password-confirmation), you can also ask the user to confirm an action using `2fa.confirm`.

```php
Route::get('api/token')
    ->uses('ApiTokenController@show')
    ->middleware('2fa.confirm');
```

Laraguard automatically uses the [`Confirm2FACodeController`](src/Http/Controllers/Confirm2FACodeController.php) to handle the form view and the code confirmation for you.

Alternatively, [you can point your own controller actions](#confirmation-middleware) to handle the form view and confirmation. Better yet, you can start with the [`Confirms2FACode`](src/Http/Controllers/Confirms2FACode.php) trait to avoid reinventing the wheel.

## Validation

Sometimes you may want to manually trigger a TOTP validation in any part of your application for the authenticated user. You can validate a TOTP code for the authenticated user using the `totp_code` rule.

```php
public function checkTotp(Request $request)
{
    $request->validate([
        'code' => 'required|totp_code'
    ]);

    // ...
}
```

This rule will succeed if the user is authenticated, is has Two Factor Authentication enabled, and the code is correct.

## Translations

Laraguard comes with translation files (only for english) that you can use immediately in your application. These are also used for the [validation rule](#validation).

```php
public function disableTwoFactorAuth()
{
    // ...

    session()->flash('2fa_disabled', trans('laraguard::messages.disabled'));

    return back();
}
```

To add your own in your language, publish the translation files. These will be located in `resources/vendor/laraguard`:

    php artisan vendor:publish --provider="DarkGhostHunter\Laraguard\LaraguardServiceProvider" --tag="translations"

## Protecting the Login

Two Factor Authentication can be victim of brute-force attacks. The attacker will need between 16.000~34.000 requests each second to get the correct code, or less depending on the lifetime of the code.

Since the listener throws a response before the default Login throttler increments its failed tries, its recommended to use a try-catch in the `attemptLogin()` method to keep the throttler working.

```php
/**
 * Attempt to log the user into the application.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return bool
 */
protected function attemptLogin(Request $request)
{
    try {
        return $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );
    } catch (HttpResponseException $exception) {
        $this->incrementLoginAttempts($request);
        throw $exception;
    }
}
```  

To show the form, the Listener uses `HttpResponseException` to forcefully exit the authentication logic. This exception catch allows to throw the response after the login attempts are incremented.

## Configuration

To further configure the package, publish the configuration files and assets:

    php artisan vendor:publish --provider="DarkGhostHunter\Laraguard\LaraguardServiceProvider"

You will receive the authentication view in `resources/views/vendor/laraguard/auth.blade.php`, and the `config/laraguard.php` config file with the following contents:

```php
return [
    'listener' => \DarkGhostHunter\Laraguard\Listeners\EnforceTwoFactorAuth::class,
    'model' => \DarkGhostHunter\Laraguard\Eloquent\TwoFactorAuthentication::class,
    'input' => '2fa_code',
    'cache' => [
        'store' => null,
        'prefix' => '2fa.code'
    ],
    'recovery' => [
        'enabled' => true,
        'codes' => 10,
        'length' => 8,
	],
    'safe_devices' => [
        'enabled' => false,
        'max_devices' => 3,
        'expiration_days' => 14,
	],
    'confirm' => [
        'timeout' => 10800,
        'view' => 'DarkGhostHunter\Laraguard\Http\Controllers\Confirm2FACodeController@showConfirmForm',
        'action' => 'DarkGhostHunter\Laraguard\Http\Controllers\Confirm2FACodeController@confirm'
    ],
    'secret_length' => 20,
    'issuer' => env('OTP_TOTP_ISSUER'),
    'totp' => [
        'digits' => 6,
        'seconds' => 30,
        'window' => 1,
        'algorithm' => 'sha1',
    ],
    'qr_code' => [
        'size' => 400,
        'margin' => 4
    ],
];
```

### Listener

```php
return [
    'listener' => \DarkGhostHunter\Laraguard\Listeners\EnforceTwoFactorAuth::class,
];
```

This package works out-of-the-box by hooking up the `ForcesTwoFactorAuth` listener to the `Attempting` and `Validated` events, which is in charge of checking if the user login needs a 2FA code or not. 

This may work wonders, but if you want tighter control on how and when prompt for Two Factor Authentication, you can use another listener, or disable it. For example, to create your own 2FA Guard or greatly modify the Login Controller.

> If you change it for your own Listener, ensure it implements the `TwoFactorAuthListener` contract.

### Eloquent Model

```php
return [
    'model' => \DarkGhostHunter\Laraguard\Eloquent\TwoFactorAuthentication::class,
];
```

This is the model where the data for Two Factor Authentication is saved, like the shared secret and recovery codes, and associated to the User model.

You can change this model for your own if you wish.

> If you change it for your own Model, ensure it implements the `TwoFactorTotp` contract.

### Input name

```php
return [
    'input' => '2fa_code',
];
```

By default, the input name that must contain the Two Factor Authentication Code is called `2fa_code`, which is a good default value to avoid collisions with other inputs names.

This allows to seamlessly intercept the log in attempt and proceed with Two Factor Authentication or bypass it. Change it if it collides with other login form inputs.

### Cache Store

```php
return  [
    'cache' => [
        'store' => null,
        'prefix' => '2fa.code'
    ],
];
```

[RFC 6238](https://tools.ietf.org/html/rfc6238#section-5) states that one-time passwords shouldn't be able to be usable again, even if inside the time window. For this, we need to use the Cache to save the code for a given period.

You can change the store to use, which it's the default used by your application, and the prefix to use as cache keys, in case of collisions.

### Recovery

```php
return [
    'recovery' => [
        'enabled' => true,
        'codes' => 10,
        'length' => 8,
    ],
];
```

You can disable the generation and checking of Recovery Codes. If you do, ensure Users can authenticate by other means, like sending an email with a link to a signed URL that logs him in and disables Two Factor Authentication, or SMS.

The number and length of codes generated is configurable. 10 Codes of 8 random characters are enough for most authentication scenarios.

### Safe devices

```php
return [
    'safe_devices' => [
        'enabled' => false,
        'max_devices' => 3,
        'expiration_days' => 14,
    ],
];
```

Enabling this option will allow the application to "remember" a device using a cookie, allowing it to bypass Two Factor Authentication once a code is verified in that device. When the User logs in again in that device, it won't be prompted for a 2FA Code again. 

There is a limit of devices that can be saved. New devices will displace the oldest devices registered. Devices are considered no longer "safe" until a set amount of days.

You can change the maximum number of devices saved and the amount of days of validity once they're registered. More devices and more expiration days will make the Two Factor Authentication less secure.

> When re-enabling Two Factor Authentication, the list of devices is automatically invalidated.

### Confirmation Middleware

```php
return [
    'confirm' => [
        'timeout' => 10800, // 3 hours
        'view' => 'DarkGhostHunter\Laraguard\Http\Controllers\Confirm2FACodeController@showConfirmForm',
        'action' => 'DarkGhostHunter\Laraguard\Http\Controllers\Confirm2FACodeController@confirm'
    ],
];
```

If the `view` or `action` are not `null`, the `2fa/notice` and `2fa/confirm` routes will be registered to handle 2FA code notice and confirmation for the [`2fa.confirm` middleware](#confirm-2fa). If you disable it, you will have to register the routes and controller actions yourself.

This array also sets by how much to "remember" the 2FA Code confirmation, and the actions used to show the view to confirm the 2FA Code with also the action to handle the confirmation.

You may want to change these, specially if you want your own view to show the confirmation form.

### Secret length

```php
return [
    'secret_length' => 20,
];
```

This controls the length (in bytes) used to create the Shared Secret. While a 160-bit shared secret is enough, you can tighten or loosen the secret length to your liking.

It's recommended to use 128-bit or 160-bit because some Authenticator apps may have some problems with other non-RFC-recommended lengths.

### TOTP Configuration 

```php
return [
    'issuer' => env('OTP_TOTP_ISSUER'),
    'totp' => [
        'digits' => 6,
        'seconds' => 30,
        'window' => 1,
        'algorithm' => 'sha1',
    ],
];
```

This controls TOTP code generation and verification mechanisms:

* Issuer: The name of the issuer of the TOTP. Default is the application name. 
* TOTP Digits: The amount of digits to ask for TOTP code. 
* TOTP Seconds: The number of seconds a code is considered valid.
* TOTP Window: Additional steps of seconds to keep a code as valid.
* TOTP Algorithm: The system-supported algorithm to handle code generation.

This configuration values are always passed down to the authentication app as URI parameters:

    otpauth://totp/Laravel:taylor@laravel.com?secret=THISISMYSECRETPLEASEDONOTSHAREIT&issuer=Laravel&label=taylor%40laravel.com&algorithm=SHA1&digits=6&period=30

These values are printed to each 2FA data record inside the application. Changes will only take effect for new activations.

> Do not edit these parameters if you plan to use publicly available Authenticator apps, since some of them **may not support non-standard configuration**, like more digits, different period of seconds or other algorithms.

### QR Code Configuration 

```php
return [
    'qr_code' => [
        'size' => 400,
        'margin' => 4
    ],
];
```

This controls the size and margin used to create the QR Code, which are created as SVG.

### Custom view

    resources/views/vendor/laraguard/auth.blade.php

You can override the view, which handles the Two Factor Code verification for the User. It receives this data:

* `$action`: The full URL where the form should send the login credentials.
* `$credentials`: An `array` containing the User credentials used for the login.
* `$user`: The User instance trying to authenticate.
* `$error`: If the Two Factor Code is invalid.
* `$remember`: If the "remember" checkbox has been filled.

The way it works is very simple: it will hold the User credentials in a hidden input while it asks for the Two Factor Code. The User will send everything again along with the Code, the application will ensure its correct, and complete the log in.

This view and its form is bypassed if the User doesn't uses Two Factor Authentication, making the login transparent and non-invasive.

## Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

Laravel is a Trademark of Taylor Otwell. Copyright © 2011-2020 Laravel LLC.
