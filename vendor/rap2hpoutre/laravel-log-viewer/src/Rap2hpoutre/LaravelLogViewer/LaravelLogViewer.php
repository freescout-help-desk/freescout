<?php

namespace Rap2hpoutre\LaravelLogViewer;

/**
 * Class LaravelLogViewer
 * @package Rap2hpoutre\LaravelLogViewer
 */
class LaravelLogViewer
{
    /**
     * @var string file
     */
    private $file;

    /**
     * @var string folder
     */
    private $folder;

    /**
     * @var string storage_path
     */
    private $storage_path;

    /**
     * Why? Uh... Sorry
     */
    const MAX_FILE_SIZE = 52428800;

    /**
     * @var Level level
     */
    private $level;

    /**
     * @var Pattern pattern
     */
    private $pattern;

    /**
     * LaravelLogViewer constructor.
     */
    public function __construct()
    {
        $this->level = new Level();
        $this->pattern = new Pattern();
        $this->storage_path = function_exists('config') ? config('logviewer.storage_path', storage_path('logs')) : storage_path('logs');

    }

    /**
     * @param string $folder
     */
    public function setFolder($folder)
    {
        $logsPath = $this->storage_path . '/' . $folder;

        if (app('files')->exists($logsPath)) {
            $this->folder = $folder;
        }
    }

    /**
     * @param string $file
     * @throws \Exception
     */
    public function setFile($file)
    {
        $file = $this->pathToLogFile($file);

        if (app('files')->exists($file)) {
            $this->file = $file;
        }
    }

    /**
     * @param string $file
     * @return string
     * @throws \Exception
     */
    public function pathToLogFile($file)
    {
        $logsPath = $this->storage_path;
        $logsPath .= ($this->folder) ? '/' . $this->folder : '';

        if (app('files')->exists($file)) { // try the absolute path
            return $file;
        }

        $file = $logsPath . '/' . $file;

        // check if requested file is really in the logs directory
        if (dirname($file) !== $logsPath) {
            throw new \Exception('No such log file');
        }

        return $file;
    }

    /**
     * @return string
     */
    public function getFolderName()
    {
        return $this->folder;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return basename($this->file);
    }

    /**
     * @return array
     */
    public function all()
    {
        $log = array();

        if (!$this->file) {
            $log_file = (!$this->folder) ? $this->getFiles() : $this->getFolderFiles();
            if (!count($log_file)) {
                return [];
            }
            $this->file = $log_file[0];
        }

        if (app('files')->size($this->file) > self::MAX_FILE_SIZE) return null;

        $file = app('files')->get($this->file);

        preg_match_all($this->pattern->getPattern('logs'), $file, $headings);

        if (!is_array($headings)) {
            return $log;
        }

        $log_data = preg_split($this->pattern->getPattern('logs'), $file);

        if ($log_data[0] < 1) {
            array_shift($log_data);
        }

        foreach ($headings as $h) {
            for ($i = 0, $j = count($h); $i < $j; $i++) {
                foreach ($this->level->all() as $level) {
                    if (strpos(strtolower($h[$i]), '.' . $level) || strpos(strtolower($h[$i]), $level . ':')) {

                        preg_match($this->pattern->getPattern('current_log',0) . $level . $this->pattern->getPattern('current_log',1), $h[$i], $current);
                        if (!isset($current[4])) continue;

                        $log[] = array(
                            'context' => $current[3],
                            'level' => $level,
                            'level_class' => $this->level->cssClass($level),
                            'level_img' => $this->level->img($level),
                            'date' => $current[1],
                            'text' => $current[4],
                            'in_file' => isset($current[5]) ? $current[5] : null,
                            'stack' => preg_replace("/^\n*/", '', $log_data[$i])
                        );
                    }
                }
            }
        }

        if (empty($log)) {

            $lines = explode(PHP_EOL, $file);
            $log = [];

            foreach ($lines as $key => $line) {
                $log[] = [
                    'context' => '',
                    'level' => '',
                    'level_class' => '',
                    'level_img' => '',
                    'date' => $key + 1,
                    'text' => $line,
                    'in_file' => null,
                    'stack' => '',
                ];
            }
        }

        return array_reverse($log);
    }

    /**
     * @return array
     */
    public function getFolders()
    {
        $folders = glob($this->storage_path.'/*', GLOB_ONLYDIR);
        if (is_array($folders)) {
            foreach ($folders as $k => $folder) {
                $folders[$k] = basename($folder);
            }
        }
        return array_values($folders);
    }

    /**
     * @param bool $basename
     * @return array
     */
    public function getFolderFiles($basename = false)
    {
        return $this->getFiles($basename, $this->folder);
    }

    /**
     * @param bool $basename
     * @param string $folder
     * @return array
     */
    public function getFiles($basename = false, $folder = '')
    {
        $pattern = function_exists('config') ? config('logviewer.pattern', '*.log') : '*.log';
        $files = glob($this->storage_path.'/' . $folder . '/' . $pattern, preg_match($this->pattern->getPattern('files'), $pattern) ? GLOB_BRACE : 0);
        $files = array_reverse($files);
        $files = array_filter($files, 'is_file');
        if ($basename && is_array($files)) {
            foreach ($files as $k => $file) {
                $files[$k] = basename($file);
            }
        }
        return array_values($files);
    }
}
