<?php

namespace Modules\ExtendedAttachments\Http\Controllers;

use App\Thread;
use App\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ExtendedAttachmentsController extends Controller
{
    public function downloadThreadAttachments($thread_id)
    {
        $thread = Thread::findOrFail($thread_id);

        if (!$thread->has_attachments) {
            abort(404);
        }

        $archive_path = 'extendedattachments'.DIRECTORY_SEPARATOR.'attachments_'.$thread_id.'.zip';

        $storage = \Helper::getPrivateStorage();

        // Check if archive already exists.
        if (!$storage->exists($archive_path)) {
            // Copy attachments to the temporary folder.
            $storage->makeDirectory('extendedattachments');
            $storage->makeDirectory('extendedattachments'.DIRECTORY_SEPARATOR.$thread_id);
            foreach ($thread->attachments as $attachment) {

                $attachment_path = 'extendedattachments'.DIRECTORY_SEPARATOR.$thread_id.DIRECTORY_SEPARATOR.$attachment->file_name;
                if ($storage->exists($attachment_path)) {
                    $i = 2;
                    do {
                        $attachment_path = 'extendedattachments'.DIRECTORY_SEPARATOR.$thread_id.DIRECTORY_SEPARATOR.$i.'_'.$attachment->file_name;
                        $i++;
                    } while ($storage->exists($attachment_path));
                }
                $storage->copy($attachment->getStorageFilePath(), $attachment_path);
            }

            \Helper::createZipArchive(storage_path().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'extendedattachments'.DIRECTORY_SEPARATOR.$thread_id.DIRECTORY_SEPARATOR.'*', 'attachments_'.$thread_id.'.zip', '', $archive_path);

            $storage->deleteDirectory('extendedattachments'.DIRECTORY_SEPARATOR.$thread_id);
        }
        
        // Send archive.
        return $storage->download($archive_path);
    }

    /**
     * Ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $user = auth()->user();

        switch ($request->action) {

            // Delete board.
            case 'delete_attachment':
                $attachment = Attachment::find($request->attachment_id);
                if (!$attachment) {
                    $response['msg'] = __('Attachment not found');
                }

                if (!$response['msg'] && $attachment->thread_id 
                    && $attachment->thread && $attachment->thread->conversation
                    && $attachment->thread->conversation->mailbox
                ) {
                    if (!$attachment->thread->conversation->mailbox->userHasAccess($user)) {
                        $response['msg'] = __('Not enough permissions');
                    }
                }

                if (!$response['msg']) {                    
                    Attachment::deleteAttachments([$attachment]);
                }

                $response['status'] = 'success';
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

    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            
            case 'delete_attachment':
                $attachment = Attachment::find($request->attachment_id);
                return view('extendedattachments::ajax_html/delete_attachment', [
                    'attachment' => $attachment,
                ]);
                break;
        }

        abort(404);
    }
}
