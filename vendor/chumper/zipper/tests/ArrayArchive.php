<?php

namespace Chumper\Zipper;

use Chumper\Zipper\Repositories\RepositoryInterface;

class ArrayArchive implements RepositoryInterface
{
    private $entries = [];

    /**
     * Construct with a given path
     *
     * @param $filePath
     * @param bool $new
     * @param $archiveImplementation
     */
    public function __construct($filePath, $new = false, $archiveImplementation = null)
    {
    }

    /**
     * Add a file to the opened Archive
     *
     * @param $pathToFile
     * @param $pathInArchive
     */
    public function addFile($pathToFile, $pathInArchive)
    {
        $this->entries[$pathInArchive] = $pathInArchive;
    }

    /**
     * Add a file to the opened Archive using its contents
     *
     * @param $name
     * @param $content
     */
    public function addFromString($name, $content)
    {
        $this->entries[$name] = $name;
    }

    /**
     * Remove a file permanently from the Archive
     *
     * @param $pathInArchive
     */
    public function removeFile($pathInArchive)
    {
        unset($this->entries[$pathInArchive]);
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
        return $this->entries[$pathInArchive];
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
        return $this->entries[$pathInArchive];
    }

    /**
     * Will loop over every item in the archive and will execute the callback on them
     * Will provide the filename for every item
     *
     * @param $callback
     */
    public function each($callback)
    {
        foreach ($this->entries as $entry) {
            call_user_func_array($callback, [
                'file' => $entry,
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
        return array_key_exists($fileInArchive, $this->entries);
    }

    /**
     * Returns the status of the archive as a string
     *
     * @return string
     */
    public function getStatus()
    {
        return 'OK';
    }

    /**
     * Closes the archive and saves it
     */
    public function close()
    {
    }

    /**
     * Add an empty directory
     *
     * @param $dirName
     */
    public function addEmptyDir($dirName)
    {
        // CODE...
    }

    /**
     * Sets the password to be used for decompressing
     *
     * @param $password
     */
    public function usePassword($password)
    {
        // CODE...
    }
}
