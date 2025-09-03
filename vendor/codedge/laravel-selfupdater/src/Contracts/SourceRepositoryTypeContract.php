<?php

namespace Codedge\Updater\Contracts;

interface SourceRepositoryTypeContract
{
    /**
     * Fetches the latest version. If you do not want the latest version, specify one and pass it.
     *
     * @param string $version
     *
     * @return mixed
     */
    public function fetch($version = '');

    /**
     * Perform the actual update process.
     *
     * @return bool
     */
    public function update();

    /**
     * Check repository if a newer version than the installed one is available.
     * Caution: v.1.1 compared to 1.1 is not the same. Check to actually compare correct version, including letters
     * before or after.
     *
     * @param string $currentVersion
     *
     * @return bool
     */
    public function isNewVersionAvailable($currentVersion = '');

    /**
     * Get the version that is currenly installed.
     * Example: 1.1.0 or v1.1.0 or "1.1.0 version".
     *
     * @param string $prepend
     * @param string $append
     *
     * @return string
     */
    public function getVersionInstalled($prepend = '', $append = '');

    /**
     * Get the latest version that has been published in a certain repository.
     * Example: 2.6.5 or v2.6.5.
     *
     * @param string $prepend Prepend a string to the latest version
     * @param string $append  Append a string to the latest version
     *
     * @return string
     */
    public function getVersionAvailable($prepend = '', $append = '');
}
