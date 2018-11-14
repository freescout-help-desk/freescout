<?php

namespace Chumper\Zipper\Repositories;

use Exception;
use ZipArchive;

class ZipRepository implements RepositoryInterface
{
    private $archive;

    /**
     * Construct with a given path
     *
     * @param $filePath
     * @param bool $create
     * @param $archive
     *
     * @throws \Exception
     *
     * @return ZipRepository
     */
    public function __construct($filePath, $create = false, $archive = null)
    {
        //Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            throw new Exception('Error: Your PHP version is not compiled with zip support');
        }
        $this->archive = $archive ? $archive : new ZipArchive();

        $res = $this->archive->open($filePath, ($create ? ZipArchive::CREATE : null));
        if ($res !== true) {
            throw new Exception("Error: Failed to open $filePath! Error: ".$this->getErrorMessage($res));
        }
    }

    /**
     * Add a file to the opened Archive
     *
     * @param $pathToFile
     * @param $pathInArchive
     */
    public function addFile($pathToFile, $pathInArchive)
    {
        $this->archive->addFile($pathToFile, $pathInArchive);
    }

    /**
     * Add an empty directory
     *
     * @param $dirName
     */
    public function addEmptyDir($dirName)
    {
        $this->archive->addEmptyDir($dirName);
    }

    /**
     * Add a file to the opened Archive using its contents
     *
     * @param $name
     * @param $content
     */
    public function addFromString($name, $content)
    {
        $this->archive->addFromString($name, $content);
    }

    /**
     * Remove a file permanently from the Archive
     *
     * @param $pathInArchive
     */
    public function removeFile($pathInArchive)
    {
        $this->archive->deleteName($pathInArchive);
    }

    /**
     * Get the content of a file
     *
     * @param $pathInArchive
     *
     * @return string
     */
    public function getFileContent($pathInArchive)
    {
        return $this->archive->getFromName($pathInArchive);
    }

    /**
     * Get the stream of a file
     *
     * @param $pathInArchive
     *
     * @return mixed
     */
    public function getFileStream($pathInArchive)
    {
        return $this->archive->getStream($pathInArchive);
    }

    /**
     * Will loop over every item in the archive and will execute the callback on them
     * Will provide the filename for every item
     *
     * @param $callback
     */
    public function each($callback)
    {
        for ($i = 0; $i < $this->archive->numFiles; ++$i) {
            //skip if folder
            $stats = $this->archive->statIndex($i);
            if ($stats['size'] === 0 && $stats['crc'] === 0) {
                continue;
            }
            call_user_func_array($callback, [
                'file' => $this->archive->getNameIndex($i),
            ]);
        }
    }

    /**
     * Checks whether the file is in the archive
     *
     * @param $fileInArchive
     *
     * @return bool
     */
    public function fileExists($fileInArchive)
    {
        return $this->archive->locateName($fileInArchive) !== false;
    }

    /**
     * Sets the password to be used for decompressing
     * function named usePassword for clarity
     *
     * @param $password
     *
     * @return bool
     */
    public function usePassword($password)
    {
        return $this->archive->setPassword($password);
    }

    /**
     * Returns the status of the archive as a string
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->archive->getStatusString();
    }

    /**
     * Closes the archive and saves it
     */
    public function close()
    {
        @$this->archive->close();
    }

    private function getErrorMessage($resultCode)
    {
        switch ($resultCode) {
            case ZipArchive::ER_EXISTS:
                return 'ZipArchive::ER_EXISTS - File already exists.';
            case ZipArchive::ER_INCONS:
                return 'ZipArchive::ER_INCONS - Zip archive inconsistent.';
            case ZipArchive::ER_MEMORY:
                return 'ZipArchive::ER_MEMORY - Malloc failure.';
            case ZipArchive::ER_NOENT:
                return 'ZipArchive::ER_NOENT - No such file.';
            case ZipArchive::ER_NOZIP:
                return 'ZipArchive::ER_NOZIP - Not a zip archive.';
            case ZipArchive::ER_OPEN:
                return 'ZipArchive::ER_OPEN - Can\'t open file.';
            case ZipArchive::ER_READ:
                return 'ZipArchive::ER_READ - Read error.';
            case ZipArchive::ER_SEEK:
                return 'ZipArchive::ER_SEEK - Seek error.';
            default:
                return "An unknown error [$resultCode] has occurred.";
        }
    }
}
