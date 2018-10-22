<?php

namespace RachidLaasri\LaravelInstaller\Helpers;

class PermissionsChecker
{
    /**
     * @var array
     */
    protected $results = [];

    /**
     * Set the result array permissions and errors.
     *
     * @return mixed
     */
    public function __construct()
    {
        $this->results['permissions'] = [];

        $this->results['errors'] = null;
    }

    /**
     * Check for the folders permissions.
     *
     * @param array $folders
     * @return array
     */
    public function check(array $folders)
    {
        foreach($folders as $folder => $permission)
        {
            //if(!($this->getPermission($folder) >= $permission))
            if (!$this->isWritable($folder))
            {
                $this->addFileAndSetErrors($folder, $permission, false);
            }
            else {
                $this->addFile($folder, $permission, true);
            }
        }

        return $this->results;
    }

    /**
     * Get a folder permission.
     *
     * @param $folder
     * @return string
     */
    private function getPermission($folder)
    {
        return substr(sprintf('%o', fileperms(base_path($folder))), -4);
    }

    /**
     * Check if folder is writable by creating a temp file.
     * @param  [type] $folder [description]
     * @return [type]         [description]
     */
    private function isWritable($folder)
    {
        $path = base_path($folder);

        try {
            if (!file_exists($path)) {
                \File::makeDirectory($path, 0775, true);
            }
            $file = $path.'.installer_test';
            if ($file && file_put_contents($file, 'test')) {
                unlink($file);
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add the file to the list of results.
     *
     * @param $folder
     * @param $permission
     * @param $isSet
     */
    private function addFile($folder, $permission, $isSet)
    {
        array_push($this->results['permissions'], [
            'folder' => $folder,
            'permission' => $permission,
            'isSet' => $isSet
        ]);
    }

    /**
     * Add the file and set the errors.
     *
     * @param $folder
     * @param $permission
     * @param $isSet
     */
    private function addFileAndSetErrors($folder, $permission, $isSet)
    {
        $this->addFile($folder, $permission, $isSet);

        $this->results['errors'] = true;
    }
}