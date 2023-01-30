<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    const TYPE_TEXT = 0;
    const TYPE_MULTIPART = 1;
    const TYPE_MESSAGE = 2;
    const TYPE_APPLICATION = 3;
    const TYPE_AUDIO = 4;
    const TYPE_IMAGE = 5;
    const TYPE_VIDEO = 6;
    const TYPE_MODEL = 7;
    const TYPE_OTHER = 8;

    const DIRECTORY = 'attachment';

    CONST DISK = 'private';

    // https://github.com/Webklex/laravel-imap/blob/master/src/IMAP/Attachment.php
    public static $types = [
        'message'     => self::TYPE_MESSAGE,
        'application' => self::TYPE_APPLICATION,
        'audio'       => self::TYPE_AUDIO,
        'image'       => self::TYPE_IMAGE,
        'video'       => self::TYPE_VIDEO,
        'model'       => self::TYPE_MODEL,
        'text'        => self::TYPE_TEXT,
        'multipart'   => self::TYPE_MULTIPART,
        'other'       => self::TYPE_OTHER,
    ];

    public static $type_extensions = [
        self::TYPE_VIDEO => ['flv', 'mp4', 'm3u8', 'ts', '3gp', 'mov', 'avi', 'wmv']
    ];

    public $timestamps = false;

    /**
     * Get thread.
     */
    public function thread()
    {
        return $this->belongsTo('App\Thread');
    }

    /**
     * Save attachment to file and database.
     */
    public static function create($file_name, $mime_type, $type, $content, $uploaded_file, $embedded = false, $thread_id = null, $user_id = null)
    {
        if (!$content && !$uploaded_file) {
            return false;
        }

        $orig_extension = pathinfo($file_name, PATHINFO_EXTENSION);

        // Add underscore to the extension if file has restricted extension.
        $file_name = \Helper::sanitizeUploadedFileName($file_name, $uploaded_file, $content);

        // Replace some symbols in file name.
        // Gmail can not load image if it contains spaces.
        $file_name = preg_replace('/[ #\/]/', '-', $file_name);
        // Replace soft hyphens.
        $file_name = str_replace(html_entity_decode('&#xAD;'), '-', $file_name);

        if (!$file_name) {
            if (!$orig_extension) {
                preg_match("/.*\/([^\/]+)$/", $mime_type, $m);
                if (!empty($m[1])) {
                    $orig_extension = $m[1];
                }
            }
            $file_name = uniqid();
            if ($orig_extension) {
                $file_name .= '.'.$orig_extension;
            }
        }

        // https://github.com/freescout-helpdesk/freescout/issues/2385
        // Fix for webklex/php-imap.
        if ($file_name == 'undefined' && $mime_type == 'message/rfc822') {
            $file_name = 'RFC822.eml';
        }

        if (strlen($file_name) > 255) {
            $without_ext = pathinfo($file_name, PATHINFO_FILENAME);
            $extension = pathinfo($file_name, PATHINFO_EXTENSION);
            // 125 because file name may have unicode symbols.
            $file_name = \Helper::substrUnicode($without_ext, 0, 125-strlen($extension)-1);
            $file_name .= '.'.$extension;
        }

        if (!$type) {
            $type = self::detectType($mime_type, $orig_extension);
        }

        $attachment = new self();
        $attachment->thread_id = $thread_id;
        $attachment->user_id = $user_id;
        $attachment->file_name = $file_name;
        $attachment->mime_type = $mime_type;
        $attachment->type = $type;
        $attachment->embedded = $embedded;
        $attachment->save();

        $file_info = self::saveFileToDisk($attachment, $file_name, $content, $uploaded_file);

        $attachment->file_dir = $file_info['file_dir'];
        $attachment->size = Storage::disk(self::DISK)->size($file_info['file_path']);
        $attachment->save();

        return $attachment;
    }

    /**
     * Save file to the disk and return file_dir.
     */
    public static function saveFileToDisk($attachment, $file_name, $content, $uploaded_file)
    {
        // Save file from content or copy file.
        // We have to keep file name as is, so if file exists we create extra folder.
        // Examples: 1/2/3
        $file_dir = self::generatePath($attachment->id);

        $i = 0;
        do {
            $i++;
            $file_path = self::DIRECTORY.DIRECTORY_SEPARATOR.$file_dir.$i.DIRECTORY_SEPARATOR.$file_name;
        } while (Storage::disk(self::DISK)->exists($file_path));

        $file_dir .= $i.DIRECTORY_SEPARATOR;

        if ($uploaded_file) {
            $uploaded_file->storeAs(self::DIRECTORY.DIRECTORY_SEPARATOR.$file_dir, $file_name, ['disk' => self::DISK]);
        } else {
            Storage::disk(self::DISK)->put($file_path, $content);
        }

        \Helper::sanitizeUploadedFileData($file_path, \Helper::getPrivateStorage(), $content);

        return [
            'file_dir'  => $file_dir,
            'file_path' => $file_path,
        ];
    }

    /**
     * Get file path.
     * Examples: 1/2, 1/3.
     *
     * @param int $id
     *
     * @return string
     */
    public static function generatePath($id)
    {
        $hash = md5($id);

        $first = -1;
        $second = 0;

        for ($i = 0; $i < strlen($hash); $i++) {
            if (is_numeric($hash[$i])) {
                if ($first == -1) {
                    $first = $hash[$i];
                } else {
                    $second = $hash[$i];
                    break;
                }
            }
        }
        if ($first == -1) {
            $first = 0;
        }

        return $first.DIRECTORY_SEPARATOR.$second.DIRECTORY_SEPARATOR;
    }

    /**
     * Detect attachment type by it's mime type.
     *
     * @param string $mime_type
     *
     * @return int
     */
    public static function detectType($mime_type, $extension = '')
    {
        if (preg_match("/^text\//", $mime_type)) {
            return self::TYPE_TEXT;
        } elseif (preg_match("/^message\//", $mime_type)) {
            return self::TYPE_MESSAGE;
        } elseif (preg_match("/^application\//", $mime_type)) {
            // This is tricky mime type.
            // For .mp4 mime type can be application/octet-stream
            if (!empty($extension) && in_array(strtolower($extension), self::$type_extensions[self::TYPE_VIDEO])) {
                return self::TYPE_VIDEO;
            }
            return self::TYPE_APPLICATION;
        } elseif (preg_match("/^audio\//", $mime_type)) {
            return self::TYPE_AUDIO;
        } elseif (preg_match("/^image\//", $mime_type)) {
            return self::TYPE_IMAGE;
        } elseif (preg_match("/^video\//", $mime_type)) {
            return self::TYPE_VIDEO;
        } elseif (preg_match("/^model\//", $mime_type)) {
            return self::TYPE_MODEL;
        } else {
            return self::TYPE_OTHER;
        }
    }

    /**
     * Conver type name to integer.
     */
    public static function typeNameToInt($type_name)
    {
        if (!empty(self::$types[$type_name])) {
            return self::$types[$type_name];
        } else {
            return self::TYPE_OTHER;
        }
    }

    /**
     * Get attachment full public URL.
     *
     * @return string
     */
    public function url()
    {
        return Storage::url($this->getStorageFilePath()).'?id='.$this->id.'&token='.$this->getToken();
    }

    /**
     * Get hashed security token for the attachment.
     */
    public function getToken()
    {
        // \Hash::make() may contain . and / symbols which may cause problems.
        return md5(config('app.key').$this->id.$this->size);
    }

    /**
     * Outputs the current Attachment as download
     */
    public function download($view = false)
    {
        $headers = [];
        // #533
        //return $this->getDisk()->download($this->getStorageFilePath(), \Str::ascii($this->file_name));
        if ($view) {
            $headers['Content-Disposition'] = '';
        }
        $file_name = $this->file_name;

        if ($file_name == "RFC822"){
            $file_name = $file_name.'.eml';
        }

        return $this->getDisk()->download($this->getStorageFilePath(), $file_name, $headers);
    }

    private function getDisk() {
        return Storage::disk(self::DISK);
    }

    /**
     * Convert size into human readable format.
     *
     * @return string
     */
    public function getSizeName()
    {
        return self::formatBytes($this->size);
    }

    /**
     * attachment/...
     */
    public function getStorageFilePath()
    {
        return self::DIRECTORY.DIRECTORY_SEPARATOR.$this->file_dir.$this->file_name;
    }

    /**
     * /var/html/storage/app/attachment/...
     */
    public function getLocalFilePath($full = true)
    {
        if ($full) {
            return $this->getDisk()->path(self::DIRECTORY.DIRECTORY_SEPARATOR.$this->file_dir.$this->file_name);
        } else {
            return DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.self::DIRECTORY.DIRECTORY_SEPARATOR.$this->file_dir.$this->file_name;
        }
    }

    /**
     * Check if the attachment file actually exists on the disk.
     */
    public function fileExists()
    {
        return $this->getDisk()->exists(self::DIRECTORY.DIRECTORY_SEPARATOR.$this->file_dir.$this->file_name);
    }

    public static function formatBytes($size, $precision = 0)
    {
        $size = (int) $size;
        if ($size > 0) {
            $base = log($size) / log(1024);
            $suffixes = [' b', ' KB', ' MB', ' GB', ' TB'];

            return round(pow(1024, $base - floor($base)), $precision).$suffixes[floor($base)];
        } else {
            return $size;
        }
    }

    /**
     * Delete attachments from disk and DB.
     * Embeds are not taken into account.
     *
     * @param array $attachments
     */
    public static function deleteByIds($attachment_ids)
    {
        if (!count($attachment_ids)) {
            return;
        }
        $attachments = self::whereIn('id', $attachment_ids)->get();

        // Delete from disk
        self::deleteForever($attachments);
    }

    /**
     * Delete attachments by thread IDs.
     */
    public static function deleteByThreadIds($thread_ids)
    {
        if (!count($thread_ids)) {
            return;
        }
        $attachments = self::whereIn('thread_id', $thread_ids)->get();

        // Delete from disk
        self::deleteForever($attachments);
    }

    public static function deleteForever($attachments)
    {
        // Delete from disk
        foreach ($attachments as $attachment) {
            $attachment->getDisk()->delete($attachment->getStorageFilePath());
        }

        // Delete from DB
        self::whereIn('id', $attachments->pluck('id')->toArray())->delete();
    }

    /**
     * Delete attachments and update Thread & Conversation.
     */
    public static function deleteAttachments($attachments)
    {
        if (!$attachments instanceof \Illuminate\Support\Collection) { 
            $attachments = collect($attachments);
        }

        foreach ($attachments as $attachment) {
            if ($attachment->thread_id && $attachment->thread
                && count($attachment->thread->attachments) <= 1
            ) {
                $attachment->thread->has_attachments = false;
                $attachment->thread->save();
                // Update conversation.
                $conversation = $attachment->thread->conversation;
                foreach ($conversation->threads as $thread) {
                    if ($thread->has_attachments) {
                        break 2;
                    }
                }
                $conversation->has_attachments = false;
                $conversation->save();
            }
        }
        Attachment::deleteForever($attachments);
    }

    /**
     * Create a copy of the attachment and it's file.
     */
    public function duplicate($thread_id = null)
    {
        $new_attachment = $this->replicate();
        if ($thread_id) {
            $new_attachment->thread_id = $thread_id;
        }

        $new_attachment->save();

        try {
            $attachment_file = new \Illuminate\Http\UploadedFile(
                $this->getLocalFilePath(), $this->file_name,
                null, null, true
            );

            $file_info = Attachment::saveFileToDisk($new_attachment, $new_attachment->file_name, '', $attachment_file);

            if (!empty($file_info['file_dir'])) {
                $new_attachment->file_dir = $file_info['file_dir'];
                $new_attachment->save();
            }
        } catch (\Exception $e) {
            \Helper::logException($e);
        }

        return $new_attachment;
    }

    public function getFileContents()
    {
        return $this->getDisk()->get($this->getStorageFilePath());
    }
}
