<?php

namespace App\Minify\Providers;

use Countable;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

abstract class BaseProvider implements Countable
{
    /**
     * @var string
     */
    protected $outputDir;

    /**
     * @var string
     */
    protected $appended = '';

    /**
     * @var string
     */
    protected $filename = '';

    /**
     * @var array
     */
    protected $files = [];

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var string
     */
    private $publicPath;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $file;

    /**
     * @var bool
     */
    private $disableMtime;

    /**
     * @var string
     */
    private $hashSalt;

    /**
     * @param string|null                              $publicPath
     * @param array                                    $config
     * @param \Illuminate\Filesystem\Filesystem|null $file
     */
    public function __construct($publicPath = null, array $config = [], ?Filesystem $file = null)
    {
        $this->file = $file ?: new Filesystem();
        $this->publicPath = $publicPath ?: ($_SERVER['DOCUMENT_ROOT'] ?? '');
        $this->disableMtime = !empty($config['disable_mtime']);
        $this->hashSalt = $config['hash_salt'] ?? '';

        $value = function ($key) {
            return $_SERVER[$key] ?? '';
        };

        $this->headers = [
            'User-Agent' => $value('HTTP_USER_AGENT'),
            'Accept' => $value('HTTP_ACCEPT'),
            'Accept-Language' => $value('HTTP_ACCEPT_LANGUAGE'),
            'Accept-Encoding' => 'identity',
            'Connection' => 'close',
        ];
    }

    /**
     * Prepare the bundle and return whether it needs to be generated.
     *
     * @param string $outputDir
     *
     * @return bool
     */
    public function make($outputDir)
    {
        $this->outputDir = $this->publicPath.$outputDir;
        $this->checkDirectory();

        if ($this->checkExistingFiles()) {
            return false;
        }

        $this->removeOldFiles();
        $this->appendFiles();

        return true;
    }

    /**
     * Add one or more files to the bundle.
     *
     * @param string|array $file
     *
     * @return void
     */
    public function add($file)
    {
        if (is_array($file)) {
            foreach ($file as $value) {
                $this->add($value);
            }

            return;
        }

        if ($this->checkExternalFile($file)) {
            $this->files[] = $file;

            return;
        }

        $path = $this->publicPath.$file;
        if (!file_exists($path)) {
            throw new RuntimeException("File '{$path}' does not exist");
        }

        $this->files[] = $path;
    }

    /**
     * Render individual asset tags.
     *
     * @param string $baseUrl
     * @param array  $attributes
     *
     * @return string
     */
    public function tags($baseUrl, array $attributes)
    {
        $html = '';
        foreach ($this->files as $file) {
            $url = $baseUrl.str_replace($this->publicPath, '', $file);
            $html .= $this->tag($url, $attributes);
        }

        return $html;
    }

    /**
     * Return the number of files in the bundle.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->files);
    }

    /**
     * Append all source file contents.
     *
     * @return void
     */
    protected function appendFiles()
    {
        foreach ($this->files as $file) {
            if ($this->checkExternalFile($file)) {
                $contents = $this->fetchExternalFile($file);
            } else {
                $contents = file_get_contents($file);
                if ($contents === false) {
                    throw new RuntimeException("File '{$file}' cannot be read");
                }
            }

            $this->appended .= $contents."\n";
        }
    }

    /**
     * Fetch an external asset.
     *
     * @param string $file
     *
     * @return string
     */
    private function fetchExternalFile($file)
    {
        if (strpos($file, '//') === 0) {
            $file = 'http:'.$file;
        }

        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = $key.': '.$value;
        }
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers),
            ],
        ]);

        $contents = file_get_contents($file, false, $context);
        $responseHeaders = $http_response_header ?? [];
        if ($contents === false || empty($responseHeaders[0]) || strpos($responseHeaders[0], '200') === false) {
            throw new RuntimeException("File '{$file}' does not exist");
        }

        return $contents;
    }

    /**
     * Determine whether the generated bundle exists.
     *
     * @return bool
     */
    protected function checkExistingFiles()
    {
        $this->buildMinifiedFilename();

        return file_exists($this->outputDir.$this->filename);
    }

    /**
     * Ensure that the output directory exists and is writable.
     *
     * @return void
     */
    protected function checkDirectory()
    {
        if (!file_exists($this->outputDir) && !$this->file->makeDirectory($this->outputDir, 0775, true)) {
            throw new RuntimeException("Build path '{$this->outputDir}' does not exist");
        }

        if (!is_writable($this->outputDir)) {
            throw new RuntimeException("Build path '{$this->outputDir}' is not writable");
        }
    }

    /**
     * Determine whether an asset is external.
     *
     * @param string $file
     *
     * @return bool
     */
    protected function checkExternalFile($file)
    {
        return (bool) preg_match('/^(https?:)?\/\//', $file);
    }

    /**
     * Build the generated filename.
     *
     * @return void
     */
    protected function buildMinifiedFilename()
    {
        $this->filename = $this->getHashedFilename().($this->disableMtime ? '' : $this->countModificationTime()).static::EXTENSION;
    }

    /**
     * Build an HTML attribute string.
     *
     * @param array $attributes
     *
     * @return string
     */
    protected function attributes(array $attributes)
    {
        $html = [];
        foreach ($attributes as $key => $value) {
            $element = $this->attributeElement($key, $value);
            if ($element !== null) {
                $html[] = $element;
            }
        }

        return implode(' ', $html);
    }

    /**
     * Build a single HTML attribute.
     *
     * @param string|int  $key
     * @param string|bool $value
     *
     * @return string|null
     */
    protected function attributeElement($key, $value)
    {
        if (is_numeric($key)) {
            $key = $value;
        }
        if (is_bool($value)) {
            return $key;
        }
        if ($value !== null) {
            return $key.'="'.htmlentities($value, ENT_QUOTES, 'UTF-8', false).'"';
        }

        return null;
    }

    /**
     * Return the stable hash for this set of files.
     *
     * @return string
     */
    protected function getHashedFilename()
    {
        $publicPath = $this->publicPath;
        $files = array_map(function ($file) use ($publicPath) {
            return str_replace($publicPath, '', $file);
        }, $this->files);

        return md5(implode('-', $files).$this->hashSalt);
    }

    /**
     * Return the aggregate modification value for this bundle.
     *
     * @return int
     */
    protected function countModificationTime()
    {
        $time = 0;
        foreach ($this->files as $file) {
            if ($this->checkExternalFile($file)) {
                $time += hexdec(substr(md5($file.($this->headers['User-Agent'] ?? '')), 0, 8));
            } else {
                $time += filemtime($file);
            }
        }

        return $time;
    }

    /**
     * Remove older bundles generated for the same file set.
     *
     * @return void
     */
    protected function removeOldFiles()
    {
        $files = glob($this->outputDir.$this->getHashedFilename().'*');
        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            if (!unlink($file)) {
                throw new RuntimeException("File '{$file}' cannot be removed");
            }
        }
    }

    /**
     * Store a generated bundle.
     *
     * @param string $minified
     *
     * @return string
     */
    protected function put($minified)
    {
        if (file_put_contents($this->outputDir.$this->filename, $minified) === false) {
            throw new RuntimeException("File '{$this->outputDir}{$this->filename}' cannot be saved");
        }

        return $this->filename;
    }

    /**
     * Return the appended source contents.
     *
     * @return string
     */
    public function getAppended()
    {
        return $this->appended;
    }

    /**
     * Return the generated filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Minify and write the current bundle.
     *
     * @return string
     */
    abstract public function minify();

    /**
     * Render an asset tag.
     *
     * @param string $file
     * @param array  $attributes
     *
     * @return string
     */
    abstract public function tag($file, array $attributes = []);
}
