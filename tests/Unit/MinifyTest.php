<?php

namespace Tests\Unit;

use App\Minify\Minify;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class MinifyTest extends TestCase
{
    /**
     * Temporary public directory.
     *
     * @var string
     */
    private $publicPath;

    /**
     * Filesystem helper used to manage test files.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->publicPath = sys_get_temp_dir().'/freescout-minify-'.uniqid();
        $this->filesystem->makeDirectory($this->publicPath.'/css', 0775, true);
        $this->filesystem->makeDirectory($this->publicPath.'/js', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->publicPath);

        parent::tearDown();
    }

    public function testStylesheetPreservesModernCssSyntax()
    {
        $css = ':root { --sidebar-width: 360px; }'
            .'.sidebar { width: clamp(280px, 25vw, var(--sidebar-width)); color: var(--missing, rgb(1 2 3 / 50%)); }'
            .'@layer base { @container card (width > 30rem) { .item { color: color-mix(in oklab, red 40%, blue); &:hover { color: blue; } } } }';
        file_put_contents($this->publicPath.'/css/source.css', $css);

        $url = (string) $this->minify('production')->stylesheet('/css/source.css')->onlyUrl();
        $output = file_get_contents($this->publicPath.$url);

        $this->assertStringContainsString('--sidebar-width:360px', $output);
        $this->assertStringContainsString('clamp(280px,25vw,var(--sidebar-width))', $output);
        $this->assertStringContainsString('var(--missing,rgb(1 2 3 / 50%))', $output);
        $this->assertStringContainsString('@layer base{', $output);
        $this->assertStringContainsString('@container card (width > 30rem){', $output);
        $this->assertStringContainsString('color-mix(in oklab,red 40%,blue)', $output);
        $this->assertStringContainsString('&:hover{color:blue}', $output);
    }

    public function testLocalEnvironmentRendersIndividualFilesInOrder()
    {
        file_put_contents($this->publicPath.'/css/first.css', '.first { color: red; }');
        file_put_contents($this->publicPath.'/css/second.css', '.second { color: blue; }');

        $html = (string) $this->minify('local')->stylesheet(
            ['/css/first.css', '/css/second.css'],
            ['media' => 'screen']
        );

        $this->assertSame(
            '<link href="/css/first.css" rel="stylesheet" media="screen">'.PHP_EOL
            .'<link href="/css/second.css" rel="stylesheet" media="screen">'.PHP_EOL,
            $html
        );
    }

    public function testStylesheetBundlePreservesSourceOrderAndRelativeUrls()
    {
        file_put_contents($this->publicPath.'/css/core.css', '.core { color: red; }');
        file_put_contents(
            $this->publicPath.'/css/module.css',
            '.module { background-image: url("../img/module.svg"); }'
        );

        $url = (string) $this->minify('production')
            ->stylesheet(['/css/core.css', '/css/module.css'])
            ->onlyUrl();

        $this->assertSame(
            '.core{color:red}.module{background-image:url("../img/module.svg")}',
            file_get_contents($this->publicPath.$url)
        );
    }

    public function testProductionBundleIsCachedAndInvalidatedByModificationTime()
    {
        $source = $this->publicPath.'/css/source.css';
        file_put_contents($source, '.first { color: red; }');

        $firstUrl = (string) $this->minify('production')->stylesheet('/css/source.css')->onlyUrl();
        $cachedUrl = (string) $this->minify('production')->stylesheet('/css/source.css')->onlyUrl();

        $this->assertSame($firstUrl, $cachedUrl);
        $this->assertFileExists($this->publicPath.$firstUrl);

        file_put_contents($source, '.first { color: blue; }');
        touch($source, filemtime($source) + 1);
        clearstatcache(true, $source);

        $updatedUrl = (string) $this->minify('production')->stylesheet('/css/source.css')->onlyUrl();

        $this->assertNotSame($firstUrl, $updatedUrl);
        $this->assertFileDoesNotExist($this->publicPath.$firstUrl);
        $this->assertFileExists($this->publicPath.$updatedUrl);
        $this->assertSame('.first{color:blue}', file_get_contents($this->publicPath.$updatedUrl));
    }

    public function testJavaScriptStillUsesJshrink()
    {
        file_put_contents($this->publicPath.'/js/source.js', 'function add(a, b) { return a + b; }');

        $url = (string) $this->minify('production')->javascript('/js/source.js')->onlyUrl();
        $output = file_get_contents($this->publicPath.$url);

        $this->assertSame('function add(a,b){return a+b;}', $output);
    }

    public function testMissingSourceFileRaisesAnException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $this->minify('production')->stylesheet('/css/missing.css');
    }

    public function testFullUrlUsesConfiguredBaseUrl()
    {
        file_put_contents($this->publicPath.'/css/source.css', '.source { color: red; }');

        $html = (string) $this->minify('local')->stylesheet('/css/source.css')->withFullUrl();

        $this->assertSame(
            '<link href="https://example.test/css/source.css" rel="stylesheet">'.PHP_EOL,
            $html
        );
    }

    /**
     * Create a configured minifier.
     *
     * @param string $environment
     *
     * @return \App\Minify\Minify
     */
    private function minify($environment)
    {
        return new Minify([
            'reverse_sort' => true,
            'ignore_environments' => ['local'],
            'css_build_path' => '/css/builds/',
            'css_url_path' => '/css/builds/',
            'js_build_path' => '/js/builds/',
            'js_url_path' => '/js/builds/',
            'disable_mtime' => false,
            'hash_salt' => '',
            'base_url' => 'https://example.test',
        ], $environment, $this->publicPath);
    }
}
