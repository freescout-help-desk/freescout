<?php

namespace Codedge\Updater\Tests\SourceRepositoryTypes;

use Codedge\Updater\Events\UpdateAvailable;
use Codedge\Updater\SourceRepositoryTypes\GithubRepositoryType;
use Codedge\Updater\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Mockery;
use Psr\Http\Message\StreamInterface;

class GithubRepositoryTypeTest extends TestCase
{
    const GITHUB_API_URL = 'https://api.github.com';

    /**
     * @var Client;
     */
    protected $client;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    private $releasesAsJson;

    public function setUp()
    {
        parent::setUp();
        $this->config = $this->app['config']['self-update']['repository_types']['github'];
        $this->releasesAsJson = fopen('tests/Data/releases.json', 'r');

        $response = new Response(
            200,
            [
                'Content-Type' => 'application/json'
            ],
            \GuzzleHttp\Psr7\stream_for($this->releasesAsJson));

        $mock = new MockHandler([
            $response,
            $response,
            $response,
            $response
        ]);

        $handler = HandlerStack::create($mock);
        $this->client = new Client(['handler' => $handler]);
        $this->client->request(
            'GET',
            self::GITHUB_API_URL
            .'/repos/'
            .$this->config['repository_vendor'].'/'.$this->config['repository_name'].'/tags'
        );
    }

    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testIsNewVersionAvailableFailsWithInvalidArgumentException()
    {
        $class = new GithubRepositoryType($this->client, $this->config);
        $this->expectException("InvalidArgumentException");
        $class->isNewVersionAvailable();
    }

    public function testIsNewVersionAvailableTriggerUpdateAvailableEvent()
    {
        $class = new GithubRepositoryType($this->client, $this->config);
        $currentVersion = 'v1.1.0';

        $this->expectsEvents(UpdateAvailable::class);
        $this->assertTrue($class->isNewVersionAvailable($currentVersion));
    }

    public function testIsNewVersionAvailable()
    {
        $class = new GithubRepositoryType($this->client, $this->config);

        $currentVersion = 'v1.1.0';
        $this->assertTrue($class->isNewVersionAvailable($currentVersion));

        $currentVersion = 'v100.1';
        $this->assertFalse($class->isNewVersionAvailable($currentVersion));

    }

    public function testGetVersionAvailable()
    {
        $class = new GithubRepositoryType($this->client, $this->config);
        $this->assertNotEmpty($class->getVersionAvailable());
        $this->assertStringStartsWith('v', $class->getVersionAvailable('v'));
        $this->assertStringEndsWith('version', $class->getVersionAvailable('', 'version'));
    }

    public function testFetchingFailsWithException()
    {
        $class = new GithubRepositoryType($this->client, $this->config);
        $this->expectException(\Exception::class);
        $class->fetch();
    }
}