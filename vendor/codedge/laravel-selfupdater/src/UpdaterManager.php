<?php

namespace Codedge\Updater;

use Closure;
use GuzzleHttp\Client;
use Illuminate\Foundation\Application;
use Codedge\Updater\Contracts\UpdaterContract;
use Codedge\Updater\Contracts\SourceRepositoryTypeContract;
use Codedge\Updater\SourceRepositoryTypes\GithubRepositoryType;

/**
 * Updater.php.
 *
 * @author Holger Lösken <holger.loesken@codedge.de>
 * @copyright See LICENSE file that was distributed with this source code.
 */
class UpdaterManager implements UpdaterContract
{
    /**
     * Application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $sources = [];

    /**
     * @var array
     */
    protected $customSourceCreators = [];

    /**
     * Create a new Updater manager instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a source repository type instance.
     *
     * @param string $name
     *
     * @return SourceRepository
     */
    public function source($name = '')
    {
        $name = ! empty($name) ? $name : $this->getDefaultSourceRepository();

        return $this->sources[$name] = $this->get($name);
    }

    /**
     * Get the default source repository type.
     *
     * @return string
     */
    public function getDefaultSourceRepository()
    {
        return $this->app['config']['self-update']['default'];
    }

    /**
     * @param SourceRepositoryTypeContract $sourceRepository
     *
     * @return SourceRepository
     */
    public function sourceRepository(SourceRepositoryTypeContract $sourceRepository)
    {
        return new SourceRepository($sourceRepository);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param string  $source
     * @param Closure $callback
     *
     * @return $this
     */
    public function extend($source, Closure $callback)
    {
        $this->customRepositoryTypes[$source] = $callback;

        return $this;
    }

    /**
     * Dynamically call the default source repository instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->source(), $method], $parameters);
    }

    /**
     * Get the source repository connection configuration.
     *
     * @param string $name
     *
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']['self-update']['repository_types'][$name];
    }

    /**
     * Attempt to get the right source repository instance.
     *
     * @param string $name
     *
     * @return SourceRepositoryTypeContract
     */
    protected function get($name)
    {
        return isset($this->sources[$name]) ? $this->sources[$name] : $this->resolve($name);
    }

    /**
     * Try to find the correct source repository implementation ;-).
     *
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Source repository [{$name}] is not defined.");
        }

        if (isset($this->customSourceCreators[$config['type']])) {
            return $this->callCustomSourceCreators($config);
        }
        $repositoryMethod = 'create'.ucfirst($name).'Repository';

        if (method_exists($this, $repositoryMethod)) {
            return $this->{$repositoryMethod}($config);
        }
        throw new InvalidArgumentException("Repository [{$name}] is not supported.");
    }

    /**
     * Create an instance for the Github source repository.
     *
     * @param array $config
     *
     * @return SourceRepository
     */
    protected function createGithubRepository(array $config)
    {
        $client = new Client();

        return $this->sourceRepository(new GithubRepositoryType($client, $config));
    }

    /**
     * Call a custom source repository type.
     *
     * @param array $config
     *
     * @return mixed
     */
    protected function callCustomSourceCreators(array $config)
    {
        return $this->customSourceCreators[$config['type']]($this->app, $config);
    }
}
