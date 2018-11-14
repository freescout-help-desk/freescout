<?php

namespace Codedge\Updater\Tests;

use Codedge\Updater\UpdaterFacade;
use Codedge\Updater\UpdaterServiceProvider;
use GuzzleHttp\Client;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected $client;

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('self-update', [
            'default' => 'github',
            'version_installed' => '',
            'repository_types' => [
                'github' => [
                    'type' => 'github',
                    'repository_vendor' => 'laravel',
                    'repository_name' => 'laravel',
                    'repository_url' => '',
                    'download_path' => '/tmp',
                ],
            ],
            'log_events' => false,
            'mail_to' => [
                'address' => '',
                'name' => '',
            ],
        ]);
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            UpdaterServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Updater' => UpdaterFacade::class,
        ];
    }
}