<?php  namespace Devfactory\Minify\Providers;

use Devfactory\Minify\Exceptions\CannotRemoveFileException;
use Devfactory\Minify\Exceptions\CannotSaveFileException;
use Devfactory\Minify\Exceptions\DirNotExistException;
use Devfactory\Minify\Exceptions\DirNotWritableException;
use Devfactory\Minify\Exceptions\FileNotExistException;
use Illuminate\Filesystem\Filesystem;
use Countable;

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
    protected $files = array();

    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @var string
     */
    private $publicPath;

    /**
     * @var Illuminate\Foundation\Filesystem
     */
    protected $file;

    /**
     * @var boolean
     */
    private $disable_mtime;

    /**
     * @var string
     */
    private $hash_salt;

    /**
     * @param null $publicPath
     */
    public function __construct($publicPath = null, $config = null, Filesystem $file = null)
    {
        $this->file = $file ?: new Filesystem;

        $this->publicPath = $publicPath ?: $_SERVER['DOCUMENT_ROOT'];

        $this->disable_mtime = $config['disable_mtime'] ?: false;
        $this->hash_salt = $config['hash_salt'] ?: '';

        $value = function($key)
        {
            return isset($_SERVER[$key]) ? $_SERVER[$key] : '';
        };

        $this->headers = array(
            'User-Agent'      => $value('HTTP_USER_AGENT'),
            'Accept'          => $value('HTTP_ACCEPT'),
            'Accept-Language' => $value('HTTP_ACCEPT_LANGUAGE'),
            'Accept-Encoding' => 'identity',
            'Connection'      => 'close',
        );
    }

    /**
     * @param $outputDir
     * @return bool
     */
    public function make($outputDir)
    {
        $this->outputDir = $this->publicPath . $outputDir;

        $this->checkDirectory();

        if ($this->checkExistingFiles())
        {
            return false;
        }

        $this->removeOldFiles();
        $this->appendFiles();

        return true;
    }

    /**
     * @param  $file
     * @return void
     * @throws \Devfactory\Minify\Exceptions\FileNotExistException
     */
    public function add($file)
    {
        if (is_array($file))
        {
            foreach ($file as $value) $this->add($value);
        }
        else if ($this->checkExternalFile($file))
        {
            $this->files[] = $file;
        }
        else {
            $file = $this->publicPath . $file;
            if (!file_exists($file))
            {
                throw new FileNotExistException("File '{$file}' does not exist");
            }

            $this->files[] = $file;
        }
    }

    /**
     * @param      $baseUrl
     * @param $attributes
     *
     * @return string
     */
    public function tags($baseUrl, $attributes)
    {
        $html = '';
        foreach($this->files as $file)
        {
            $file = $baseUrl . str_replace($this->publicPath, '', $file);
            $html .= $this->tag($file, $attributes);
        }

        return $html;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->files);
    }

    /**
     * @throws \Devfactory\Minify\Exceptions\FileNotExistException
     */
    protected function appendFiles()
    {
        foreach ($this->files as $file) {
            if ($this->checkExternalFile($file))
            {
                if (strpos($file, '//') === 0) $file = 'http:' . $file;

                $headers = $this->headers;
                foreach ($headers as $key => $value)
                {
                    $headers[$key] = $key . ': ' . $value;
                }
                $context = stream_context_create(array('http' => array(
                    'ignore_errors' => true,
                    'header' => implode("\r\n", $headers),
                )));

                $http_response_header = array(false);
                $contents = file_get_contents($file, false, $context);

                if (strpos($http_response_header[0], '200') === false)
                {
                    throw new FileNotExistException("File '{$file}' does not exist");
                }
            } else {
                $contents = file_get_contents($file);
            }

            $this->appended .= $contents . "\n";
        }
    }

    /**
     * @return bool
     */
    protected function checkExistingFiles()
    {
        $this->buildMinifiedFilename();

        return file_exists($this->outputDir . $this->filename);
    }

    /**
     * @throws \Devfactory\Minify\Exceptions\DirNotWritableException
     * @throws \Devfactory\Minify\Exceptions\DirNotExistException
     */
    protected function checkDirectory()
    {
        if (!file_exists($this->outputDir))
        {
          // Try to create the directory
          if (!$this->file->makeDirectory($this->outputDir, 0775, true)) {
            throw new DirNotExistException("Buildpath '{$this->outputDir}' does not exist");
          }
        }

        if (!is_writable($this->outputDir))
        {
            throw new DirNotWritableException("Buildpath '{$this->outputDir}' is not writable");
        }
    }

    /**
     * @param  string  $file
     * @return bool
     */
    protected function checkExternalFile($file)
    {
        return preg_match('/^(https?:)?\/\//', $file);
    }

    /**
     * @return string
     */
    protected function buildMinifiedFilename()
    {
        $this->filename = $this->getHashedFilename() . (($this->disable_mtime) ? '' : $this->countModificationTime()) . static::EXTENSION;
    }

    /**
     * Build an HTML attribute string from an array.
     *
     * @param  array  $attributes
     * @return string
     */
    protected function attributes($attributes)
    {
        $html = array();
        foreach ((array) $attributes as $key => $value)
        {
            $element = $this->attributeElement($key, $value);

            if ( ! is_null($element)) $html[] = $element;
        }

        $output = count($html) > 0 ? ' '.implode(' ', $html) : '';

        return trim($output);
    }

    /**
     * Build a single attribute element.
     *
     * @param  string|integer $key
     * @param  string|boolean $value
     * @return string|null
     */
    protected function attributeElement($key, $value)
    {
        if (is_numeric($key)) $key = $value;

        if(is_bool($value))
            return $key;

        if ( ! is_null($value))
            return $key.'="'.htmlentities($value, ENT_QUOTES, 'UTF-8', false).'"';

        return null;
    }

    /**
     * @return string
     */
    protected function getHashedFilename()
    {
        $publicPath = $this->publicPath;
        return md5(implode('-', array_map(function($file) use ($publicPath) { return str_replace($publicPath, '', $file); }, $this->files)) . $this->hash_salt);
    }

    /**
     * @return int
     */
    protected function countModificationTime()
    {
        $time = 0;

        foreach ($this->files as $file)
        {
            if ($this->checkExternalFile($file))
            {
                $userAgent = isset($this->headers['User-Agent']) ? $this->headers['User-Agent'] : '';
                $time += hexdec(substr(md5($file . $userAgent), 0, 8));
            }
            else {
                $time += filemtime($file);
            }
        }

        return $time;
    }

    /**
     * @throws \Devfactory\Minify\Exceptions\CannotRemoveFileException
     */
    protected function removeOldFiles()
    {
        $pattern = $this->outputDir . $this->getHashedFilename() . '*';
        $find = glob($pattern);

        if( is_array($find) && count($find) )
        {
            foreach ($find as $file)
            {
                if ( ! unlink($file) ) {
                    throw new CannotRemoveFileException("File '{$file}' cannot be removed");
                }
            }
        }
    }

    /**
     * @param $minified
     * @return string
     * @throws \Devfactory\Minify\Exceptions\CannotSaveFileException
     */
    protected function put($minified)
    {
        if(file_put_contents($this->outputDir . $this->filename, $minified) === false)
        {
            throw new CannotSaveFileException("File '{$this->outputDir}{$this->filename}' cannot be saved");
        }

        return $this->filename;
    }

    /**
     * @return string
     */
    public function getAppended()
    {
        return $this->appended;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }
}
