<?php

namespace DarkGhostHunter\Laraguard\Http\Controllers;

use Illuminate\Routing\Controller;

class Confirm2FACodeController extends Controller
{
    use Confirms2FACode;

    /*
    |--------------------------------------------------------------------------
    | Confirm Two Factor Code Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the 2FA TOTP code confirmation automatically.
    | Instead of copying this controller you can create your own using the
    | "Confirms2FACode" trait to modify how to confirm the 2FA TOTP code.
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth']);
    }
}