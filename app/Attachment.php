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

    /**
     * Files with such extensions are being renamed on upload.
     */
    public static $restricted_extensions = [
        'php.*',
        'sh',
        'pl',
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

        // Check extension.
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (preg_match('/('.implode('|', self::$restricted_extensions).')/', $extension)) {
            // Add underscore to the extension if file has restricted extension.
            $file_name = $file_name.'_';
        }

        // Replace some symbols in file name.
        // Gmail can not load image if it contains spaces.
        $file_name = preg_replace('/[ #]/', '-', $file_name);

        if (!$type) {
            $type = self::detectType($mime_type);
        }

        $attachment = new self();
        $attachment->thread_id = $thread_id;
        $attachment->user_id = $user_id;
        $attachment->file_name = $file_name;
        $attachment->mime_type = $mime_type;
        $attachment->type = $type;
        $attachment->embedded = $embedded;
        $attachment->save();

        // Save file from content or copy file.
        // We have to keep file name as is, so if file exists we create extra folder.
        // Examples: 1/2/3
        $file_dir = self::generatePath($attachment->id);

        $i = 0;
        do {
            $i++;
            $file_path = self::DIRECTORY.DIRECTORY_SEPARATOR.$file_dir.$i.DIRECTORY_SEPARATOR.$file_name;
        } while (Storage::exists($file_path));

        $file_dir .= $i.DIRECTORY_SEPARATOR;

        if ($uploaded_file) {
            $uploaded_file->storeAs(self::DIRECTORY.DIRECTORY_SEPARATOR.$file_dir, $file_name);
        } else {
            Storage::put($file_path, $content);
        }

        $attachment->file_dir = $file_dir;
        $attachment->size = Storage::size($file_path);
        $attachment->save();

        return $attachment;
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
    public static function detectType($mime_type)
    {
        if (preg_match("/^text\//", $mime_type)) {
            return self::TYPE_TEXT;
        } elseif (preg_match("/^message\//", $mime_type)) {
            return self::TYPE_MESSAGE;
        } elseif (preg_match("/^application\//", $mime_type)) {
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
        return Storage::url($this->getStorageFilePath());
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

    public function getStorageFilePath()
    {
        return self::DIRECTORY.DIRECTORY_SEPARATOR.$this->file_dir.$this->file_name;
    }

    public function getLocalFilePath()
    {
        return Storage::path(self::DIRECTORY.DIRECTORY_SEPARATOR.$this->file_dir.$this->file_name);
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
            Storage::delete($attachment->getStorageFilePath());
        }

        // Delete from DB
        self::whereIn('id', $attachments->pluck('id')->toArray())->delete();
    }

    /**
     * Generate dummy ID for attachment.
     * Not used for now.
     */
    /*public static function genrateDummyId()
    {
        if (!$this->id) {
            // Math.random should be unique because of its seeding algorithm.
            // Convert it to base 36 (numbers + letters), and grab the first 9 characters
            // after the decimal.
            $hash = mt_rand() / mt_getrandmax();
            $hash = base_convert($hash, 10, 36);
            $id = substr($hash, 2, 9);
        } else {
            $id = substr(md5($this->id), 0, 9);
        }
        return $id;
    }*/
}
