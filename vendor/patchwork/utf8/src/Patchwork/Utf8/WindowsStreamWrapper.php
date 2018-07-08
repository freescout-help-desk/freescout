<?php

/*
 * Copyright (C) 2014 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\Utf8;

/**
 * Unicode UTF-8 aware stream based filesystem access on MS-Windows.
 *
 * Based on COM Scripting.FileSystemObject object and short paths.
 * See Patchwork\Utf8::wrapPath()
 *
 * See also https://code.google.com/p/php-wfio/ for a PHP extension
 * and comments on http://www.rooftopsolutions.nl/blog/filesystem-encoding-and-php
 */
class WindowsStreamWrapper
{
    public $context;

    protected $handle;

    public static function hide($path)
    {
        list($fs, $path) = self::fs($path);
        if ($fs->FileExists($path)) {
            $fs->GetFile($path)->Attributes |= 2;
        } elseif ($fs->FolderExists($path)) {
            $fs->GetFolder($path)->Attributes |= 2;
        } else {
            return false;
        }

        return true;
    }

    public static function fs($path, $is_utf8 = true)
    {
        static $fs;

        if (!class_exists('COM', false)) {
            throw new \RuntimeException('The `wfio` or `com_dotnet` extension is required to handle UTF-8 filesystem access on Windows');
        }

        isset($fs) or $fs = new \COM('Scripting.FileSystemObject', null, CP_UTF8);

        $path = explode('://', $path, 2);
        $path = $path[(int) isset($path[1])];
        $path = strtr($path, '/', '\\');
        $pre = '';

        if (!isset($path[0]) || ('/' !== $path[0] && '\\' !== $path[0] && false === strpos($path, ':'))) {
            $pre = getcwd().'\\';
        }

        $pre = new \VARIANT($pre);

        if ($is_utf8) {
            $path = new \VARIANT($path, VT_BSTR, CP_UTF8);
        } else {
            $path = new \VARIANT($path);
        }

        return array($fs, $fs->getAbsolutePathName(variant_cat($pre, $path)));
    }

    public function dir_closedir()
    {
        $this->handle = null;

        return true;
    }

    public function dir_opendir($path, $options)
    {
        list($fs, $path) = self::fs($path);
        if (!$fs->FolderExists($path)) {
            return false;
        }

        $dir = $fs->GetFolder($path);

        try {
            $f = array('.', '..');

            foreach ($dir->SubFolders() as $v) {
                $f[] = $v->Name;
            }
            foreach ($dir->Files as $v) {
                $f[] = $v->Name;
            }
        } catch (\Exception $f) {
            $f = array();
        }

        $this->handle = $f;

        return true;
    }

    public function dir_readdir()
    {
        if (list(, $c) = each($this->handle)) {
            return $c;
        }

        return false;
    }

    public function dir_rewinddir()
    {
        reset($this->handle);

        return true;
    }

    public function mkdir($path, $mode, $options)
    {
        list($fs, $path) = self::fs($path);

        try {
            if ($options & STREAM_MKDIR_RECURSIVE) {
                $path = $fs->GetAbsolutePathName($path);

                $path = explode('\\', $path);

                if (isset($path[3]) && '' === $path[0].$path[1]) {
                    $pre = '\\\\'.$path[2].'\\'.$path[3].'\\';
                    $i = 4;
                } elseif (isset($path[1])) {
                    $pre = $path[0].'\\';
                    $i = 1;
                } else {
                    $pre = '';
                    $i = 0;
                }

                while (isset($path[$i]) && $fs->FolderExists($pre.$path[$i])) {
                    $pre .= $path[$i++].'\\';
                }

                if (!isset($path[$i])) {
                    return false;
                }

                while (isset($path[$i])) {
                    $fs->CreateFolder($pre .= $path[$i++].'\\');
                }

                return true;
            } else {
                $fs->CreateFolder($path);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function rename($from, $to)
    {
        list($fs, $to) = self::fs($to);

        if ($fs->FileExists($to) || $fs->FolderExists($to)) {
            return false;
        }

        list(, $from) = self::fs($from);

        try {
            if ($fs->FileExists($from)) {
                $fs->MoveFile($from, $to);

                return true;
            }

            if ($fs->FolderExists($from)) {
                $fs->MoveFolder($from, $to);

                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    public function rmdir($path, $options)
    {
        list($fs, $path) = self::fs($path);

        if ($fs->FolderExists($path)) {
            return rmdir($fs->GetFolder($path)->ShortPath);
        }

        return false;
    }

    public function stream_close()
    {
        fclose($this->handle);
        $this->handle = null;
    }

    public function stream_eof()
    {
        return feof($this->handle);
    }

    public function stream_flush()
    {
        return fflush($this->handle);
    }

    public function stream_lock($operation)
    {
        return flock($this->handle, $operation);
    }

    public function stream_metadata($path, $option, $value)
    {
        list($fs, $path) = self::fs($path);

        if ($fs->FileExists($path)) {
            $f = $fs->GetFile($path);
        } elseif ($fs->FileExists($path)) {
            $f = $fs->GetFolder($path);
        } else {
            $f = false;
        }

        if (STREAM_META_TOUCH === $option) {
            if ($f) {
                return touch($f->ShortPath);
            }

            try {
                $fs->OpenTextFile($path, 8, true, 0)->Close();

                return true;
            } catch (\Exception $e) {
            }
        }

        if (!$f) {
            return false;
        }

        switch ($option) {
            case STREAM_META_ACCESS:     return chmod($f->ShortPath, $value);
            case STREAM_META_OWNER:
            case STREAM_META_OWNER_NAME: return chown($f->ShortPath, $value);
            case STREAM_META_GROUP:
            case STREAM_META_GROUP_NAME: return chgrp($f->ShortPath, $value);
            default: return false;
        }
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $mode .= '';
        list($fs, $path) = self::fs($path);

        if ($fs->FolderExists($path)) {
            return false;
        }

        try {
            if ('x' === $m = substr($mode, 0, 1)) {
                $fs->CreateTextFile($path, false)->Close();
                $f = $fs->GetFile($path);
                $mode[0] = 'w';
            } else {
                $f = $fs->GetFile($path);
            }
        } catch (\Exception $f) {
            try {
                switch ($m) {
                    case 'w':
                    case 'c':
                    case 'a':
                        $h = $fs->CreateTextFile($path, true);
                        $f = $fs->GetFile($path);
                        $h->Close();
                        break;

                    default: return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        if (!(STREAM_REPORT_ERRORS & $options)) {
            set_error_handler('var_dump', 0);
            $e = error_reporting(0);
        }

        $this->handle = fopen($f->ShortPath, $mode);

        if (!(STREAM_REPORT_ERRORS & $options)) {
            error_reporting($e);
            restore_error_handler();
        }

        if ($this->handle) {
            return true;
        }
        if (isset($h)) {
            $f->Delete(true);
        }

        return false;
    }

    public function stream_read($count)
    {
        return fread($this->handle, $count);
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return fseek($this->handle, $offset, $whence);
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
        switch ($option) {
            case STREAM_OPTION_BLOCKING:     return stream_set_blocking($this->handle, $arg1);
            case STREAM_OPTION_READ_TIMEOUT: return stream_set_timeout($this->handle, $arg1, $arg2);
            case STREAM_OPTION_WRITE_BUFFER: return stream_set_write_buffer($this->handle, $arg1, $arg2);
            default: return false;
        }
    }

    public function stream_stat()
    {
        return fstat($this->handle);
    }

    public function stream_tell()
    {
        return ftell($this->handle);
    }

    public function stream_truncate($new_size)
    {
        return ftruncate($this->handle, $new_size);
    }

    public function stream_write($data)
    {
        return fwrite($this->handle, $data, strlen($data));
    }

    public function unlink($path)
    {
        list($fs, $path) = self::fs($path);

        if ($fs->FileExists($path)) {
            return unlink($fs->GetFile($path)->ShortPath);
        }

        return false;
    }

    public function url_stat($path, $flags)
    {
        list($fs, $path) = self::fs($path);

        if ($fs->FileExists($path)) {
            $f = $fs->GetFile($path);
        } elseif ($fs->FolderExists($path)) {
            $f = $fs->GetFolder($path);
        } else {
            return false;
        }

        if (STREAM_URL_STAT_QUIET & $flags) {
            set_error_handler('var_dump', 0);
            $e = error_reporting(0);
        }

        if (STREAM_URL_STAT_LINK & $flags) {
            $f = @lstat($f->ShortPath) ?: stat($f->ShortPath);
        } else {
            $f = stat($f->ShortPath);
        }

        if (STREAM_URL_STAT_QUIET & $flags) {
            error_reporting($e);
            restore_error_handler();
        }

        return $f;
    }
}
