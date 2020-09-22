<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\Folder;
use App\Mailbox;
use App\Thread;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Validator;

class MailboxesController extends Controller
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
     * Mailboxes list.
     */
    public function mailboxes()
    {
        //$this->authorize('create', 'App\Mailbox');
        //$mailboxes = Mailbox::all();
        $mailboxes = auth()->user()->mailboxesCanView();

        return view('mailboxes/mailboxes', ['mailboxes' => $mailboxes]);
    }

    /**
     * New mailbox.
     */
    public function create()
    {
        $this->authorize('create', 'App\Mailbox');

        $users = User::where('role', '!=', User::ROLE_ADMIN)->get();

        return view('mailboxes/create', ['users' => $users]);
    }

    /**
     * Create new mailbox.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function createSave(Request $request)
    {
        $this->authorize('create', 'App\Mailbox');

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:128|unique:mailboxes',
            'name'  => 'required|string|max:40',
        ]);

        // //event(new Registered($user = $this->create($request->all())));

        if ($validator->fails()) {
            return redirect()->route('mailboxes.create')
                        ->withErrors($validator)
                        ->withInput();
        }

        $mailbox = new Mailbox();
        $mailbox->fill($request->all());
        $mailbox->save();

        $mailbox->users()->sync($request->users);
        $mailbox->syncPersonalFolders($request->users);

        \Session::flash('flash_success_floating', __('Mailbox created successfully'));

        return redirect()->route('mailboxes.update', ['id' => $mailbox->id]);
    }

    /**
     * Edit mailbox.
     */
    public function update($id)
    {
        $mailbox = Mailbox::findOrFail($id);
        //$this->authorize('update', $mailbox);
        if (!auth()->user()->can('updateSettings', $mailbox)) {
            $accessible_route = \Eventy::filter('mailbox.accessible_settings_route', '', auth()->user(), $mailbox);
            if ($accessible_route) {
                return redirect()->route($accessible_route, ['id' => $mailbox->id]);
            } else {
                \Helper::denyAccess();
            }
        }

        $user = auth()->user();
        $mailbox_user = $user->mailboxesWithSettings()->where('mailbox_id', $id)->first();
        if (!$mailbox_user && $user->isAdmin()) {
            // Admin may not be connected to the mailbox yet
            $user->mailboxes()->attach($id);
            $mailbox_user = $user->mailboxesWithSettings()->where('mailbox_id', $id)->first();
        }

        //$mailboxes = Mailbox::all()->except($id);

        return view('mailboxes/update', ['mailbox' => $mailbox, 'mailbox_user' => $mailbox_user, 'flashes' => $this->mailboxActiveWarning($mailbox)]);
    }

    /**
     * Save mailbox.
     *
     * @param int                      $id
     * @param \Illuminate\Http\Request $request
     */
    public function updateSave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);

        $this->authorize('updateSettings', $mailbox);

        // if not admin, the text only fields don't pass so spike them into the request.
        if (!auth()->user()->isAdmin()) {
            $request->merge([
                'name' => $mailbox->name,
                'email' => $mailbox->email
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:40',
            'email'            => 'required|string|email|max:128|unique:mailboxes,email,'.$id,
            'aliases'          => 'nullable|string|max:255',
            'from_name'        => 'required|integer',
            'from_name_custom' => 'nullable|string|max:128',
            'ticket_status'    => 'required|integer',
            'template'         => 'required|integer',
            'ticket_assignee'  => 'required|integer',
        ]);

        //event(new Registered($user = $this->create($request->all())));

        if ($validator->fails()) {
            return redirect()->route('mailboxes.update', ['id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $mailbox->fill($request->all());

        $mailbox->save();

        \Session::flash('flash_success_floating', __('Mailbox settings saved'));

        return redirect()->route('mailboxes.update', ['id' => $id]);
    }

    /**
     * Mailbox permissions.
     */
    public function permissions($id)
    {
        $mailbox = Mailbox::findOrFail($id);

        $this->authorize('updatePermissions', $mailbox);

        $users = User::nonDeleted()->where('role', '!=', User::ROLE_ADMIN)->get();
        $users = User::sortUsers($users);

        $managers = User::nonDeleted()
            ->select(['users.*', 'mailbox_user.hide', 'mailbox_user.access'])
            ->leftJoin('mailbox_user', function ($join) use ($mailbox) {
                $join->on('mailbox_user.user_id', '=', 'users.id');
                $join->where('mailbox_user.mailbox_id', $mailbox->id);
            })->get();
        $managers = User::sortUsers($managers);

        return view('mailboxes/permissions', [
            'mailbox' => $mailbox,
            'users' => $users,
            'managers' => $managers,
            'mailbox_users' => $mailbox->users,
        ]);
    }

    /**
     * Save mailbox permissions.
     *
     * @param int                      $id
     * @param \Illuminate\Http\Request $request
     */
    public function permissionsSave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('updatePermissions', $mailbox);

        $mailbox->users()->sync($request->users);
        $mailbox->syncPersonalFolders($request->users);

        // Save admins settings.
        $admins = User::nonDeleted()->where('role', User::ROLE_ADMIN)->get();
        foreach ($admins as $admin) {
            $mailbox_user = $admin->mailboxesWithSettings()->where('mailbox_id', $id)->first();
            if (!$mailbox_user) {
                // Admin may not be connected to the mailbox yet
                $admin->mailboxes()->attach($id);
                $mailbox_user = $admin->mailboxesWithSettings()->where('mailbox_id', $id)->first();
            }
            $mailbox_user->settings->hide = (isset($request->managers[$admin->id]['hide']) ? (int)$request->managers[$admin->id]['hide'] : false);
            $mailbox_user->settings->save();
        }

        // Sets the mailbox_user.access JSON array
        $mailbox_users = $mailbox->users;
        foreach ($mailbox_users as $mailbox_user) {
            $access = Array();
            $mailbox_with_settings = $mailbox_user->mailboxesWithSettings()->where('mailbox_id', $id)->first();
            foreach (\App\Mailbox::$USER_ACCESS_PERMISSIONS as $label=>$perm) {
                if (!empty($request->managers[$mailbox_user->id]['access'][$perm])) $access[] = $request->managers[$mailbox_user->id]['access'][$perm];
            }
            $mailbox_with_settings->settings->access = json_encode($access);
            $mailbox_with_settings->settings->save();
        }

        \Session::flash('flash_success_floating', __('Mailbox permissions saved!'));

        return redirect()->route('mailboxes.permissions', ['id' => $id]);
    }

    /**
     * Mailbox connection settings.
     */
    public function connectionOutgoing($id)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('update', $mailbox);

        return view('mailboxes/connection', ['mailbox' => $mailbox, 'sendmail_path' => ini_get('sendmail_path'), 'flashes' => $this->mailboxActiveWarning($mailbox)]);
    }

    /**
     * Save mailbox connection settings.
     */
    public function connectionOutgoingSave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('update', $mailbox);

        if ($request->out_method == Mailbox::OUT_METHOD_SMTP) {
            $validator = Validator::make($request->all(), [
                'out_server'          => 'required|string|max:255',
                'out_port'            => 'required|integer',
                'out_username'        => 'nullable|string|max:100',
                'out_password'        => 'nullable|string|max:255',
                'out_encryption'      => 'required|integer',
            ]);

            if ($validator->fails()) {
                return redirect()->route('mailboxes.connection', ['id' => $id])
                            ->withErrors($validator)
                            ->withInput();
            }
        }

        // Do not save dummy password.
        if (preg_match("/^\*+$/", $request->out_password)) {
            $params = $request->except(['out_password']);
        } else {
            $params = $request->all();
        }
        $mailbox->fill($params);
        $mailbox->save();

        if (!empty($request->send_test_to)) {
            \Option::set('send_test_to', $request->send_test_to);
        }

        // Sometimes background job continues to use old connection settings.
        \Helper::queueWorkRestart();

        \Session::flash('flash_success_floating', __('Connection settings saved!'));

        return redirect()->route('mailboxes.connection', ['id' => $id]);
    }

    /**
     * Mailbox incoming settings.
     */
    public function connectionIncoming($id)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('update', $mailbox);

        $fields = [
            'in_server'   => $mailbox->in_server,
            'in_port'     => $mailbox->in_port,
            'in_username' => $mailbox->in_username,
            'in_password' => $mailbox->in_password,
        ];

        $validator = Validator::make($fields, [
            'in_server'   => 'required',
            'in_port'     => 'required',
            'in_username' => 'required',
            'in_password' => 'required',
        ]);

        return view('mailboxes/connection_incoming', ['mailbox' => $mailbox, 'flashes' => $this->mailboxActiveWarning($mailbox)])->withErrors($validator);
    }

    /**
     * Save mailbox connection settings.
     */
    public function connectionIncomingSave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('update', $mailbox);

        // $validator = Validator::make($request->all(), [
        //     'in_server'   => 'nullable|string|max:255',
        //     'in_port'     => 'nullable|integer',
        //     'in_username' => 'nullable|string|max:100',
        //     'in_password' => 'nullable|string|max:255',
        // ]);

        // if ($validator->fails()) {
        //     return redirect()->route('mailboxes.connection.incoming', ['id' => $id])
        //                 ->withErrors($validator)
        //                 ->withInput();
        // }

        // Checkboxes
        $request->merge([
            'in_validate_cert' => ($request->filled('in_validate_cert') ?? false),
        ]);

        // Do not save dummy password.
        if (preg_match("/^\*+$/", $request->in_password)) {
            $params = $request->except(['in_password']);
        } else {
            $params = $request->all();
        }

        $mailbox->fill($params);

        // Save IMAP Folders.
        // Save all custom folders except INBOX.
        $in_imap_folders = [];
        if (is_array($request->in_imap_folders)) {
            foreach ($request->in_imap_folders as $imap_folder) {
                $in_imap_folders[] = $imap_folder;
            }
        }
        $mailbox->setInImapFolders($in_imap_folders);

        $mailbox->save();

        \Session::flash('flash_success_floating', __('Connection settings saved!'));

        return redirect()->route('mailboxes.connection.incoming', ['id' => $id]);
    }

    /**
     * View mailbox.
     */
    public function view($id, $folder_id = null)
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            $mailbox = Mailbox::findOrFailWithSettings($id, $user->id);
        } else {
            $mailbox = Mailbox::findOrFail($id);
        }
        $this->authorize('view', $mailbox);

        $folders = $mailbox->getAssesibleFolders();

        $folder = null;
        if (!empty($folder_id)) {
            $folder = $folders->filter(function ($item) use ($folder_id) {
                return $item->id == $folder_id;
            })->first();
        }
        // By default we display Unassigned folder
        if (empty($folder)) {
            $folder = $folders->filter(function ($item) {
                return $item->type == Folder::TYPE_UNASSIGNED;
            })->first();
        }

        $this->authorize('view', $folder);

        $query_conversations = Conversation::getQueryByFolder($folder, $user->id);
        $conversations = $folder->queryAddOrderBy($query_conversations)->paginate(Conversation::DEFAULT_LIST_SIZE);

        return view('mailboxes/view', [
            'mailbox'       => $mailbox,
            'folders'       => $folders,
            'folder'        => $folder,
            'conversations' => $conversations,
        ]);
    }

    private function mailboxActiveWarning($mailbox)
    {
        $flashes = [];

        if ($mailbox) {
            if (Route::currentRouteName() != 'mailboxes.connection' && !$mailbox->isOutActive()) {
                $flashes[] = [
                    'type'      => 'warning',
                    'text'      => __('Sending emails need to be configured for the mailbox in order to send emails to customers and support agents').' ('.__('Connection Settings').' » <a href="'.route('mailboxes.connection', ['id' => $mailbox->id]).'">'.__('Sending Emails').'</a>)',
                    'unescaped' => true,
                ];
            }
            if (Route::currentRouteName() != 'mailboxes.connection.incoming' && !$mailbox->isInActive()) {
                $flashes[] = [
                    'type'      => 'warning',
                    'text'      => __('Receiving emails need to be configured for the mailbox in order to fetch emails from your support email address').' ('.__('Connection Settings').' » <a href="'.route('mailboxes.connection.incoming', ['id' => $mailbox->id]).'">'.__('Receiving Emails').'</a>)',
                    'unescaped' => true,
                ];
            }
        }

        return $flashes;
    }

    /**
     * Auto reply settings.
     */
    public function autoReply($id)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('updateAutoReply', $mailbox);

        if (!$mailbox->auto_reply_subject) {
            $mailbox->auto_reply_subject = 'Re: {%subject%}';
        }

        return view('mailboxes/auto_reply', [
            'mailbox' => $mailbox,
        ]);
    }

    /**
     * Save auto reply settings.
     */
    public function autoReplySave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);

//        $this->authorize('update', $mailbox);
        $this->authorize('updateAutoReply', $mailbox);

        $request->merge([
            'auto_reply_enabled'     => ($request->filled('auto_reply_enabled') ?? false),
        ]);

        if ($request->auto_reply_enabled) {
            $post = $request->all();
            $post['auto_reply_message'] = strip_tags($post['auto_reply_message']);
            $validator = Validator::make($post, [
                'auto_reply_subject' => 'required|string|max:128',
                'auto_reply_message' => 'required|string',
            ]);
            $validator->setAttributeNames([
                'auto_reply_subject' => __('Subject'),
                'auto_reply_message' => __('Message'),
            ]);

            if ($validator->fails()) {
                return redirect()->route('mailboxes.auto_reply', ['id' => $id])
                            ->withErrors($validator)
                            ->withInput();
            }
        }

        $mailbox->fill($request->all());

        $mailbox->save();

        \Session::flash('flash_success_floating', __('Auto Reply status saved'));

        return redirect()->route('mailboxes.auto_reply', ['id' => $id]);
    }

    /**
     * Auto reply settings.
     */
    public function emailSignature($id)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('updateAutoReply', $mailbox);

        if (!$mailbox->auto_reply_subject) {
            $mailbox->auto_reply_subject = 'Re: {%subject%}';
        }

        return view('mailboxes/email_signature', [
            'mailbox' => $mailbox,
        ]);
    }

    /**
     * Save auto reply settings.
     */
    public function emailSignatureSave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);

        $this->authorize('updateEmailSignature', $mailbox);

        $validator = Validator::make($request->all(), [
            'signature'        => 'nullable|string',
        ]);

        //event(new Registered($user = $this->create($request->all())));

        if ($validator->fails()) {
            return redirect()->route('mailboxes.email_signature', ['id' => $id])
                ->withErrors($validator)
                ->withInput();
        }

        $mailbox->fill($request->all());

        $mailbox->save();

        \Session::flash('flash_success_floating', __('Mailbox settings saved'));

        return redirect()->route('mailboxes.email_signature', ['id' => $id]);
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

        $user = auth()->user();

        switch ($request->action) {

            // Test sending emails from mailbox
            case 'send_test':
                $mailbox = Mailbox::find($request->mailbox_id);

                if (!$mailbox) {
                    $response['msg'] = __('Mailbox not found');
                } elseif (!$user->can('update', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                } elseif (empty($request->to)) {
                    $response['msg'] = __('Please specify recipient of the test email');
                }

                // Check if outgoing port is open.
                if (!$response['msg']) {
                    $test_result = \Helper::checkPort($mailbox->out_server, $mailbox->out_port);
                    if (!$test_result) {
                        $response['msg'] = __(':host is not available on :port port. Make sure that :host address is correct and that outgoing port :port on YOUR server is open.', ['host' => '<strong>'.$mailbox->out_server.'</strong>', 'port' => '<strong>'.$mailbox->out_port.'</strong>']);
                    }
                }

                if (!$response['msg']) {
                    $test_result = false;

                    try {
                        $test_result = \App\Misc\Mail::sendTestMail($request->to, $mailbox);
                    } catch (\Exception $e) {
                        $response['msg'] = $e->getMessage();
                    }

                    if (!$test_result && !$response['msg']) {
                        $response['msg'] = __('Error occurend sending email. Please check your mail server logs for more details.');
                    }
                }

                if (!$response['msg']) {
                    $response['status'] = 'success';
                }

                // Remember email address
                if (!empty($request->to)) {
                    \App\Option::set('send_test_to', $request->to);
                }
                break;

            // Test sending emails from mailbox
            case 'fetch_test':
                $mailbox = Mailbox::find($request->mailbox_id);

                if (!$mailbox) {
                    $response['msg'] = __('Mailbox not found');
                } elseif (!$user->can('update', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }

                // Check if outgoing port is open.
                if (!$response['msg']) {
                    $test_result = \Helper::checkPort($mailbox->in_server, $mailbox->in_port);
                    if (!$test_result) {
                        $response['msg'] = __(':host is not available on :port port. Make sure that :host address is correct and that outgoing port :port on YOUR server is open.', ['host' => '<strong>'.$mailbox->in_server.'</strong>', 'port' => '<strong>'.$mailbox->in_port.'</strong>']);
                    }
                }

                if (!$response['msg']) {
                    $test_result = false;

                    try {
                        $test_result = \MailHelper::fetchTest($mailbox);
                    } catch (\Exception $e) {
                        $response['msg'] = $e->getMessage();
                    }

                    if (!$test_result && !$response['msg']) {
                        $response['msg'] = __('Error occurend connecting to the server');
                    }
                }

                if (!$response['msg']) {
                    $response['status'] = 'success';
                }
                break;

            // Retrieve a list of available IMAP folders from server.
            case 'imap_folders':
                $mailbox = Mailbox::find($request->mailbox_id);

                if (!$mailbox) {
                    $response['msg'] = __('Mailbox not found');
                } elseif (!$user->can('update', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }

                $response['folders'] = [];

                if (!$response['msg']) {

                    try {
                        $client = \MailHelper::getMailboxClient($mailbox);
                        $client->connect();

                        $imap_folders = $client->getFolders();

                        if (count($imap_folders)) {
                            foreach ($imap_folders as $imap_folder) {
                                if (!empty($imap_folder->name)) {
                                    $response['folders'][] = $imap_folder->name;
                                }
                                // Maybe we need a recursion here.
                                if (!empty($imap_folder->children)) {
                                    foreach ($imap_folder->children as $child_imap_folder) {
                                        if (!empty($child_imap_folder->fullName)) {
                                            $response['folders'][] = $child_imap_folder->fullName;
                                        }
                                    }
                                }
                            }
                        }

                        if (count($response['folders'])) {
                            $response['msg_success'] = __('IMAP folders retrieved: '.implode(', ', $response['folders']));
                        } else {
                            $response['msg_success'] = __('Connected, but no IMAP folders found');
                        }

                    } catch (\Exception $e) {
                        $response['msg'] = $e->getMessage();
                    }
                }

                if (!$response['msg']) {
                    $response['status'] = 'success';
                }
                break;

            // Delete mailbox
            case 'delete_mailbox':
                $mailbox = Mailbox::find($request->mailbox_id);

                if (!$mailbox) {
                    $response['msg'] = __('Mailbox not found');
                } elseif (!$user->can('update', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                } elseif (!Hash::check($request->password, $user->password)) {
                    $response['msg'] = __('Please double check your password, and try again');
                }

                if (!$response['msg']) {

                    // Remove threads and conversations
                    Thread::whereIn('conversation_id', $mailbox->conversations()->pluck('id')->toArray())
                        ->delete();
                    $mailbox->conversations()->delete();
                    $mailbox->users()->sync([]);
                    $mailbox->folders()->delete();
                    // Maybe remove notifications on events in this mailbox?

                    $mailbox->delete();

                    \Session::flash('flash_success_floating', __('Mailbox deleted'));

                    $response['status'] = 'success';
                }
                break;

            // Mute notifications
            case 'mute':
                $mailbox = Mailbox::find($request->mailbox_id);

                if (!$mailbox) {
                    $response['msg'] = __('Mailbox not found');
                } elseif (!$user->isAdmin()) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {

                    $mailbox_user = $user->mailboxesWithSettings()->where('mailbox_id', $mailbox->id)->first();
                    if (!$mailbox_user) {
                        // User may not be connected to the mailbox yet
                        $user->mailboxes()->attach($id);
                        $mailbox_user = $user->mailboxesWithSettings()->where('mailbox_id', $mailbox->id)->first();
                    }
                    $mailbox_user->settings->mute = (bool)$request->mute;
                    $mailbox_user->settings->save();

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

}
