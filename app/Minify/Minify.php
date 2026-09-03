<?php

namespace App\Minify;

use App\Minify\Providers\JavaScript;
use App\Minify\Providers\StyleSheet;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Request;

class Minify
{
    /**
     * Minification configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * HTML attributes for generated tags.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Current application environment.
     *
     * @var string
     */
    private $environment;

    /**
     * Provider for the current asset type.
     *
     * @var \App\Minify\Providers\BaseProvider
     */
    private $provider;

    /**
     * Filesystem path for generated bundles.
     *
     * @var string
     */
    private $buildPath;

    /**
     * Whether to prepend the configured base URL.
     *
     * @var bool
     */
    private $fullUrl = false;

    /**
     * Whether to render only the generated URL.
     *
     * @var bool
     */
    private $onlyUrl = false;

    /**
     * Extension of the current asset type.
     *
     * @var string
     */
    private $buildExtension;

    /**
     * Public directory containing source and generated assets.
     *
     * @var string
     */
    private $publicPath;

    /**
     * Create a minify service.
     *
     * @param array       $config
     * @param string      $environment
     * @param string|null $publicPath
     */
    public function __construct(array $config, $environment, $publicPath = null)
    {
        $this->checkConfiguration($config);

        $this->config = $config;
        $this->environment = $environment;
        $this->publicPath = $publicPath ?: public_path();
    }

    /**
     * Build a JavaScript bundle.
     *
     * @param string|array $file
     * @param array        $attributes
     *
     * @return $this
     */
    public function javascript($file, $attributes = [])
    {
        $this->setProvider(new JavaScript($this->publicPath, $this->providerConfig()), 'js', $attributes);
        $this->process($file);

        return $this;
    }

    /**
     * Build a stylesheet bundle.
     *
     * @param string|array $file
     * @param array        $attributes
     *
     * @return $this
     */
    public function stylesheet($file, $attributes = [])
    {
        $this->setProvider(new StyleSheet($this->publicPath, $this->providerConfig()), 'css', $attributes);
        $this->process($file);

        return $this;
    }

    /**
     * Build a stylesheet bundle from a directory.
     *
     * @param string $dir
     * @param array  $attributes
     *
     * @return $this
     */
    public function stylesheetDir($dir, $attributes = [])
    {
        $this->setProvider(new StyleSheet($this->publicPath, $this->providerConfig()), 'css', $attributes);

        return $this->assetDirHelper('css', $dir);
    }

    /**
     * Build a JavaScript bundle from a directory.
     *
     * @param string $dir
     * @param array  $attributes
     *
     * @return $this
     */
    public function javascriptDir($dir, $attributes = [])
    {
        $this->setProvider(new JavaScript($this->publicPath, $this->providerConfig()), 'js', $attributes);

        return $this->assetDirHelper('js', $dir);
    }

    /**
     * Render asset tags from a directory.
     *
     * @param string $extension
     * @param string $dir
     *
     * @return $this
     */
    private function assetDirHelper($extension, $dir)
    {
        $files = [];
        $iterator = new RecursiveDirectoryIterator($this->publicPath.$dir, RecursiveDirectoryIterator::SKIP_DOTS);

        foreach (new RecursiveIteratorIterator($iterator) as $fileInfo) {
            $filename = $fileInfo->getFilename();
            if (!$fileInfo->isDir() && pathinfo($filename, PATHINFO_EXTENSION) === $extension && strlen($filename) < 30) {
                $files[] = str_replace($this->publicPath, '', $fileInfo->getPathname());
            }
        }

        if ($files) {
            if ($this->config['reverse_sort']) {
                rsort($files);
            } else {
                sort($files);
            }
            $this->process($files);
        }

        return $this;
    }

    /**
     * Set the provider used for the current asset type.
     *
     * @param \App\Minify\Providers\BaseProvider $provider
     * @param string                              $extension
     * @param array                               $attributes
     *
     * @return void
     */
    private function setProvider($provider, $extension, array $attributes)
    {
        $this->provider = $provider;
        $this->buildPath = $this->config[$extension.'_build_path'];
        $this->attributes = $attributes;
        $this->buildExtension = $extension;
    }

    /**
     * Add and, where appropriate, minify the supplied files.
     *
     * @param string|array $file
     *
     * @return void
     */
    private function process($file)
    {
        $this->provider->add($file);

        if ($this->minifyForCurrentEnvironment() && $this->provider->make($this->buildPath)) {
            $this->provider->minify();
        }

        $this->fullUrl = false;
    }

    /**
     * Render the asset URL or tag.
     *
     * @return string
     */
    protected function render()
    {
        $baseUrl = $this->fullUrl ? $this->getBaseUrl() : '';
        if (!$this->minifyForCurrentEnvironment()) {
            return $this->provider->tags($baseUrl, $this->attributes);
        }

        $urlPath = $this->config[$this->buildExtension.'_url_path'] ?? $this->buildPath;
        $filename = $baseUrl.$urlPath.$this->provider->getFilename();

        if ($this->onlyUrl) {
            return $filename;
        }

        return $this->provider->tag($filename, $this->attributes);
    }

    /**
     * Determine whether assets should be minified in this environment.
     *
     * @return bool
     */
    protected function minifyForCurrentEnvironment()
    {
        return !in_array($this->environment, $this->config['ignore_environments']);
    }

    /**
     * Render the asset with an absolute base URL.
     *
     * @return $this
     */
    public function withFullUrl()
    {
        $this->fullUrl = true;

        return $this;
    }

    /**
     * Render only the generated asset URL.
     *
     * @return $this
     */
    public function onlyUrl()
    {
        $this->onlyUrl = true;

        return $this;
    }

    /**
     * Render the current asset.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Return the provider configuration.
     *
     * @return array
     */
    private function providerConfig()
    {
        return [
            'hash_salt' => $this->config['hash_salt'],
            'disable_mtime' => $this->config['disable_mtime'],
        ];
    }

    /**
     * Validate the required configuration.
     *
     * @param array $config
     *
     * @return void
     */
    private function checkConfiguration(array $config)
    {
        if (!isset($config['css_build_path']) || !is_string($config['css_build_path'])) {
            throw new InvalidArgumentException('Missing css_build_path field');
        }
        if (!isset($config['js_build_path']) || !is_string($config['js_build_path'])) {
            throw new InvalidArgumentException('Missing js_build_path field');
        }
        if (!isset($config['ignore_environments']) || !is_array($config['ignore_environments'])) {
            throw new InvalidArgumentException('Missing ignore_environments field');
        }
    }

    /**
     * Return the configured base URL.
     *
     * @return string
     */
    private function getBaseUrl()
    {
        if (empty(trim($this->config['base_url'] ?? ''))) {
            return Request::root();
        }

        return $this->config['base_url'];
    }
}
