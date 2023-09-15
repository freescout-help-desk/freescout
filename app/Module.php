<?php
/**
 * 'active' parameter in module.json is not taken in account.
 * Module 'active' flag is taken from DB.
 */

namespace App;

use App\Misc\WpApi;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Output\BufferedOutput;

class Module extends Model
{
    const IMG_DEFAULT = '/img/default-module.png';

    public $timestamps = false;

    /**
     * Modules list cached in memory.
     */
    public static $modules;

    public static function getCached()
    {
        if (!self::$modules) {
            // At this stage modules table may not exist
            try {
                self::$modules = self::all();
            } catch (\Exception $e) {
                // Do nothing
            }
        }

        return self::$modules;
    }

    public static function isActive($alias)
    {
        $module = self::getByAlias($alias);
        if ($module) {
            return $module->active;
        } else {
            return false;
        }
    }

    public static function setActive($alias, $active, $save = true)
    {
        $module = self::getByAliasOrCreate($alias);
        $module->active = $active;
        if ($save) {
            $module->save();
        }

        return true;
    }

    /**
     * Is module license activated.
     */
    public static function isLicenseActivated($alias, $author_url)
    {
        // If module is from modules directory, license activation is required
        if ($author_url && self::isOfficial($author_url)) {
            $module = self::getByAlias($alias);
            if ($module) {
                return $module->activated;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public static function isOfficial($author_url)
    {
        return parse_url($author_url ?? '', PHP_URL_HOST) == parse_url(\Config::get('app.freescout_url') ?? '', PHP_URL_HOST);
    }

    /**
     * Activate module license.
     *
     * @param [type] $alias       [description]
     * @param [type] $details_url [description]
     *
     * @return bool [description]
     */
    public static function activateLicense($alias, $license)
    {
        $module = self::getByAliasOrCreate($alias);
        $module->license = $license;
        $module->activated = true;
        $module->save();
    }

    public static function deactivateLicense($alias, $license)
    {
        $module = self::getByAliasOrCreate($alias);
        $module->license = $license;
        $module->activated = false;
        $module->save();
    }

    public static function getByAliasOrCreate($alias)
    {
        $module = self::getByAlias($alias);
        if (!$module) {
            $module = new self();
            $module->alias = $alias;
        }

        return $module;
    }

    /**
     * Get module license.
     */
    public static function getLicense($alias)
    {
        $module = self::getByAlias($alias);
        if ($module) {
            return $module->license;
        } else {
            return '';
        }
    }

    public static function setLicense($alias, $license)
    {
        $module = self::getByAliasOrCreate($alias);
        $module->license = $license;
        $module->save();
    }

    public static function normalizeAlias($alias)
    {
        return trim(strtolower($alias));
    }

    public static function getByAlias($alias)
    {
        $modules = self::getCached();
        if ($modules) {
            return self::getCached()->where('alias', $alias)->first();
        } else {
            return;
        }
    }

    /**
     * Deactivate module and update modules cache.
     */
    public static function deactiveModule($alias, $clear_app_cache = true)
    {
        self::setActive($alias, false);
        // Update modules cache
        \Module::clearCache();
        if ($clear_app_cache) {
            \Artisan::call('freescout:clear-cache');
        }
    }

    /**
     * Get URL used to active and check license.
     *
     * @return [type] [description]
     */
    public static function getAppUrl()
    {
        return parse_url(\Config::get('app.url'), PHP_URL_HOST);
    }

    /**
     * Check missing extensions among required by module.
     *
     * @param [type] $required_extensions [description]
     *
     * @return [type] [description]
     */
    public static function getMissingExtensions($required_extensions)
    {
        $missing = [];

        $list = explode(',', $required_extensions ?? '');
        if (!is_array($list) || !count($list)) {
            return [];
        }
        foreach ($list as $ext) {
            $ext = trim($ext);
            if ($ext && !extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        return $missing;
    }

    /**
     * Check missing modules required by the module.
     */
    public static function getMissingModules($required_modules, $modules = [])
    {
        $missing = [];

        if (!$modules) {
            $modules = \Module::all();
        }

        if (!is_array($required_modules) || !count($required_modules)) {
            return [];
        }
        foreach ($required_modules as $alias => $version) {
            $module = null;
            foreach ($modules as $module_item) {
                if ($module_item->alias == $alias) {
                    $module = $module_item;
                }
            }
            if (!$module) {
                $missing[$alias] = $version;
                continue;
            }

            if (!self::isActive($alias) || !version_compare($module->version, $version, '>=')) {
                $missing[$alias] = $version;
            }
        }

        return $missing;
    }

    public static function formatName($name)
    {
        return preg_replace("/ Module($|.*\[.*\]$)/", '$1', $name);
    }

    public static function formatModuleData($module_data)
    {
        // Add (Third-Party).
        if (\App\Module::isOfficial($module_data['authorUrl']) 
            && $module_data['author'] != 'FreeScout'
            && mb_substr(trim($module_data['name']), -1)  != ']'
        ) {
            $module_data['name'] = $module_data['name'].' ['.__('Third-Party').']';
        }
        return $module_data;
    }

    public static function isThirdParty($module_data)
    {
        if (\App\Module::isOfficial($module_data['authorUrl']) 
            && $module_data['author'] != 'FreeScout'
        ) {
            return true;
        } else {
            return false;
        }
    }

    public static function getSymlinkPath($alias)
    {
        return public_path().\Module::getPublicPath($alias);
    }

    // Check and try to fix invalid or missing symlinks.
    public static function checkSymlinks($module_aliases = null)
    {
        $invalid_symlinks = [];

        if ($module_aliases === null) {
            // Get all active modules.
            foreach (\Module::all() as $module) {
                if ($module->active()) {
                    $module_aliases[] = $module->getAlias();
                }
            }
        }
        if ($module_aliases && count($module_aliases)) {
            foreach ($module_aliases as $module_alias) {
                $from = self::getSymlinkPath($module_alias);

                $create = false;

                // file_exists() also checks if symlink target exists.
                // file_exists() and is_dir() may throw "open_basedir restriction in effect".
                try {
                    if (!file_exists($from) || !is_link($from)) {
                        if (is_dir($from)) {
                            @rename($from, $from.'_'.date('YmdHis'));
                        } else {
                            @unlink($from);
                        }
                        $create = true;
                    } 
                } catch (\Exception $e) {
                    $create = true;
                }

                // Skip this check.
                // elseif (is_link($from) && readlink($symlink_path) != '') {
                //     // Symlink leads to the wrong place.
                //     $create = true;
                // }

                // Try to create the symlink.
                if ($create) {
                    $to = self::createModuleSymlink($module_alias);

                    if ($to && (!is_link($from) || is_link($to) || !file_exists($from))) {
                        $invalid_symlinks[$from] = $to;
                    }
                }
            }
        }

        return $invalid_symlinks;
    }

    // There is similar function in ModuleInstall.php
    public static function createModuleSymlink($alias)
    {
        $from = self::getSymlinkPath($alias);

        $module = \Module::findByAlias($alias);
        if (!$module) {
            return false;
        }

        $to = $module->getExtraPath('Public');

        // file_exists() may throw "open_basedir restriction in effect".
        try {
            // If module's Public is symlink.
            if (is_link($to)) {
                @unlink($to);
            }

            // Symlimk may exist but lead to the module folder in a wrong case.
            // So we need first try to remove it.
            if (!file_exists($from)) {
                @unlink($from);
            }

            if (file_exists($from)) {
                return $to;
            }

            if (!file_exists($to)) {
                // Try to create Public folder.
                try {
                    \File::makeDirectory($to, \Helper::DIR_PERMISSIONS);
                } catch (\Exception $e) {
                    // If it's a broken symlink.
                    if (is_link($to)) {
                        @unlink($to);
                    }
                }
            }

            try {
                symlink($to, $from);
            } catch (\Exception $e) {
                \Log::error('Error occurred creating ['.$from.' » '.$to.'] symlink: '.$e->getMessage());
                //return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return $to;
    }

    public static function updateModule($alias)
    {
        $result = [
            'status' => 'error',
            // Error message.
            'msg' => '',
            'msg_success' => '',
            'download_error' => false,
            // Error message with the link for downloading the module.
            'download_msg' => '',
            'output' => '',
            'module_name' => '',
        ];

        $module = \Module::findByAlias($alias);

        if (!$module) {
            $result['msg'] = __('Module not found').': '.$alias;
        }

        // Get module name.
        $name = '?';
        if ($module) {
            $name = $module->getName();
            $result['module_name'] = $name;
        }

        // Download new version.
        if (!$result['msg']) {
            $params = [
                'license'      => self::getLicense($alias),
                'module_alias' => $alias,
                'url'          => self::getAppUrl(),
            ];
            $license_details = WpApi::getVersion($params);

            if (WpApi::$lastError) {
                $result['msg'] = WpApi::$lastError['message'];
            } elseif (!empty($license_details['code']) && !empty($license_details['message'])) {
                $result['msg'] = $license_details['message'];
            } elseif (!empty($license_details['required_app_version']) && !\Helper::checkAppVersion($license_details['required_app_version'])) {
                $result['msg'] = 'Module requires app version:'.' '.$license_details['required_app_version'];
            } elseif (!empty($license_details['download_link'])) {
                // Download module.
                $module_archive = \Module::getPath().DIRECTORY_SEPARATOR.$alias.'.zip';

                try {
                    \Helper::downloadRemoteFile($license_details['download_link'], $module_archive);
                } catch (\Exception $e) {
                    $result['msg'] = $e->getMessage();
                }

                if (!file_exists($module_archive)) {
                    $result['download_error'] = true;
                } else {
                    // Extract.
                    try {
                        // Sometimes by some reason Public folder becomes a symlink leading to itself.
                        // It causes an error during updating process.
                        // https://github.com/freescout-helpdesk/freescout/issues/2709
                        $public_folder = $module->getPath().DIRECTORY_SEPARATOR.'Public';
                        try {
                            if (is_link($public_folder)) {
                                unlink($public_folder);
                            }
                        } catch (\Exception $e) {
                            // Do nothing.
                        }

                        \Helper::unzip($module_archive, \Module::getPath());
                    } catch (\Exception $e) {
                        $result['msg'] = $e->getMessage();
                    }
                    // Check if extracted module exists.
                    \Module::clearCache();
                    $module = \Module::findByAlias($alias);
                    if (!$module) {
                        $result['download_error'] = true;
                    }
                }

                // Remove archive.
                if (file_exists($module_archive)) {
                    \File::delete($module_archive);
                }

                if ($result['download_error']) {
                    $result['download_msg'] = __('Error occurred downloading the module. Please :%a_being%download:%a_end% module manually and extract into :folder', ['%a_being%' => '<a href="'.$license_details['download_link'].'" target="_blank">', '%a_end%' => '</a>', 'folder' => '<strong>'.\Module::getPath().'</strong>']);
                }
            } elseif ($license_details['status'] && $result['msg'] = self::getErrorMessage($license_details['status'])) {
                //$result['msg'] = ;
            } else {
                $result['msg'] = __('Error occurred').': '.json_encode($license_details);
            }
        }

        // Run post-update instructions.
        if (!$result['msg'] && !$result['download_error']) {

            $output_log = new BufferedOutput();
            \Artisan::call('freescout:module-install', ['module_alias' => $alias], $output_log);
            $result['output'] = $output_log->fetch() ?: ' ';

            $result['msg'] = __('Error occurred activating ":name" module', ['name' => $name]);

            if (session('flashes_floating') && is_array(session('flashes_floating'))) {
                // Error.
                // If there was any error, module has been deactivated via modules.register_error filter
                $result['msg'] = '';
                foreach (session('flashes_floating') as $flash) {
                    $result['msg'] .= $flash['text'].' ';
                }
            } elseif (strstr($result['output'], 'Configuration cached successfully')) {
                // Success.
                $result['status'] = 'success';
                $result['msg'] = '';
                $result['msg_success'] = __('":name" module successfully updated!', ['name' => $name]);
            } else {
                // Error.
                // Deactivate module.
                \App\Module::setActive($alias, false);
                \Artisan::call('freescout:clear-cache');
            }
        }

        return $result;
    }

    public static function getErrorMessage($code, $result = null)
    {
        $msg = '';

        switch ($code) {
            case 'missing':
                $msg = __('License key does not exist');
                break;
            case 'license_not_activable':
                $msg = __("You have to activate each bundle's module separately");
                break;
            case 'disabled':
                $msg = __('License key has been revoked');
                break;
            case 'no_activations_left':
                $msg = __('No activations left for this license key').' ('.__("Use 'Deactivate License' link above to transfer license key from another domain").')';
                break;
            case 'expired':
                $msg = __('License key has expired');
                break;
            case 'key_mismatch':
                $msg = __('License key belongs to another module');
                break;
            // This also happens when entering a valid license key for wrong module.
            case 'invalid_item_id':
                $msg = __('Invalid license key');
                //$msg = __('Module not found in the modules directory');
                break;
            case 'site_inactive':
                $msg = __('License key is activated on another domain.').' '.__("Use 'Deactivate License' link above to transfer license key from another domain");
                //$msg = __('Module not found in the modules directory');
                break;
            default:
                if ($result && !empty($result['error'])) {
                    $msg = __('Error code:'.' '.$result['error']);
                }
                break;
        }

        return $msg;
    }
}
