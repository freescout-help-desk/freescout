<?php
/**
 * php artisan module:migrate modulename.
 *
 * Replaces the command from nwidart/laravel-modules, which passes the module's
 * migrations folder to the migrate command after removing base_path() from it.
 * Module::getPath() is a realpath(), so on installations where the Modules
 * folder is a symlink leading outside of the application folder (a common
 * container layout: /var/www/html/Modules -> /data/Modules) base_path() does
 * not occur in that path. The value stays absolute, migrate prefixes
 * base_path() to it anyway (Illuminate\Database\Console\Migrations\
 * BaseCommand::getMigrationPaths()), the resulting folder does not exist and
 * the migration reports "Nothing to migrate" with a zero exit code.
 *
 * https://github.com/freescout-help-desk/freescout/issues/5569
 */

namespace App\Console\Commands;

use Nwidart\Modules\Commands\MigrateCommand;
use Nwidart\Modules\Migrations\Migrator;
use Nwidart\Modules\Module;

class ModuleMigrate extends MigrateCommand
{
    /**
     * Run the migrations of the specified module.
     *
     * @param Module $module
     */
    protected function migrate(Module $module)
    {
        $path = $this->getMigrationPath($module);

        if ($this->option('subpath')) {
            $path = $path.'/'.$this->option('subpath');
        }

        $this->call('migrate', [
            '--path'     => $path,
            '--database' => $this->option('database'),
            '--pretend'  => $this->option('pretend'),
            '--force'    => $this->option('force'),
        ]);

        if ($this->option('seed')) {
            $this->call('module:seed', ['module' => $module->getName()]);
        }
    }

    /**
     * Get the module's migrations folder relative to base_path(), as the
     * migrate command prepends base_path() to whatever --path receives.
     *
     * @param Module $module
     *
     * @return string
     */
    protected function getMigrationPath(Module $module)
    {
        $absolute_path = (new Migrator($module))->getPath();

        $path = str_replace(base_path(), '', $absolute_path);

        if ($path != $absolute_path) {
            // The module is located inside of the application folder.
            return $path;
        }

        // The module path is a realpath() leading outside of the application
        // folder. Rebuild the path from the folder the module has been scanned
        // from, which still contains the symlink and is therefore located
        // inside of the application folder.
        $module_path = $module->getPath();

        if (method_exists($module, 'getScannedPath')
            && $module_path
            && strpos($absolute_path, $module_path) === 0
        ) {
            $scanned_path = str_replace(base_path(), '', $module->getScannedPath());
            $scanned_path .= substr($absolute_path, strlen($module_path));

            if (is_dir(base_path().DIRECTORY_SEPARATOR.$scanned_path)) {
                return $scanned_path;
            }
        }

        // Neither path is located inside of the application folder, so the
        // migrate command can not resolve it. Tell the user instead of
        // reporting "Nothing to migrate".
        $this->error('Module migrations folder is located outside of the application folder: '.$absolute_path);
        $this->error('Run "php artisan migrate" to apply the module migrations.');

        return $path;
    }
}
