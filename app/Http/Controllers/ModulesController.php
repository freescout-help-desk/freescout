<?php

namespace App\Http\Controllers;

use App\Misc\WpApi;
use Illuminate\Http\Request;
//use Nwidart\Modules\Traits\CanClearModulesCache;
use Symfony\Component\Console\Output\BufferedOutput;

class ModulesController extends Controller
{
    //use CanClearModulesCache;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Modules.
     */
    public function modules(Request $request)
    {
        $installed_modules = [];
        $modules_directory = [];

        // Get available modules and cache them
        if (\Cache::has('modules_directory')) {
            $modules_directory = \Cache::get('modules_directory');
        }

        if (!$modules_directory) {
            $modules_directory = WpApi::getModules();
            if ($modules_directory) {
                \Cache::put('modules_directory', $modules_directory, now()->addMinutes(15));
            }
        }

        // Get installed modules
        $modules = \Module::all();
        foreach ($modules as $module) {
            $img = '';
            $installed_modules[] = [
                'alias'              => $module->getAlias(),
                'name'               => $module->getName(),
                'description'        => $module->getDescription(),
                'version'            => $module->get('version'),
                'detailsUrl'         => $module->get('detailsUrl'),
                'author'             => $module->get('author'),
                'authorUrl'          => $module->get('authorUrl'),
                'requiredAppVersion' => $module->get('requiredAppVersion'),
                'img'                => $img,
                'active'             => \App\Module::isActive($module->getAlias()),
                'installed'          => true,
                'activated'          => \App\Module::isLicenseActivated($module->getAlias(), $module->get('detailsUrl')),
            ];
        }

        // No need, as we update modules list on each page load
        // Clear modules cache if any module has been added or removed
        // if (count($modules) != count(Module::getCached())) {
        //     $this->clearCache();
        // }

        // Prepare directory modules
        foreach ($modules_directory as $i_dir => $dir_module) {
            // Remove installed modules from modules directory
            foreach ($installed_modules as $i_installed => $module) {
                if ($dir_module['alias'] == $module['alias']) {
                    // Set image from director
                    $installed_modules[$i_installed]['img'] = $dir_module['img'];
                    //unset($modules_directory[$i_dir]);
                    continue 2;
                }
            }
            $modules_directory[$i]['active'] = \App\Module::isActive($dir_module['alias']);
            $modules_directory[$i]['activated'] = false;
        }

        return view('modules/modules', [
            'installed_modules' => $installed_modules,
            'modules_directory' => $modules_directory
        ]);
    }
}
