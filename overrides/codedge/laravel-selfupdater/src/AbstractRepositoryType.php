<?php

namespace Codedge\Updater;

use File;
use GuzzleHttp\Client;
use Symfony\Component\Finder\Finder;
use Codedge\Updater\Events\HasWrongPermissions;

/**
 * AbstractRepositoryType.php.
 *
 * @author Holger Lösken <holger.loesken@codedge.de>
 * @copyright See LICENSE file that was distributed with this source code.
 */
abstract class AbstractRepositoryType
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Finder|SplFileInfo[]
     */
    protected $pathToUpdate;

    /**
     * Unzip an archive.
     *
     * @param string $file
     * @param string $targetDir
     * @param bool   $deleteZipArchive
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function unzipArchive($file = '', $targetDir = '', $deleteZipArchive = true)
    {
        if (empty($file) || ! File::exists($file)) {
            throw new \InvalidArgumentException("Archive [{$file}] cannot be found or is empty.");
        }

        $zip = new \ZipArchive();
        $res = $zip->open($file);

        if (! $res) {
            throw new \Exception("Cannot open zip archive [{$file}].");
        }

        if (empty($targetDir)) {
            $extracted = $zip->extractTo(File::dirname($file));
        } else {
            $extracted = $zip->extractTo($targetDir);
        }

        $zip->close();

        if ($extracted && $deleteZipArchive === true) {
            File::delete($file);
        }

        return true;
    }

    /**
     * Check a given directory recursively if all files are writeable.
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function hasCorrectPermissionForUpdate()
    {
        if (! $this->pathToUpdate) {
            throw new \Exception('No directory set for update. Please set the update with: setPathToUpdate(path).');
        }

        $collection = collect($this->pathToUpdate->files())->each(function ($file) { /* @var \SplFileInfo $file */
            if (! File::isWritable($file->getRealPath())) {
                event(new HasWrongPermissions($this));

                return false;
            }
        });

        return true;
    }

    /**
     * Download a file to a given location.
     *
     * @param Client $client
     * @param string $source
     * @param string $storagePath
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function downloadRelease(Client $client, $source, $storagePath)
    {
        return $client->request(
            'GET', $source, [
                'sink' => $storagePath,
                'timeout' => config('app.curl_timeout'),
                'connect_timeout' => config('app.curl_connect_timeout'),
                'proxy' => config('app.proxy'),
        ]);
    }

    /**
     * Check if the source has already been downloaded.
     *
     * @param string $version A specific version
     *
     * @return bool
     */
    protected function isSourceAlreadyFetched($version)
    {
        $storagePath = $this->config['download_path'].'/'.$version;
        if (! File::exists($storagePath) || empty(File::directories($storagePath))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Set the paths to be updated.
     *
     * @param string $path    Path where the update should be run into
     * @param array  $exclude List of folder names that shall not be updated
     */
    protected function setPathToUpdate($path, array $exclude)
    {
        $finder = (new Finder())->in($path)->exclude($exclude);

        $this->pathToUpdate = $finder;
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
}
