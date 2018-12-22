<?php

namespace Codedge\Updater\SourceRepositoryTypes;

use Codedge\Updater\AbstractRepositoryType;
use Codedge\Updater\Contracts\SourceRepositoryTypeContract;
use Codedge\Updater\Events\UpdateAvailable;
use Codedge\Updater\Events\UpdateFailed;
use Codedge\Updater\Events\UpdateSucceeded;
use File;
use GuzzleHttp\Client;
use Storage;
use Symfony\Component\Finder\Finder;

/**
 * Github.php.
 *
 * @author Holger Lösken <holger.loesken@codedge.de>
 * @copyright See LICENSE file that was distributed with this source code.
 */
class GithubRepositoryType extends AbstractRepositoryType implements SourceRepositoryTypeContract
{
    const GITHUB_API_URL = 'https://api.github.com';
    const NEW_VERSION_FILE = 'self-updater-new-version';

    /**
     * @var Client
     */
    protected $client;

    /**
     * Github constructor.
     *
     * @param Client $client
     * @param array  $config
     */
    public function __construct(Client $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
        $this->config['version_installed'] = config('self-update.version_installed');
        $this->config['exclude_folders'] = config('self-update.exclude_folders');
    }

    /**
     * Check repository if a newer version than the installed one is available.
     *
     * @param string $currentVersion
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     *
     * @return bool
     */
    public function isNewVersionAvailable($currentVersion = '')
    {
        $version = $currentVersion ?: $this->getVersionInstalled();

        if (!$version) {
            throw new \InvalidArgumentException('No currently installed version specified.');
        }

        if (version_compare($version, $this->getVersionAvailable(), '<')) {
            // if (!$this->versionFileExists()) {
            //     $this->setVersionFile($this->getVersionAvailable());
            //     event(new UpdateAvailable($this->getVersionAvailable()));
            // }

            return true;
        }

        return false;
    }

    /**
     * Fetches the latest version. If you do not want the latest version, specify one and pass it.
     *
     * @param string $version
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function fetch($version = '')
    {
        $response = $this->getRepositoryReleases();
        $releaseCollection = collect(\GuzzleHttp\json_decode($response->getBody()));

        if ($releaseCollection->isEmpty()) {
            throw new \Exception('Cannot find a release to update. Please check the repository you\'re pulling from');
        }

        $release = $releaseCollection->first();

        $storagePath = $this->config['download_path'];
        //$storagePath = storage_path().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'updater';

        if (!File::exists($storagePath)) {
            File::makeDirectory($storagePath, 493, true, true);
        }

        if (!empty($version)) {
            $release = $releaseCollection->where('name', $version)->first();
        }

        $storageFilename = "{$release->name}.zip";

        if (!$this->isSourceAlreadyFetched($release->name)) {
            $storageFile = $storagePath.DIRECTORY_SEPARATOR.$storageFilename;
            $this->downloadRelease($this->client, $release->zipball_url, $storageFile);

            $this->unzipArchive($storageFile, $storagePath);
            $this->createReleaseFolder($storagePath, $release->name);
        }
    }

    /**
     * Perform the actual update process.
     *
     * @param string $version
     *
     * @return bool
     */
    public function update($version = '')
    {
        $this->setPathToUpdate(base_path(), $this->config['exclude_folders']);

        if ($this->hasCorrectPermissionForUpdate()) {
            if (!empty($version)) {
                $sourcePath = $this->config['download_path'].DIRECTORY_SEPARATOR.$version;
            } else {
                $sourcePath = File::directories($this->config['download_path'])[0];
            }

            // Copy Vendor first.
            File::deleteDirectory(base_path('vendor_backup'));

            // We copy new vendor and rename to avoid error:
            // /vendor/composer/../laravel/framework/src/Illuminate/Foundation/Exceptions/Handler.php): failed to open stream: No such file or directory in /vendor/composer/ClassLoader.php:444
            File::move(
                $sourcePath.DIRECTORY_SEPARATOR.'vendor',
                base_path('vendor_new')
            );
            File::move(
                base_path('vendor'),
                base_path('vendor_backup')
            );
            File::move(
                base_path('vendor_new'),
                base_path('vendor')
            );

            // Move all directories first
            collect((new Finder())->in($sourcePath)->exclude($this->config['exclude_folders'])->directories()->sort(function ($a, $b) {
                return strlen($b->getRealpath()) - strlen($a->getRealpath());
            }))->each(function ($directory) { /** @var \SplFileInfo $directory */
                if (count(array_intersect(File::directories(
                        $directory->getRealPath()), $this->config['exclude_folders'])) == 0
                ) {
                    // Copy dir
                    File::copyDirectory(
                        $directory->getRealPath(),
                        base_path($directory->getRelativePath()).'/'.$directory->getBasename()
                    );
                }
                // Delete dir
                File::deleteDirectory($directory->getRealPath());
            });

            // Now move all the files left in the main directory
            //collect(File::allFiles($sourcePath, true))->each(function ($file) { /* @var \SplFileInfo $file */
            collect(File::files($sourcePath, true))->each(function ($file) { /* @var \SplFileInfo $file */
                File::copy($file->getRealPath(), base_path($file->getFilename()));
            });

            File::deleteDirectory($sourcePath);
            $this->deleteVersionFile();
            event(new UpdateSucceeded($version));

            return true;
        }

        event(new UpdateFailed($this));

        return false;
    }

    /**
     * Create a releas sub-folder inside the storage dir.
     *
     * @param string $storagePath
     * @param string $releaseName
     */
    public function createReleaseFolder($storagePath, $releaseName)
    {
        $subDirName = File::directories($storagePath);
        $directories = File::directories($subDirName[0]);

        File::makeDirectory($storagePath.'/'.$releaseName, 493, true, true);

        foreach ($directories as $directory) { /* @var string $directory */
            File::moveDirectory($directory, $storagePath.'/'.$releaseName.'/'.File::name($directory));
        }

        $files = File::allFiles($subDirName[0], true);
        foreach ($files as $file) { /* @var \SplFileInfo $file */
            File::move($file->getRealPath(), $storagePath.'/'.$releaseName.'/'.$file->getFilename());
        }

        File::deleteDirectory($subDirName[0]);
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
        return $prepend.$this->config['version_installed'].$append;
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
        // No need to save version to file.
        // if ($this->versionFileExists()) {
        //     $version = $prepend.$this->getVersionFile().$append;
        // } else {
        $response = $this->getRepositoryReleases();
        $releaseCollection = collect(\GuzzleHttp\json_decode($response->getBody()));
        $version = $prepend.$releaseCollection->first()->name.$append;
        //}

        return $version;
    }

    /**
     * Get all releases for a specific repository.
     *
     * @throws \Exception
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function getRepositoryReleases()
    {
        if (empty($this->config['repository_vendor']) || empty($this->config['repository_name'])) {
            throw new \Exception('No repository specified. Please enter a valid Github repository owner and name in your config.');
        }

        return $this->client->request(
            'GET',
            self::GITHUB_API_URL.'/repos/'.$this->config['repository_vendor'].'/'.$this->config['repository_name'].'/tags'
        );
    }

    /**
     * Check if the file with the new version already exists.
     *
     * @return bool
     */
    protected function versionFileExists()
    {
        return Storage::exists(static::NEW_VERSION_FILE);
    }

    /**
     * Write the version file.
     *
     * @param $content
     *
     * @return bool
     */
    protected function setVersionFile($content)
    {
        return Storage::put(static::NEW_VERSION_FILE, $content);
    }

    /**
     * Get the content of the version file.
     *
     * @return string
     */
    protected function getVersionFile()
    {
        return Storage::get(static::NEW_VERSION_FILE);
    }

    /**
     * Delete the version file.
     *
     * @return bool
     */
    protected function deleteVersionFile()
    {
        return Storage::delete(static::NEW_VERSION_FILE);
    }
}
