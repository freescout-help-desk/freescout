<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\Folder;
use App\Mailbox;
use App\MailboxUser;
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
        $user = auth()->user();

        $mailboxes = $user->mailboxesCanView();

        if (!\Eventy::filter('user.can_view_mailbox_menu', false, $user)) {
            foreach ($mailboxes as $i => $mailbox) {
                if (!$user->canManageMailbox($mailbox->id)) {
                    $mailboxes->forget($i);
                }
            }
        }

        return view('mailboxes/mailboxes', ['mailboxes' => $mailboxes]);
    }

    /**
     * New mailbox.
     */
    public function create()
    {
        $this->authorize('create', 'App\Mailbox');

        $users = User::nonDeleted()->where('role', '!=', User::ROLE_ADMIN)->get();

        return view('mailboxes/create', ['users' => $users]);
    }

    /**
     * Create new mailbox.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function createSave(Request $request)
    {
        $invalid = false;

        $this->authorize('create', 'App\Mailbox');

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:128|unique:mailboxes',
            'name'  => 'required|string|max:40',
        ]);

        // //event(new Registered($user = $this->create($request->all())));

        if (Mailbox::userEmailExists($request->email)) {
            $invalid = true;
            $validator->errors()->add('email', __('There is a user with such email. Users and mailboxes can not have the same email addresses.'));
        }

        if ($invalid || $validator->fails()) {
            return redirect()->route('mailboxes.create')
                        ->withErrors($validator)
                        ->withInput();
        }

        $request_data = [
            'email'   => $request->email,
            'name'    => trim(strip_tags($request->name)),
            'ratings' => $request->ratings ?? 1,
        ];

        $mailbox = new Mailbox();
        $mailbox->fill($request_data);

        $mailbox->save();

        $mailbox->users()->sync($request->users ?: []);
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
        $user = auth()->user();

        // Redirect to the accessible page.
        if (!$user->can('updateSettings', $mailbox) && !$user->can('updateEmailSignature', $mailbox)) {
            $accessible_route = '';

            $mailbox_settings = $user->mailboxSettings($mailbox->id);
            
            if (!is_array($mailbox_settings->access)) {
                $access_permissions = json_decode($mailbox_settings->access ?? '');
            } else {
                $access_permissions = $mailbox_settings->access;
            }

            if ($access_permissions && is_array($access_permissions)) {
                foreach ($access_permissions as $perm) {
                    $accessible_route = Mailbox::getAccessPermissionRoute($perm);
                    if ($accessible_route) {
                        break;
                    }
                }
            }
            if (!$accessible_route) {
                $accessible_route = \Eventy::filter('mailbox.accessible_settings_route', '', auth()->user(), $mailbox);
            }
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
        $invalid = false;
        $mailbox = Mailbox::findOrFail($id);

        $user = auth()->user();

        $can_update_settings = $user->can('updateSettings', $mailbox);
        $can_update_signature = $user->can('updateEmailSignature', $mailbox);
        
        if (!$can_update_settings && !$can_update_signature) {
            \Helper::denyAccess();
        }

        $allowed_fields = [];

        if ($user->can('updateSettings', $mailbox)) {

            // Checkboxes
            $request->merge([
                'aliases_reply' => ($request->filled('aliases_reply') ?? false),
            ]);

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
                'aliases'          => 'nullable|string',
                'from_name'        => 'required|integer',
                'from_name_custom' => 'nullable|string|max:128',
                'ticket_status'    => 'required|integer',
                'template'         => 'required|integer',
                'ticket_assignee'  => 'required|integer',
            ]);

            //event(new Registered($user = $this->create($request->all())));
            if (Mailbox::userEmailExists($request->email)) {
                $invalid = true;
                $validator->errors()->add('email', __('There is a user with such email. Users and mailboxes can not have the same email addresses.'));
            }

            $validator = \Eventy::filter('mailbox.settings_validator', $validator, $mailbox, $request);

            if ($invalid || count($validator->errors()) || $validator->fails()) {
                return redirect()->route('mailboxes.update', ['id' => $id])
                            ->withErrors($validator)
                            ->withInput();
            }

            $allowed_fields = [
                'name',
                'email',
                'aliases',
                'aliases_reply',
                'auto_bcc',
                'from_name',
                'from_name_custom',
                'ticket_status',
                'ticket_assignee',
                'before_reply',
            ];
        }

        if ($can_update_signature) {
            $validator = Validator::make($request->all(), [
                'signature'        => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return redirect()->route('mailboxes.email_signature', ['id' => $id])
                    ->withErrors($validator)
                    ->withInput();
            }
            $allowed_fields[] = 'signature';
        }

        \Eventy::action('mailbox.settings_before_save', $mailbox, $request);

        // Leave only allowed fields.
        $fields = $request->all();
        foreach ($fields as $field_name => $value) {
            if (!in_array($field_name, $allowed_fields)) {
                unset($fields[$field_name]);
            }
        }

        $mailbox->fill($fields);

        // Chat: Start a new conversation when receiving a reply to the closed / deleted Chat conversation.
        if (!empty($request->chat_start_new)) {
            $mailbox->setMetaParam('chat_start_new', true);
        } else {
            $mailbox->removeMetaParam('chat_start_new');
        }

        $mailbox->signature = \Helper::stripDangerousTags($mailbox->signature);

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

        $user = auth()->user();
        $user_ids = \Eventy::filter('mailbox.permission_users', $request->users ?? [], $id);

        // Get pivot data from mailbox_user to pass to sync() not to loose values.
        // https://github.com/freescout-help-desk/freescout/issues/4339
        $users_with_settings = [];
        $mailbox_users = MailboxUser::where('mailbox_id', $id)
            //->whereIn('user_id', $user_ids)
            ->get()
            ->keyBy('user_id')
            ->toArray();
        foreach ($user_ids as $user_id) {
            $users_with_settings[$user_id] = $mailbox_users[$user_id] ?? [];
        }

        // Settings for admins are being reset here.
        // So we restore them below.
        $mailbox->users()->sync($users_with_settings);

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
            // Restore settings for admins.
            if (!empty($mailbox_users[$admin->id])) {
                $admin_settings = $mailbox_users[$admin->id];
                foreach (MailboxUser::$pivot_settings as $pivot_parameter) {
                    if (isset($admin_settings[$pivot_parameter])) {
                        $mailbox_user->settings->$pivot_parameter = $admin_settings[$pivot_parameter];
                    }
                }
            }
            $mailbox_user->settings->hide = (isset($request->managers[$admin->id]['hide']) ? (int)$request->managers[$admin->id]['hide'] : false);
            $mailbox_user->settings->save();
        }

        // Sets the mailbox_user.access array
        $mailbox_users = $mailbox->users;
        foreach ($mailbox_users as $mailbox_user) {
            $access = [];
            $mailbox_with_settings = $mailbox_user->mailboxesWithSettings()->where('mailbox_id', $id)->first();

            foreach (Mailbox::$access_permissions as $perm) {
                if (!empty($request->managers[$mailbox_user->id]['access'][$perm])) {
                    $access[] = $request->managers[$mailbox_user->id]['access'][$perm];
                }
            }

            if ($user->id == $mailbox_user->id && !$user->isAdmin()) {
                // User with Permission priv's can't edit their own additional priv's.
            } else {
                $mailbox_with_settings->settings->access = json_encode($access);
            }
            $mailbox_with_settings->settings->hide = (isset($request->managers[$mailbox_user->id]['hide']) ? (int)$request->managers[$mailbox_user->id]['hide'] : false);
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
        $this->authorize('admin', $mailbox);

        return view('mailboxes/connection', ['mailbox' => $mailbox, 'sendmail_path' => ini_get('sendmail_path'), 'flashes' => $this->mailboxActiveWarning($mailbox)]);
    }

    /**
     * Save mailbox connection settings.
     */
    public function connectionOutgoingSave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('admin', $mailbox);

        if ($request->out_method == Mailbox::OUT_METHOD_SMTP) {
            $validator = Validator::make($request->all(), [
                'out_server'          => 'required|string|max:255',
                'out_port'            => 'required|integer',
                'out_username'        => 'nullable|string|max:255',
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
        if (preg_match("/^\*+$/", $request->out_password ?? '')) {
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
        \Helper::queueWorkerRestart();

        \Session::flash('flash_success_floating', __('Connection settings saved!'));

        return redirect()->route('mailboxes.connection', ['id' => $id]);
    }

    /**
     * Mailbox incoming settings.
     */
    public function connectionIncoming($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('admin', $mailbox);

        $fields = [
            'in_server'   => $mailbox->in_server ?? '',
            'in_port'     => $mailbox->in_port ?? '',
            'in_username' => $mailbox->in_username ?? '',
            'in_password' => $mailbox->in_password ?? '',
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
        $this->authorize('admin', $mailbox);

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
        if (preg_match("/^\*+$/", $request->in_password ?? '')) {
            $params = $request->except(['in_password']);
        } else {
            $params = $request->all();
        }

        \Eventy::action('mailbox.incoming_settings_before_save', $mailbox, $request);

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
    public function view(Request $request, $id, $folder_id = null)
    {
        $user = auth()->user();

        $mailbox = Mailbox::findOrFailWithSettings($id, $user->id);
        $this->authorize('viewCached', $mailbox);

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
        $conversations = $folder->queryAddOrderBy($query_conversations)->paginate(
            Conversation::DEFAULT_LIST_SIZE, ['*'], 'page', $request->get('page')
        );

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

        if ($mailbox && \Auth::user()->can('admin', $mailbox)) {
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
                    'text'      => __('Receiving emails need to be configured for the mailbox in order to fetch emails from your support email address').' ('.__('Connection Settings').' » <a href="'.route('mailboxes.connection.incoming', ['id' => $mailbox->id]).'">'.__('Fetching Emails').'</a>)',
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

        $data = [
            'auto_reply_enabled' => $request->auto_reply_enabled,
            'auto_reply_subject' => $request->auto_reply_subject,
            'auto_reply_message' => $request->auto_reply_message,
        ];

        $mailbox->fill($data);
        $mailbox->auto_reply_message = \Helper::stripDangerousTags($mailbox->auto_reply_message);

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
                } elseif (!$user->can('admin', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                } elseif (empty($request->to)) {
                    $response['msg'] = __('Please specify recipient of the test email');
                }

                // Check if outgoing port is open.
                if (!$response['msg'] && $mailbox->out_method == Mailbox::OUT_METHOD_SMTP) {
                    $test_result = \Helper::checkPort($mailbox->out_server, $mailbox->out_port);
                    if (!$test_result) {
                        $response['msg'] = __(':host is not available on :port port. Make sure that :host address is correct and that outgoing port :port on YOUR server is open.', ['host' => '<strong>'.$mailbox->out_server.'</strong>', 'port' => '<strong>'.$mailbox->out_port.'</strong>']);
                    }
                }

                if (!$response['msg']) {
                    $test_result = [
                        'status' => 'error'
                    ];

                    try {
                        $test_result = \MailHelper::sendTestMail($request->to, $mailbox);
                    } catch (\Exception $e) {
                        $test_result['msg'] = $e->getMessage();
                    }

                    if ($test_result['status'] == 'error') {
                        $response['msg'] = $test_result['msg']
                            ?: __('Error occurred sending email. Please check your mail server logs for more details.');
                    }

                    $response['log'] = $test_result['log'] ?? '';
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
                } elseif (!$user->can('admin', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }

                $response = \Eventy::filter('mailbox.fetch_test', $response, $mailbox);

                $tested = (isset($response['tested']) && $response['tested'] === true);

                // Check if outgoing port is open.
                if (!$response['msg'] && !$tested) {
                    $test_result = \Helper::checkPort($mailbox->in_server, $mailbox->in_port);
                    if (!$test_result) {
                        $response['msg'] = __(':host is not available on :port port. Make sure that :host address is correct and that outgoing port :port on YOUR server is open.', ['host' => '<strong>'.$mailbox->in_server.'</strong>', 'port' => '<strong>'.$mailbox->in_port.'</strong>']);
                    }
                }

                if (!$response['msg'] && !$tested) {
                    $test_result = false;

                    $test_result = \MailHelper::fetchTest($mailbox);

                    $response['log'] = $test_result['log'] ?? '';

                    if ($test_result['result'] != 'success' && $test_result['msg']) {
                        $response['msg'] = $test_result['msg'];
                    }
                    if ($test_result['result'] != 'success' && !$response['msg']) {
                        $response['msg'] = __('Error occurred connecting to the server');
                    }
                }

                if (!$response['msg'] && !$tested) {
                    $response['status'] = 'success';
                }
                break;

            // Retrieve a list of available IMAP folders from server.
            case 'imap_folders':
                $mailbox = Mailbox::find($request->mailbox_id);

                if (!$mailbox) {
                    $response['msg'] = __('Mailbox not found');
                } elseif (!$user->can('admin', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }

                $response['folders'] = [];

                if (!$response['msg']) {

                    try {
                        $client = \MailHelper::getMailboxClient($mailbox);
                        $client->connect();

                        $imap_folders = $client->getFolders();

                        $response['folders'] = [];

                        if (count($imap_folders)) {
                            $response = $this->interateFolders($response, $imap_folders);
                            $response['folders'] = array_values(array_unique($response['folders']));
                        }

                        if (count($response['folders'])) {

                            // https://github.com/freescout-helpdesk/freescout/issues/3933
                            // Exclude duplicate INBOX.Name and Name folders.
                            // $folder_excluded = false;
                            // foreach ($response['folders'] as $i => $folder_name) {
                            //     if (\Str::startsWith($folder_name, 'INBOX.')) {
                            //         foreach ($response['folders'] as $folder_name2) {
                            //             if ($folder_name != $folder_name2 && $folder_name == 'INBOX.'.$folder_name2) {
                            //                 unset($response['folders'][$i]);
                            //                 $folder_excluded = true;
                            //                 continue 2;
                            //             }
                            //         }
                            //     }
                            // }
                            // if ($folder_excluded) {
                            //     $response['folders'] = array_values($response['folders']);
                            // }

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
                } elseif (!$user->can('admin', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                } elseif (!$user->isDummyPassword() && !Hash::check($request->password ?? '', $user->password)) {
                    $response['msg'] = __('Please double check your password, and try again');
                }

                if (!$response['msg']) {

                    // Remove threads and conversations.
                    $conversation_ids = $mailbox->conversations()->pluck('id')->toArray();
                    
                    for ($i=0; $i < ceil(count($conversation_ids) / \Helper::IN_LIMIT); $i++) { 
                        $slice_ids = array_slice($conversation_ids, $i*\Helper::IN_LIMIT, \Helper::IN_LIMIT);
                        Thread::whereIn('conversation_id', $slice_ids)->delete();
                    }

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
                if (!$user) {
                    \Helper::denyAccess();
                }

                $mailbox = Mailbox::find($request->mailbox_id);

                if (!$mailbox) {
                    $response['msg'] = __('Mailbox not found');
                } elseif (!$user->can('view', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {

                    $mailbox_user = $user->mailboxesWithSettings()->where('mailbox_id', $mailbox->id)->first();
                    if (!$mailbox_user && $user->isAdmin()) {
                        // Admin may not be connected to the mailbox yet
                        $user->mailboxes()->attach($mailbox->id);
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
            $response['msg'] = 'Unknown error occurred';
        }

        return \Response::json($response);
    }

    // Recursively interate over folders.
    public function interateFolders($response, $imap_folders, $subfolder = false) {
        foreach ($imap_folders as $imap_folder) {
            if (!empty($imap_folder->name) && !$subfolder) {
                $response['folders'][] = $imap_folder->name;
            }

            // Check for children and recurse.
            if (!empty($imap_folder->children)) {
                $response = $this->interateFolders($response, $imap_folder->children, true);
            }

            // Old library.
            if (!empty($imap_folder->fullName)) {
                $response['folders'][] = $imap_folder->fullName;
            } elseif (!empty($imap_folder->full_name)) {
                // New library.
                $response['folders'][] = $imap_folder->full_name;
            }
        }

        return $response;
    }

    public function oauth(Request $request)
    {
        $mailbox_id = $request->id ?? '';
        $provider = $request->provider ?? '';
        $in_out = $request->in_out ?? 'in';
        
        $state_data = [];
        if (!empty($request->state)) {
            $state_data = json_decode($request->state, true);
            if (!empty($state_data['mailbox_id'])) {
                $mailbox_id = $state_data['mailbox_id'];
            }
            if (!empty($state_data['provider'])) {
                $provider = $state_data['provider'];
            }
            if (!empty($state_data['in_out'])) {
                $in_out = $state_data['in_out'];
            }
        }

        // MS Exchange.
        if (!empty($request->error) && $request->error == 'invalid_request' && !empty($request->error_description)) {
            return htmlspecialchars($request->error_description);
        }

        if (empty($provider)) {
            return 'Invalid oAuth Provider';
        }

        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorize('admin', $mailbox);

        if (empty($mailbox)) {
            return __('Mailbox not found').': '.$mailbox_id;
        }
        if ($in_out == 'in') {
            $username = $mailbox->in_username;
            $password = $mailbox->in_password;
        } else {
            $username = $mailbox->out_username;
            $password = $mailbox->out_password;
        }
        if (empty($username)) {
            return 'Enter oAuth Client ID as Username and save mailbox settings';
        }
        if (empty($password)) {
            return 'Enter oAuth Client Secret as Password and save mailbox settings';
        }

        $session_data = [];
        if (\Session::get('mailbox_oauth_'.$provider.'_'.$mailbox_id)) {
            $session_data = \Session::get('mailbox_oauth_'.$provider.'_'.$mailbox_id);
        }

        if (empty($request->code)) {
            // Start.
            $state = [
                'provider' => $provider,
                'mailbox_id' => $mailbox_id,
                'in_out' => $in_out,
                'state' => crc32($username.$password),
            ];
            $url = \MailHelper::oauthGetAuthorizationUrl(\MailHelper::OAUTH_PROVIDER_MICROSOFT, [
                'state' => json_encode($state),
                'client_id' => $username,
            ]);
            if ($url) {
                \Session::put('mailbox_oauth_'.$provider.'_'.$mailbox_id, $state);
                //     [
                //     'provider' => $request->provider,
                //     'mailbox_id' => $request->mailbox_id,
                //     'state' => $provider->getState(),
                // ]);
                return redirect()->away($url);
            } else {
                return 'Could not generate authorization URL: check Client ID (Username) and Client Secret (Password)';
            }

        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($request->state) || ($state_data['state'] ?? '') !== ($session_data['state'] ?? '')) {
            
            \Session::forget('mailbox_oauth_'.$provider.'_'.$mailbox_id);
            return 'Invalid oAuth state';

        } else {
            // state is set.
            // Try to get an access token (using the authorization code grant)
            $token_data = \MailHelper::oauthGetAccessToken(\MailHelper::OAUTH_PROVIDER_MICROSOFT, [
                'client_id' => $username,
                'client_secret' => $password,
                'code' => $request->code,
            ]);

            if (!empty($token_data['a_token'])) {
                // Set username and password for the oppozite in_out.
                if ($in_out == 'in') {
                    if (empty($mailbox->out_server) 
                        || (trim($mailbox->out_server) == \MailHelper::OAUTH_MICROSOFT_SMTP 
                            && (!$mailbox->out_username || $mailbox->out_username == $username))
                    ) {
                        $mailbox->out_username = $username;
                        $mailbox->out_password = $password;
                    }
                    //$mailbox->out_method = Mailbox::OUT_METHOD_SMTP;
                } else {
                    $mailbox->in_username = $username;
                    $mailbox->in_password = $password;
                }
                $mailbox->setMetaParam('oauth', $token_data, true);
            } elseif (!empty($token_data['error'])) {
                return __('Error occurred').': '.htmlspecialchars($token_data['error']);
            }

            if ($in_out == 'in') {
                $route = 'mailboxes.connection.incoming';
            } else {
                $route = 'mailboxes.connection';
            }
            return redirect()->route($route, ['id' => $mailbox_id]);
        }
    }

    public function oauthDisconnect(Request $request)
    {
        $mailbox_id = $request->id ?? '';
        $provider = $request->provider ?? '';
        $in_out = $request->in_out ?? 'in';

        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorize('admin', $mailbox);
        
        // oAuth Disconnect.
        $mailbox->removeMetaParam('oauth', true);

        if ($in_out == 'in') {
            $route = 'mailboxes.connection.incoming';
        } else {
            $route = 'mailboxes.connection';
        }

        return \MailHelper::oauthDisconnect($provider, route($route, ['id' => $mailbox_id]));
    }
}
