<?php

namespace Codedge\Updater;

use Illuminate\Support\Facades\Artisan;
use Codedge\Updater\Contracts\SourceRepositoryTypeContract;

/**
 * SourceRepository.
 *
 * @author Holger Lösken <holger.loesken@codedge.de>
 * @copyright See LICENSE file that was distributed with this source code.
 */
class SourceRepository implements SourceRepositoryTypeContract
{
    /**
     * @var SourceRepositoryTypeContract
     */
    protected $sourceRepository;

    /**
     * SourceRepository constructor.
     *
     * @param SourceRepositoryTypeContract $sourceRepository
     */
    public function __construct(SourceRepositoryTypeContract $sourceRepository)
    {
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * Fetches the latest version. If you do not want the latest version, specify one and pass it.
     *
     * @param string $version
     *
     * @return mixed
     */
    public function fetch($version = '')
    {
        $version = ! empty($version) ? $version : $this->getVersionAvailable();

        return $this->sourceRepository->fetch($version);
    }

    /**
     * Perform the actual update process.
     *
     * @param string $version       Define the version you want to update to
     * @param bool   $forceFetching Forces a fresh download of the latest update version
     *
     * @return bool
     */
    public function update($version = '', $forceFetching = true)
    {
        $version = ! empty($version) ? $version : $this->getVersionAvailable();

        if ($forceFetching) {
            $this->fetch($version);
        }

        $this->preUpdateArtisanCommands();
        $updateStatus = $this->sourceRepository->update($version);
        $this->postUpdateArtisanCommands();

        return $updateStatus;
    }

    /**
     * Check repository if a newer version than the installed one is available.
     *
     * @param string $currentVersion
     *
     * @return bool
     */
    public function isNewVersionAvailable($currentVersion = '')
    {
        return $this->sourceRepository->isNewVersionAvailable($currentVersion);
    }

    /**
     * Get the version that is currenly installed.
     * Example: 1.1.0 or v1.1.0 or "1.1.0 version".
     *
     * @param string $prepend
     * @param string $append
     *
     * @return string
     */
    public function getVersionInstalled($prepend = '', $append = '')
    {
        return $this->sourceRepository->getVersionInstalled($prepend, $append);
    }

    /**
     * Get the latest version that has been published in a certain repository.
     * Example: 2.6.5 or v2.6.5.
     *
     * @param string $prepend Prepend a string to the latest version
     * @param string $append  Append a string to the latest version
     *
     * @return string
     */
    public function getVersionAvailable($prepend = '', $append = '')
    {
        return $this->sourceRepository->getVersionAvailable($prepend, $append);
    }

    /**
     * Run pre update artisan commands from config.
     */
    protected function preUpdateArtisanCommands()
    {
        collect(config('self-update.artisan_commands.pre_update'))->every(function ($commandParams, $commandKey) {
            Artisan::call($commandKey, $commandParams['params']);
        });
    }

    /**
     * Run post update artisan commands from config.
     */
    protected function postUpdateArtisanCommands()
    {
        collect(config('self-update.artisan_commands.post_update'))->every(function ($commandParams, $commandKey) {
            Artisan::call($commandKey, $commandParams['params']);
        });
    }
}
