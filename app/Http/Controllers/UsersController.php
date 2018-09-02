<?php

namespace App\Http\Controllers;

use App\Mailbox;
use App\Subscription;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Validator;

class UsersController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Users list.
     */
    public function users()
    {
        $users = User::all();

        return view('users/users', ['users' => $users]);
    }

    /**
     * New user.
     */
    public function create()
    {
        $this->authorize('create', 'App\User');
        $mailboxes = Mailbox::all();

        return view('users/create', ['mailboxes' => $mailboxes]);
    }

    /**
     * Create new mailbox.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function createSave(Request $request)
    {
        $this->authorize('create', 'App\User');

        $rules = [
            'role'       => 'integer',
            'first_name' => 'required|string|max:20',
            'last_name'  => 'required|string|max:30',
            'email'      => 'required|string|email|max:100|unique:users',
            'role'       => ['required', Rule::in(array_keys(User::$roles))],
        ];
        if (empty($request->send_invite)) {
            $rules['password'] = 'required|string|max:255';
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('users.create')
                        ->withErrors($validator)
                        ->withInput();
        }

        $user = new User();
        $user->fill($request->all());
        if (!empty($request->send_invite)) {
            $password = $user->generatePassword();
        }
        $user->password = Hash::make($user->password);
        $user->save();

        $user->mailboxes()->sync($request->mailboxes);
        $user->syncPersonalFolders($request->mailboxes);

        // Send invite
        if (!empty($request->send_invite)) {
            try {
                $user->sendInvite(true);
            } catch (\Exception $e) {
                // Admin is allowed to see exceptions
                \Session::flash('flash_error_floating', $e->getMessage());
            }
        }

        \Session::flash('flash_success_floating', __('User created successfully'));

        return redirect()->route('users.profile', ['id' => $user->id]);
    }

    /**
     * User profile.
     */
    public function profile($id)
    {
        $user = User::findOrFail($id);

        $this->authorize('view', $user);

        $users = User::all()->except($id);

        return view('users/profile', ['user' => $user, 'users' => $users]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function profileSave($id, Request $request)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        // This is also present in PublicController::userSetup
        $validator = Validator::make($request->all(), [
            'first_name'  => 'required|string|max:20',
            'last_name'   => 'required|string|max:30',
            'email'       => 'required|string|email|max:100|unique:users,email,'.$id,
            'emails'      => 'max:100',
            'job_title'   => 'max:100',
            'phone'       => 'max:60',
            'timezone'    => 'required|string|max:255',
            'time_format' => 'required',
            'role'        => ['required', Rule::in(array_keys(User::$roles))],
        ]);

        //event(new Registered($user = $this->create($request->all())));

        if ($validator->fails()) {
            return redirect()->route('users.profile', ['id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $user->fill($request->all());

        if (empty($request->input('enable_kb_shortcuts'))) {
            $user->enable_kb_shortcuts = false;
        }

        $user->save();

        \Session::flash('flash_success_floating', __('Profile saved successfully'));

        return redirect()->route('users.profile', ['id' => $id]);
    }

    /**
     * User permissions.
     */
    public function permissions($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $mailboxes = Mailbox::all();

        return view('users/permissions', ['user' => $user, 'mailboxes' => $mailboxes, 'user_mailboxes' => $user->mailboxes]);
    }

    /**
     * Save user permissions.
     *
     * @param int                      $id
     * @param \Illuminate\Http\Request $request
     */
    public function permissionsSave($id, Request $request)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $user->mailboxes()->sync($request->mailboxes);
        $user->syncPersonalFolders($request->mailboxes);

        \Session::flash('flash_success', __('Permissions saved successfully'));

        return redirect()->route('users.permissions', ['id' => $id]);
    }

    /**
     * User notifications settings.
     */
    public function notifications($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $subscriptions = $user->subscriptions()->select('medium', 'event')->get();

        $person = '';
        if ($id != auth()->user()->id) {
            $person = $user->getFirstName(true);
        }

        return view('users/notifications', [
            'user'          => $user,
            'subscriptions' => $subscriptions,
            'person'        => $person,
        ]);
    }

    /**
     * Save user notifications settings.
     *
     * @param int                      $id
     * @param \Illuminate\Http\Request $request
     */
    public function notificationsSave($id, Request $request)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        Subscription::saveFromArray($request->subscriptions, $user->id);

        \Session::flash('flash_success', __('Notifications saved successfully'));

        return redirect()->route('users.notifications', ['id' => $id]);
    }

    /**
     * Users ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {

            // Both send and resend
            case 'send_invite':
                if (!auth()->user()->isAdmin()) {
                     $response['msg'] = __('Not enough permissions');
                }
                if (empty($request->user_id)) {
                     $response['msg'] = __('Incorrect user');
                }
                if (!$response['msg']) {
                    $user = User::find($request->user_id);
                    if (!$user) {
                        $response['msg'] = __('User not found');
                    } elseif ($user->invite_state == User::INVITE_STATE_ACTIVATED) {
                        $response['msg'] = __('User already accepted invitation');
                    }
                }

                if (!$response['msg']) {
                    try {
                        $user->sendInvite(true);

                        $response['status'] = 'success';
                    } catch (\Exception $e) {
                        // Admin is allowed to see exceptions
                        $response['msg'] = $e->getMessage();
                    }
                }
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
}
