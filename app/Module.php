<?php
/**
 * 'active' parameter in module.json is not taken in account.
 * Module 'active' flag is taken from DB.
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

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
        return parse_url($author_url, PHP_URL_HOST) == parse_url(\Config::get('app.freescout_url'), PHP_URL_HOST);
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
}
