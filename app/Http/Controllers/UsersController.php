<?php

namespace App\Http\Controllers;

use App\Events\UserDeleted;
use App\Folder;
use App\Mailbox;
use App\Subscription;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
        $this->authorize('create', 'App\User');

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
     * Create new user.
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
        if (empty($request->send_invite)) {
            // Set password from request
            $user->password = Hash::make($request->password);
        } else {
            // Set some random password before sending invite
            $user->password = Hash::make($user->generateRandomPassword());
        }
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
            'role'        => ['nullable', Rule::in(array_keys(User::$roles))],
            'photo_url'   => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);
        $validator->setAttributeNames([
            'photo_url'   => __('Photo'),
        ]);

        // Photo
        $validator->after(function ($validator) use ($user, $request) {
            if ($request->hasFile('photo_url')) {
                $path_url = $user->savePhoto($request->file('photo_url'));

                if ($path_url) {
                    $user->photo_url = $path_url;
                } else {
                    $validator->errors()->add('photo_url', __('Error occured processing the image. Make sure that PHP GD extension is enabled.'));
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->route('users.profile', ['id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $request_data = $request->all();

        if (isset($request_data['photo_url'])) {
            unset($request_data['photo_url']);
        }
        if (!auth()->user()->can('changeRole', $user)) {
            unset($request_data['role']);
        } else {
            // Do not allow to remove last administrator
        }
        $user->fill($request_data);

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
        $user = auth()->user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $user = User::findOrFail($id);

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
        $user = auth()->user();
        if (!$user->isAdmin()) {
            abort(403);
        }
        
        $user = User::findOrFail($id);

        $user->mailboxes()->sync($request->mailboxes);
        $user->syncPersonalFolders($request->mailboxes);

        \Session::flash('flash_success_floating', __('Permissions saved successfully'));

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

        \Session::flash('flash_success_floating', __('Notifications saved successfully'));

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

        $auth_user = auth()->user();

        switch ($request->action) {

            // Both send and resend
            case 'send_invite':
                if (!$auth_user->isAdmin()) {
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

            // Reset password
            case 'reset_password':
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
                    }
                }

                if (!$response['msg']) {
                    $reset_result = Password::broker()->sendResetLink(
                        //['id' => $request->user_id]
                        ['id' => $request->user_id]
                    );

                    if ($reset_result == Password::RESET_LINK_SENT) {
                        $response['status'] = 'success';
                        $response['success_msg'] = __('Password reset email has been sent');
                    }
                }
                break;

            // Load website notifications
            case 'web_notifications':
                if (!$auth_user) {
                     $response['msg'] = __('You are not logged in');
                }
                if (!$response['msg']) {
                    $web_notifications_info = $auth_user->getWebsiteNotificationsInfo(false);
                    $response['html'] = view('users/partials/web_notifications', [
                        'web_notifications_info_data' => $web_notifications_info['data'],
                    ])->render();

                    $response['has_more_pages'] = (int)$web_notifications_info['notifications']->hasMorePages();
                    
                    $response['status'] = 'success';
                }
                break;

            // Mark all user website notifications as read
            case 'mark_notifications_as_read':
                if (!$auth_user) {
                     $response['msg'] = __('You are not logged in');
                }
                if (!$response['msg']) {
                    $auth_user->unreadNotifications()->update(['read_at' => now()]);
                    $auth_user->clearWebsiteNotificationsCache();

                    $response['status'] = 'success';
                }
                break;

            // Delete user photo
            case 'delete_photo':
                $user = User::find($request->user_id);

                if (!$user) {
                    $response['msg'] = __('User not found');
                } elseif (!$auth_user->can('update', $user)) {
                    $response['msg'] = __('Not enough permissions');
                }
                if (!$response['msg']) {
                    $user->removePhoto();
                    $user->save();

                    $response['status'] = 'success';
                }
                break;

            // Delete user
            case 'delete_user':
                $user = User::find($request->user_id);

                if (!$user) {
                    $response['msg'] = __('User not found');
                } elseif (!$auth_user->can('delete', $user)) {
                    $response['msg'] = __('Not enough permissions');
                }

                // Check if the user is the only one admin
                if (!$response['msg'] && $user->isAdmin()) {
                    $admins_count = User::where('role', User::ROLE_ADMIN)->count();
                    if ($admins_count < 2) {
                        $response['msg'] = __('Administrator can not be deleted');
                    }
                }

                if (!$response['msg']) {
                    
                    event(new UserDeleted($user, $auth_user));

                    // We have to process conversations one by one to set move them to Unassigned folder,
                    // as conversations may be in different mailboxes
                    // $user->conversations()->update(['user_id' => null, 'folder_id' => ]);
                    $mailbox_unassigned_folders = [];
                    $user->conversations->each(function ($conversation) {
                        // We don't fire ConversationUserChanged event to avoid sending notifications to users
                        $conversation->user_id = null;

                        $folder_id = null;
                        if (!empty($mailbox_unassigned_folders[$conversation->mailbox_id])) {
                            $folder_id = $mailbox_unassigned_folders[$conversation->mailbox_id];
                        } else {
                            $folder = $conversation->mailbox->folders()
                                ->where('type', Folder::TYPE_UNASSIGNED)
                                ->first();

                            if ($folder) {
                                $folder_id = $folder->id;
                                $mailbox_unassigned_folders[$conversation->mailbox_id] = $folder_id;
                            }
                        }
                        if ($folder_id) {
                            $conversation->folder_id = $folder_id;
                        }
                        $conversation->save();
                    });

                    // Recalculate counters for folders
                    if ($user->isAdmin()) {
                        // Admin has access to all mailboxes
                        Mailbox::all()->each(function ($mailbox) {
                            $mailbox->updateFoldersCounters();
                        });
                    } else {
                        $user->mailboxes->each(function ($mailbox) {
                            $mailbox->updateFoldersCounters();
                        });
                    }

                    $user->mailboxes()->sync([]);
                    $user->folders()->delete();
                    $user->delete();

                    \Session::flash('flash_success_floating', __('User deleted'));

                    $response['status'] = 'success';
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

    /**
     * Change user password.
     */
    public function password($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $users = User::all()->except($id);

        return view('users/password', ['user' => $user, 'users' => $users]);
    }

    /**
     * Save changed user password.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function passwordSave($id, Request $request)
    {
        // It is allowed to edit only your own password
        $user = auth()->user();
        if ($user->id != $id) {
            abort(403);
        }

        // This is also present in PublicController::userSetup
        $validator = Validator::make($request->all(), [
            'password_current' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $validator->after(function ($validator) use ($user, $request) {
            // Check current password
            if (!Hash::check($request->password_current, $user->password)) {
                $validator->errors()->add('password_current', __('This password is incorrect.'));
            } else if (Hash::check($request->password, $user->password)) {
                // Check new password
                $validator->errors()->add('password', __('The new password is the same as the old password.'));
            }
        });

        if ($validator->fails()) {
            return redirect()->route('users.password', ['id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $user->password = bcrypt($request->password);
        $user->save();

        $user->sendPasswordChanged();

        \Session::flash('flash_success_floating', __('Password saved successfully!'));

        return redirect()->route('users.profile', ['id' => $id]);
    }
}
