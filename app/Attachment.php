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

    const DIRECTORY = 'attachment';

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
    public static function create($name, $mime_type, $type, $content, $thread_id = null)
    {
        if (!$content) {
            return false;
        }

        $file_name = $name;

        $attachment            = new Attachment();
        $attachment->thread_id = $thread_id;
        $attachment->name      = $name;
        $attachment->file_name = $file_name;
        $attachment->mime_type = $mime_type;
        $attachment->type      = $type;
        //$attachment->size      = Storage::size($file_path);
        $attachment->save();

        $file_path = self::DIRECTORY.DIRECTORY_SEPARATOR.self::getPath($attachment->id).$file_name;
        Storage::put($file_path, $content);

        $attachment->size = Storage::size($file_path);
        if ($attachment->size) {
            $attachment->save();
        }
        return true;
    }

    /**
     * Get file path by ID.
     * 
     * @param  integer $id
     * @return string
     */
    public static function getPath($id)
    {
        $hash = md5($id);

        $first  = -1;
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
     * Get attachment public URL.
     * 
     * @return string
     */
    public function getUrl()
    {
        return Storage::url(self::DIRECTORY.DIRECTORY_SEPARATOR.self::getPath($this->id).$this->file_name);
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

    public static function formatBytes($size, $precision = 0)
    {
        $size = (int) $size;
        if ($size > 0) {
            $base = log($size) / log(1024);
            $suffixes = array(' b', ' KB', ' MB', ' GB', ' TB');

            return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
        } else {
            return $size;
        }
    }
}
