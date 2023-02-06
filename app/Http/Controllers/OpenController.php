<?php

namespace App\Http\Controllers;

use App\Attachment;
use App\Conversation;
use App\Option;
use App\Thread;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class OpenController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Setup user from invitation.
     *
     * @return \Illuminate\Http\Response
     */
    public function userSetup($hash)
    {
        if (auth()->user()) {
            return redirect()->route('dashboard');
        }
        $user = User::where('invite_hash', $hash)->first();

        if ($user && $user->locale) {
            \Helper::setLocale($user->locale);
        }

        return view('open/user_setup', ['user' => $user]);
    }

    /**
     * Save user from invitation.
     */
    public function userSetupSave($hash, Request $request)
    {
        if (auth()->user()) {
            return redirect()->route('dashboard');
        }
        $user = User::where('invite_hash', $hash)->first();

        if (!$user) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'email'       => 'required|string|email|max:100|unique:users,email,'.$user->id,
            'password'    => 'required|string|min:8|confirmed',
            'job_title'   => 'max:100',
            'phone'       => 'max:60',
            'timezone'    => 'required|string|max:255',
            'time_format' => 'required',
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
                    $validator->errors()->add('photo_url', __('Error occurred processing the image. Make sure that PHP GD extension is enabled.'));
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->route('user_setup', ['hash' => $hash])
                        ->withErrors($validator)
                        ->withInput();
        }

        $request_data = $request->all();
        // Do not allow user to set his role
        if (isset($request_data['role'])) {
            unset($request_data['role']);
        }
        if (isset($request_data['photo_url'])) {
            unset($request_data['photo_url']);
        }
        $user->fill($request_data);

        $user->password = bcrypt($request->password);

        $user->invite_state = User::INVITE_STATE_ACTIVATED;
        $user->invite_hash = '';
        
        $user = \Eventy::filter('user.setup_save', $user, $request);
        $user->save();

        // Login user
        Auth::guard()->login($user);

        \Session::flash('flash_success_floating', __('Welcome to :company_name!', ['company_name' => Option::getCompanyName()]));

        return redirect()->route('dashboard');
    }

    /*
     * Set a thread as read by customer
     */
    public function setThreadAsRead($conversation_id, $thread_id)
    {
        $conversation = Conversation::findOrFail($conversation_id);
        $thread = Thread::findOrFail($thread_id);

        // We only track the first opening
        if (empty($thread->opened_at)) {
            $thread->opened_at = date('Y-m-d H:i:s');
            $thread->save();
            \Eventy::action('thread.opened', $thread, $conversation);
        }

        // Create a 1x1 ttransparent pixel and return it
        $pixel = sprintf('%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c', 71, 73, 70, 56, 57, 97, 1, 0, 1, 0, 128, 255, 0, 192, 192, 192, 0, 0, 0, 33, 249, 4, 1, 0, 0, 0, 0, 44, 0, 0, 0, 0, 1, 0, 1, 0, 0, 2, 2, 68, 1, 0, 59);
        $response = \Response::make($pixel, 200);
        $response->header('Content-type', 'image/gif');
        $response->header('Content-Length', 42);
        $response->header('Cache-Control', 'private, no-cache, no-cache=Set-Cookie, proxy-revalidate');
        $response->header('Expires', 'Wed, 11 Jan 2000 12:59:00 GMT');
        $response->header('Last-Modified', 'Wed, 11 Jan 2006 12:59:00 GMT');
        $response->header('Pragma', 'no-cache');

        return $response;
    }

    /**
     * Download an attachment.
     */
    public function downloadAttachment($dir_1, $dir_2, $dir_3, $file_name, Request $request)
    {
        $id = $request->query('id', '');
        $token = $request->query('token', '');
        $attachment = null;

        // Old attachments can not be requested by id.
        if (!$token && $id) {
            return \Helper::denyAccess();
        }

        // Get attachment by id.
        if ($id) {
            $attachment = Attachment::findOrFail($id);
        }
        
        if (!$attachment) {
            $attachment = Attachment::where('file_dir', $dir_1.DIRECTORY_SEPARATOR.$dir_2.DIRECTORY_SEPARATOR.$dir_3.DIRECTORY_SEPARATOR)
                ->where('file_name', $file_name)
                ->firstOrFail();
        }

        // Only allow download if the attachment is public or if the token matches the hash of the contents
        if ($token != $attachment->getToken() && (bool)$attachment->public !== true) {
            return \Helper::denyAccess();
        }

        $view_attachment = false;
        $file_ext = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));

        // Some file type should be viewed in the browser instead of downloading.
        if (in_array($file_ext, config('app.viewable_attachments'))) {
            $view_attachment = true;
        }
        // If HTML file is renamed into .txt for example it will be shown by the browser as HTML.
        if ($view_attachment && $attachment->mime_type) {
            $allowed_mime_type = false;

            foreach (config('app.viewable_mime_types') as $mime_type) {
                if (preg_match('#'.$mime_type.'#', $attachment->mime_type)) {
                    $allowed_mime_type = true;
                    break;
                }
            }
            if (!$allowed_mime_type) {
                $view_attachment = false;
            }
        }

        if (config('app.download_attachments_via') == 'apache') {
            // Send using Apache mod_xsendfile.
            $response = response(null)
               ->header('Content-Type' , $attachment->mime_type)
               ->header('X-Sendfile', $attachment->getLocalFilePath());

            if (!$view_attachment) {
                $response->header('Content-Disposition', 'attachment; filename="'.$attachment->file_name.'"');
            }
        } elseif (config('app.download_attachments_via') == 'nginx') {
            // Send using Nginx.
            $response = response(null)
               ->header('Content-Type' , $attachment->mime_type)
               ->header('X-Accel-Redirect', $attachment->getLocalFilePath(false));
               
            if (!$view_attachment) {
                $response->header('Content-Disposition', 'attachment; filename="'.$attachment->file_name.'"');
            }
        } else {
            $response = $attachment->download($view_attachment);
        }

        return $response;
    }

    /**
     * Needed for the mobile app.
     */
    // public function mobilePing()
    // {
    //     echo file_get_contents(public_path('installer/css/fontawesome.css'));
    // }
}
