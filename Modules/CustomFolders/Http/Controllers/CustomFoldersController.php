<?php

namespace Modules\CustomFolders\Http\Controllers;

use App\Conversation;
use App\Folder;
use App\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CustomFoldersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $mailbox = Mailbox::findOrFail($id);

        if (!\CustomFolder::canUserUpdateMailboxCustomFolders($mailbox)) {
            \Helper::denyAccess();
        }

        $folders = [];
        $flashes = [];

        if (\CustomFolder::isTagsActive()) {
            $folders = \CustomFolder::mailboxCustomFolders($id);

            foreach ($folders as $i => $folder) {
                if (!empty($folder->meta['tag_id'])) {
                    $tag = \Modules\Tags\Entities\Tag::find($folder->meta['tag_id']);
                    if ($tag) {
                        $folder->tag_name = $tag->name;
                    }
                }
            }
        } else {
            $flashes[] = [
                'type'      => 'danger',
                'text'      => __('The following modules have to be installed and activated: :modules', ['modules' => '<strong>'.__('Tags').'</strong>']),
                'unescaped' => true,
            ];
        }

        return view('customfolders::index', [
            'mailbox' => $mailbox,
            'folders' => $folders,
            'flashes' => $flashes,
        ]);
    }

    /**
     * Conversations ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {

            // Create/update custom folder
            case 'create':
            case 'update':

                $name = $request->name;

                if (!$name) {
                    $response['msg'] = __(':field is required', ['field' => __('Name')]);
                }

                $tag_name = $request->tag_name;

                // if (!$tag_name) {
                //     $response['msg'] = __(':field is required', ['field' => __('Tag')]);
                // }

                if (!$response['msg']) {
                    $mailbox = Mailbox::find($request->mailbox_id);

                    if (!$mailbox) {
                        $response['msg'] = __('Mailbox not found');
                    }
                }

                if (!$response['msg'] && !\CustomFolder::canUserUpdateMailboxCustomFolders($mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $meta = [];
                    $prev_tag_id = null;

                    if ($request->action == 'update') {
                        $folder = Folder::find($request->folder_id);
                        if (!$folder) {
                            $response['msg'] = __('Folder not found');
                        } else {
                            $meta = $folder->meta;
                        }
                        $prev_tag_id = $folder->meta['tag_id'] ?? null;
                    } else {
                        $folder = new Folder();
                        $meta['order'] = 1000;
                    }

                    if (!$response['msg']) {

                        $tag = null;
                        $new_tag_id = null;
                        if ($tag_name) {
                            $tag = \Modules\Tags\Entities\Tag::getOrCreate(['name' => $tag_name]);
                            $new_tag_id = $tag->id;
                        }

                        $prev_user_id = $folder->user_id;
                        $prev_meta_ownonly = isset($folder->meta['own_only']) ? $folder->meta['own_only'] : null;

                        $folder->mailbox_id = $mailbox->id;
                        $folder->type = \CustomFolder::TYPE_CUSTOM;
                        $meta['name'] = $name;
                        
                        if ($tag) {
                            $meta['tag_id'] = (int)$tag->id;
                        } elseif (isset($meta['tag_id'])) {
                            unset($meta['tag_id']);
                        }

                        $meta['counter'] = (int)$request->counter;
                        $meta['status_filter'] = $request->status_filter;
                        if (count($request->status_filter) == count(Conversation::$statuses)) {
                            unset($meta['status_filter']);
                        }
                        $meta['own_only'] = $request->own_only ?? '';
                        $meta['icon'] = $request->icon;
                        $folder->meta = $meta;
                        $folder->user_id = $request->user_id;
                        $folder->save();

                        // Update counters if tag, user_id or own_only changed.
                        if (
                            $new_tag_id != $prev_tag_id
                            || $prev_user_id != $folder->user_id
                            || $prev_meta_ownonly != $folder->meta['own_only']
                        ) {
                            \CustomFolder::setCounters($folder->fresh());
                        }

                        $response['id']     = $folder->id;
                        $response['name']   = $name;
                        $response['tag_name']   = $tag_name;
                        $response['status'] = 'success';

                        if ($request->action == 'update') {
                            $response['msg_success'] = __('Folder updated');
                        } else {
                            // Flash
                            \Session::flash('flash_success_floating', __('Folder created'));
                        }
                    }
                }
                break;

            // Delete
            case 'delete':

                if (!$response['msg']) {
                    $folder = Folder::find($request->folder_id);

                    if (!$folder || $folder->type != \CustomFolder::TYPE_CUSTOM) {
                        $response['msg'] = __('Folder not found');
                    }
                }

                if (!$response['msg'] && !\CustomFolder::canUserUpdateMailboxCustomFolders($folder->mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $folder->delete();

                    $response['status'] = 'success';
                    $response['msg_success'] = __('Folder deleted');
                }
                break;

            // Update saved reply
            case 'update_sort_order':

                $folders = Folder::whereIn('id', $request->folders)->get();
                $folders = \CustomFolder::sortFolders($folders);

                if (count($folders)) {
                    foreach ($request->folders as $i => $request_folder_id) {
                        foreach ($folders as $folder) {
                            if ($folder->id != $request_folder_id) {
                                continue;
                            }
                            $meta = $folder->meta;
                            $meta['order'] = $i+1;
                            $folder->meta = $meta;
                            $folder->save();
                        }
                    }
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
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            case 'create':
                return view('customfolders::create', [
                    'folder' => new Folder,
                    'mailbox' => Mailbox::find($request->mailbox_id),
                ]);
        }

        abort(404);
    }
}
