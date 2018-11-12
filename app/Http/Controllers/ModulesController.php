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

        $flashes = [];
        $flash = \Cache::get('modules_flash');
        if ($flash) {
            $flashes[] = $flash;
            \Cache::forget('modules_flash');
        }

        // Get available modules and cache them
        if (\Cache::has('modules_directory')) {
            $modules_directory = \Cache::get('modules_directory');
        }

        if (!$modules_directory) {
            $modules_directory = WpApi::getModules();
            if ($modules_directory && is_array($modules_directory) && count($modules_directory)) {
                \Cache::put('modules_directory', $modules_directory, now()->addMinutes(15));
            }
        }

        // Get installed modules
        \Module::clearCache();
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
                'active'             => $module->active(), //\App\Module::isActive($module->getAlias()),
                'installed'          => true,
                'activated'          => \App\Module::isLicenseActivated($module->getAlias(), $module->get('authorUrl')),
                'license'            => \App\Module::getLicense($module->getAlias()),
            ];
        }

        // No need, as we update modules list on each page load
        // Clear modules cache if any module has been added or removed
        // if (count($modules) != count(Module::getCached())) {
        //     $this->clearCache();
        // }

        // Prepare directory modules
        if (is_array($modules_directory)) {
            foreach ($modules_directory as $i_dir => $dir_module) {
                // Remove installed modules from modules directory
                foreach ($installed_modules as $i_installed => $module) {
                    if ($dir_module['alias'] == $module['alias']) {
                        // Set image from director
                        $installed_modules[$i_installed]['img'] = $dir_module['img'];
                        unset($modules_directory[$i_dir]);
                        continue 2;
                    }
                }

                if (empty($dir_module['authorUrl']) || !\App\Module::isOfficial($dir_module['authorUrl'])) {
                    unset($modules_directory[$i_dir]);
                    continue;
                }

                $modules_directory[$i_dir]['active'] = \App\Module::isActive($dir_module['alias']);
                $modules_directory[$i_dir]['activated'] = false;
            }
        } else {
            $modules_directory = [];
        }

        return view('modules/modules', [
            'installed_modules' => $installed_modules,
            'modules_directory' => $modules_directory,
            'flashes'           => $flashes,
        ]);
    }

    /**
     * Ajax.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {

            case 'install':
            case 'activate_license':
                $license = $request->license;
                $alias = $request->alias;

                if (!$license) {
                    $response['msg'] = __('Empty license key');
                }

                if (!$response['msg']) {
                    $params = [
                        'license'      => $license,
                        'module_alias' => $alias,
                        'url'          => \App\Module::getAppUrl(),
                    ];
                    $result = WpApi::activateLicense($params);

                    if (WpApi::$lastError) {
                        $response['msg'] = WpApi::$lastError['message'];
                    } elseif (!empty($result['code']) && !empty($result['message'])) {
                        $response['msg'] = $result['message'];
                    } else {
                        if (!empty($result['status']) && $result['status'] == 'valid') {
                            if ($request->action == 'install') {
                                // Download and install module
                                $license_details = WpApi::getVersion($params);

                                if (WpApi::$lastError) {
                                    $response['msg'] = WpApi::$lastError['message'];
                                } elseif (!empty($license_details['code']) && !empty($license_details['message'])) {
                                    $response['msg'] = $license_details['message'];
                                } elseif (!empty($license_details['download_link'])) {
                                    // Download module
                                    $module_archive = \Module::getPath().DIRECTORY_SEPARATOR.$alias.'.zip';

                                    try {
                                        \Helper::downloadRemoteFile($license_details['download_link'], $module_archive);
                                    } catch (\Exception $e) {
                                        $response['msg'] = $e->getMessage();
                                    }

                                    $download_error = false;
                                    if (!file_exists($module_archive)) {
                                        $download_error = true;
                                    } else {
                                        // Extract
                                        try {
                                            \Helper::unzip($module_archive, \Module::getPath());
                                        } catch (\Exception $e) {
                                            $response['msg'] = $e->getMessage();
                                        }
                                        // Check if extracted module exists
                                        \Module::clearCache();
                                        $module = \Module::findByAlias($alias);
                                        if (!$module) {
                                            $download_error = true;
                                        }
                                    }

                                    // Remove archive
                                    if (file_exists($module_archive)) {
                                        \File::delete($module_archive);
                                    }

                                    if (!$response['msg'] && !$download_error) {
                                        // Activate license
                                        \App\Module::activateLicense($alias, $license);

                                        \Session::flash('flash_success_floating', __('Module successfully installed!'));
                                        $response['status'] = 'success';
                                    } elseif ($download_error) {
                                        $response['reload'] = true;

                                        if ($response['msg']) {
                                            \Session::flash('flash_error_floating', $response['msg']);
                                        }

                                        \Session::flash('flash_error_unescaped', __('Error occured downloading the module. Please :%a_being%download:%a_end% module manually and extract into :folder', ['%a_being%' => '<a href="'.$license_details['download_link'].'" target="_blank">', '%a_end%' => '</a>', 'folder' => '<strong>'.\Module::getPath().'</strong>']));
                                    }
                                } else {
                                    $response['msg'] = __('Error occured, please try again later.');
                                }
                            } else {
                                // Just activate license
                                \App\Module::activateLicense($alias, $license);

                                \Session::flash('flash_success_floating', __('License successfully activated!'));
                                $response['status'] = 'success';
                            }
                        } elseif (!empty($result['error'])) {
                            switch ($result['error']) {
                                case 'missing':
                                    $response['msg'] = __('License key does not exist');
                                    break;
                                case 'license_not_activable':
                                    $response['msg'] = __("You have to activate each bundle's module separately");
                                    break;
                                case 'disabled':
                                    $response['msg'] = __('License key has been revoked');
                                    break;
                                case 'no_activations_left':
                                    $response['msg'] = __('No activations left for this license key');
                                    break;
                                case 'expired':
                                    $response['msg'] = __('License key has expired');
                                    break;
                                case 'key_mismatch':
                                    $response['msg'] = __('License key belongs to another module');
                                    break;
                                case 'invalid_item_id':
                                    $response['msg'] = __('Module not found in the modules directory');
                                    break;
                                default:
                                    $response['msg'] = __('Error code:'.' '.$result['error']);
                                    break;
                            }
                        } else {
                            $response['msg'] = __('Error occured, please try again later.');
                        }
                    }
                }
                break;

            case 'activate':
                $alias = $request->alias;
                $module = \Module::findByAlias($alias);

                if (!$module) {
                    $response['msg'] = __('Module not found').': '.$alias;
                }

                // Check license
                if (!$response['msg']) {
                    if (!empty($module->get('authorUrl')) && $module->isOfficial()) {
                        $params = [
                            'license'      => $module->getLicense(),
                            'module_alias' => $alias,
                            'url'          => \App\Module::getAppUrl(),
                        ];
                        $license_result = WpApi::checkLicense($params);

                        if (!empty($license_result['code']) && !empty($license_result['message'])) {
                            // Remove remembered license key and deactivate license in DB
                            \App\Module::deactivateLicense($alias, '');

                            $response['msg'] = $license_result['message'];
                        } elseif (!empty($license_result['status']) && $license_result['status'] != 'valid' && $license_result['status'] != 'inactive') {
                            // Remove remembered license key and deactivate license in DB
                            \App\Module::deactivateLicense($alias, '');

                            switch ($license_result['status']) {
                                case 'expired':
                                    $response['msg'] = __('License key has expired');
                                    break;
                                case 'disabled':
                                    $response['msg'] = __('License key has been revoked');
                                    break;
                                case 'inactive':
                                    $response['msg'] = __('License key has not been activated yet');
                                case 'site_inactive':
                                    $response['msg'] = __('This app has not been activated yet');
                                    break;
                            }
                        }
                    }
                }

                if (!$response['msg']) {
                    \App\Module::setActive($alias, true);

                    $outputLog = new BufferedOutput();
                    \Artisan::call('freescout:module-install', ['module_alias' => $alias], $outputLog);
                    $output = $outputLog->fetch();

                    // Get module name
                    $name = '?';
                    if ($module) {
                        $name = $module->getName();
                    }

                    $type = 'danger';
                    $msg = __('Error occured activating ":name" module', ['name' => $name]);
                    if (session('flashes_floating') && is_array(session('flashes_floating'))) {
                        // If there was any error, module has been deactivated via modules.register_error filter
                        $msg = '';
                        foreach (session('flashes_floating') as $flash) {
                            $msg .= $flash['text'].' ';
                        }
                    } elseif (strstr($output, 'Configuration cached successfully')) {
                        $type = 'success';
                        $msg = __('":name" module successfully activated!', ['name' => $name]);
                    } else {
                        // Deactivate module
                        \App\Module::setActive($alias, false);
                        \Artisan::call('freescout:clear-cache');
                    }

                    // \Session::flash does not work after BufferedOutput
                    $flash = [
                        'text'      => '<strong>'.$msg.'</strong><pre class="margin-top">'.$output.'</pre>',
                        'unescaped' => true,
                        'type'      => $type,
                    ];
                    \Cache::forever('modules_flash', $flash);
                    $response['status'] = 'success';
                }

                break;

            case 'deactivate':
                $alias = $request->alias;
                \App\Module::setActive($alias, false);

                $outputLog = new BufferedOutput();
                \Artisan::call('freescout:clear-cache', [], $outputLog);
                $output = $outputLog->fetch();

                // Get module name
                $module = \Module::findByAlias($alias);
                $name = '?';
                if ($module) {
                    $name = $module->getName();
                }

                $type = 'danger';
                $msg = __('Error occured deactivating :name module', ['name' => $name]);
                if (strstr($output, 'Configuration cached successfully')) {
                    $type = 'success';
                    $msg = __('":name" module successfully DEactivated!', ['name' => $name]);
                }

                // \Session::flash does not work after BufferedOutput
                $flash = [
                    'text'      => '<strong>'.$msg.'</strong><pre class="margin-top">'.$output.'</pre>',
                    'unescaped' => true,
                    'type'      => $type,
                ];
                \Cache::forever('modules_flash', $flash);
                $response['status'] = 'success';
                break;

            case 'delete':
                $alias = $request->alias;

                $module = \Module::findByAlias($alias);

                if ($module) {

                    //\App\Module::deactivateLicense($alias, $license);

                    $module->delete();
                    \Session::flash('flash_success_floating', __('Module deleted'));
                } else {
                    $response['msg'] = __('Module not found').': '.$alias;
                }

                $response['status'] = 'success';
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
        }

        return \Response::json($response);
    }
}
