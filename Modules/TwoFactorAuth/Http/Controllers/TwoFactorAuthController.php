<?php

namespace Modules\TwoFactorAuth\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Validator;

class TwoFactorAuthController extends Controller
{
    use AuthorizesRequests;

    public function userAuthSettings($id)
    {
        $user = User::findOrFail($id);
        if ($user->isDeleted()) {
            abort(404);
        }

        $auth_user = \Auth::user();
        if ($auth_user->id != $id && !$auth_user->isAdmin()) {
            \Helper::denyAccess();
        }

        $not_own_profile = ($auth_user->id != $id);

        $tfa_enabled = $user->hasTwoFactorEnabled();

        $tfa_required = false;
        if ((int)config('twofactorauth.required') && !$tfa_enabled && !$not_own_profile) {
            \Session::flash('flash_warning', __('You need to enable Two-Factor Authentication.'));
        }

        $users = $this->getUsersForSidebar($id);

        return view('twofactorauth::user_auth_settings', [
            'user'            => $user,
            'tfa_enabled'     => $tfa_enabled,
            'not_own_profile' => $not_own_profile,
            'users'           => $users,
        ]);
    }

    public function getUsersForSidebar($except_id)
    {
        if (auth()->user()->isAdmin()) {
            return User::sortUsers(User::nonDeleted()->get());/*->except($except_id)*/;
        } else {
            return [];
        }
    }

    public function userAuthSettingsSave($id, Request $request)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        if (!empty($request->tfa_enabled)) {
            if (!$user->hasTwoFactorEnabled()) {
                $user->enableTwoFactorAuth();
            }
        } else {
            if ($user->hasTwoFactorEnabled()) {
                $user->disableTwoFactorAuth();
            }
        }

        \Session::flash('flash_success_floating', __('Settings saved'));

        return redirect()->route('twofactorauth.user_auth_settings', ['id' => $id]);
    }

    public function userAuthSettingsConfirm($id)
    {
        $user = \Auth::user();
        if ($user->id != $id) {
            \Helper::denyAccess();
        }

        $secret = $user->createTwoFactorAuth();

        return view('twofactorauth::user_auth_settings_confirm', [
            'user'    => $user,
            'qr_code' => base64_encode($secret->toQr()),
            'qr_string' => $secret->toString(),
        ]);
    }

    public function userAuthSettingsConfirmSave(Request $request, $id)
    {
        $user = \Auth::user();
        if ($user->id != $id) {
            \Helper::denyAccess();
        }

        $rules = [
            //'tfa_confirm_code' => 'required|totp_code',
        ];

        $validator = Validator::make($request->all(), $rules);

        // if ($validator->fails()) {
        $activated = $user->confirmTwoFactorAuth(
            $request->input('tfa_confirm_code')
        );

        if (!$activated) {
            $validator->errors()->add('tfa_confirm_code', __('The code is invalid or has expired.').' '.__("Make sure your device's timezone is correct."));
            return redirect()->route('twofactorauth.user_auth_settings_confirm', ['id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $request->session()->put('2fa.totp_confirmed_at', now()->timestamp);
            return redirect()->away(route('twofactorauth.user_auth_settings', ['id' => $id]));
        }
    }

    /**
     * Ajax.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];
        
        $user = \Auth::user();

        switch ($request->action) {
            case 'new_codes':
                $user->generateRecoveryCodes();

                \Session::flash('flash_success_floating', __('New recovery codes generated'));

                $response['status'] = 'success';
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
        }

        return \Response::json($response);
    }

    /**
     * Ajax HTML controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {

            case 'view_codes':
                $user = User::find($request->param);
                if (!$user || \Auth::id() != $user->id) {
                    abort(404);
                }
                if (!$user->hasRecoveryCodes()) {
                    $user->generateRecoveryCodes();
                }
                $codes = $user->getRecoveryCodes();
                if (!$codes) {
                    $user->generateRecoveryCodes();
                }
                return view('twofactorauth::partials/view_codes', [
                    'codes' => $codes,
                ]);

            // case 'confim_modal':
            //     $user = User::find($request->param);
            //     if (!$user || \Auth::id() != $user->id) {
            //         abort(404);
            //     }
            //     $secret = $user->createTwoFactorAuth();

            //     return view('twofactorauth::partials/confim_modal', [
            //         'qr_code' => $secret->toQr(),
            //     ]);
        }

        abort(404);
    }
}
