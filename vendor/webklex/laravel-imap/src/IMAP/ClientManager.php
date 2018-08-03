<?php
/*
* File:     ClientManager.php
* Category: -
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\IMAP;

/**
 * Class ClientManager
 *
 * @package Webklex\IMAP
 */
class ClientManager {

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @var array $accounts
     */
    protected $accounts = [];

    /**
     * Create a new client manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    public function __construct($app) {
        $this->app = $app;
    }

    /**
     * Resolve a account instance.
     *
     * @param  string  $name
     *
     * @return Client
     */
    public function account($name = null) {
        $name = $name ?: $this->getDefaultAccount();

        // If the connection has not been resolved yet we will resolve it now as all
        // of the connections are resolved when they are actually needed so we do
        // not make any unnecessary connection to the various queue end-points.
        if (!isset($this->accounts[$name])) {
            $this->accounts[$name] = $this->resolve($name);
        }

        return $this->accounts[$name];
    }

    /**
     * Resolve a account.
     *
     * @param  string  $name
     *
     * @return Client
     */
    protected function resolve($name) {
        $config = $this->getConfig($name);

        return new Client($config);
    }

    /**
     * Get the account configuration.
     *
     * @param  string  $name
     *
     * @return array
     */
    protected function getConfig($name) {
        if ($name === null || $name === 'null') {
            return ['driver' => 'null'];
        }

        return $this->app['config']["imap.accounts.{$name}"];
    }

    /**
     * Get the name of the default account.
     *
     * @return string
     */
    public function getDefaultAccount() {
        return $this->app['config']['imap.default'];
    }

    /**
     * Set the name of the default account.
     *
     * @param  string  $name
     *
     * @return void
     */
    public function setDefaultAccount($name) {
        $this->app['config']['imap.default'] = $name;
    }

    /**
     * Dynamically pass calls to the default account.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters) {
        $callable = [$this->account(), $method];

        return call_user_func_array($callable, $parameters);
    }
}