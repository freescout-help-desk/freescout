<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\Folder;
use App\Mailbox;
use App\User;
use Illuminate\Http\Request;
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
        $mailboxes = Mailbox::all();

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
        $this->authorize('update', $mailbox);

        $mailboxes = Mailbox::all()->except($id);

        return view('mailboxes/update', ['mailbox' => $mailbox, 'mailboxes' => $mailboxes, 'flashes' => $this->mailboxActiveWarning($mailbox)]);
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

        $this->authorize('update', $mailbox);

        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:40',
            'email'            => 'required|string|email|max:128|unique:mailboxes,email,'.$id,
            'aliases'          => 'string|max:255',
            'from_name'        => 'required|integer',
            'from_name_custom' => 'nullable|string|max:128',
            'ticket_status'    => 'required|integer',
            'template'         => 'required|integer',
            'ticket_assignee'  => 'required|integer',
            'signature'        => 'nullable|string',
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

        $this->authorize('update', $mailbox);

        $users = User::where('role', '!=', User::ROLE_ADMIN)->get();

        return view('mailboxes/permissions', ['mailbox' => $mailbox, 'users' => $users, 'mailbox_users' => $mailbox->users]);
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
        $this->authorize('update', $mailbox);

        $mailbox->users()->sync($request->users);
        $mailbox->syncPersonalFolders($request->users);

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
                'out_username'        => 'required|string|max:100',
                'out_password'        => 'required|string|max:255',
                'out_encryption'      => 'required|integer',
            ]);

            if ($validator->fails()) {
                return redirect()->route('mailboxes.connection', ['id' => $id])
                            ->withErrors($validator)
                            ->withInput();
            }
        }

        $mailbox->fill($request->all());
        $mailbox->save();

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

        return view('mailboxes/connection_incoming', ['mailbox' => $mailbox, 'flashes' => $this->mailboxActiveWarning($mailbox)]);
    }

    /**
     * Save mailbox connection settings.
     */
    public function connectionIncomingSave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('update', $mailbox);

        $validator = Validator::make($request->all(), [
            'in_server'   => 'required|string|max:255',
            'in_port'     => 'required|integer',
            'in_username' => 'required|string|max:100',
            'in_password' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('mailboxes.connection.incoming', ['id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $mailbox->fill($request->all());
        $mailbox->save();

        \Session::flash('flash_success_floating', __('Connection settings saved!'));

        return redirect()->route('mailboxes.connection.incoming', ['id' => $id]);
    }

    /**
     * View mailbox.
     */
    public function view($id, $folder_id = null)
    {
        //auth()->guard()->attempt(['email' => '', 'password' => '']);

        $mailbox = Mailbox::findOrFail($id);
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

        $user = auth()->user();

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
                    'text'      => __('Sending emails need to be configured for the mailbox in order to send emails to customers and support agents').' (<a href="'.route('mailboxes.connection', ['id' => $mailbox->id]).'">'.__('Connection Settings » Sending Emails').'</a>)',
                    'unescaped' => true,
                ];
            }
            if (Route::currentRouteName() != 'mailboxes.connection.incoming' && !$mailbox->isInActive()) {
                $flashes[] = [
                    'type'      => 'warning',
                    'text'      => __('Receiving emails need to be configured for the mailbox in order to fetch emails from your support email address').' (<a href="'.route('mailboxes.connection.incoming', ['id' => $mailbox->id]).'">'.__('Connection Settings » Receiving Emails').'</a>)',
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
        $this->authorize('update', $mailbox);

        if (!$mailbox->auto_reply_subject) {
            $mailbox->auto_reply_subject = 'Re: {%subject%}';
        }

        return view('mailboxes/auto_reply', [
            'mailbox' => $mailbox
        ]);
    }

    /**
     * Save auto reply settings.
     */
    public function autoReplySave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);

        $this->authorize('update', $mailbox);

        if ($request->auto_reply_enabled) {
            $post = $request->all();
            $post['auto_reply_message'] = strip_tags($post['auto_reply_message']);
            $validator = Validator::make($post, [
                'auto_reply_subject' => 'required|string|max:128',
                'auto_reply_message' => 'required|string',
            ]);
            $validator->setAttributeNames([
                'auto_reply_subject' => __('Subject'),
                'auto_reply_message' => __('Message')
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
                    $response['msg'] = __('Conversation not found');
                } elseif (!$user->can('update', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                } elseif (empty($request->to)) {
                    $response['msg'] = __('Please specify recipient of the test email');
                }

                if (!$response['msg']) {
                    $test_result = false;

                    try {
                        $test_result = \App\Misc\Mail::sendTestMail($mailbox, $request->to);
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
