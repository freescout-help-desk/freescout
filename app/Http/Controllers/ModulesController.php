<?php

namespace App\Http\Controllers;

use App\Misc\WpApi;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\BufferedOutput;

class ModulesController extends Controller
{
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
        $available = [];

        // Get available modules and cache
        if (\Cache::has('available_modules')) {
            $available = \Cache::get('available_modules');
        } else {
            $api_response = WpApi::getModules();
            if ($api_response->isSuccessful()) {

            }
            if ($available) {
                \Cache::put('available_modules', $available, now()->addMinutes(15));
            }
        }

        return view('modules/modules', [
            'available' => $available
        ]);
    }
}
