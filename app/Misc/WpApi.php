<?php

namespace App\Misc;

use Zttp\Zttp;

class WpApi
{
    const ENDPOINT_MODULES = 'freescout/v1/modules';

    const METHOD_GET = 'get';
    const METHOD_POST = 'post';

    const ACTION_CHECK_LICENSE = 'check_license';
    const ACTION_ACTIVATE_LICENSE = 'activate_license';
    const ACTION_DEACTIVATE_LICENSE = 'deactivate_license';
    const ACTION_GET_VERSION = 'get_version';

    public static $lastError;

    public static function url($path, $alternative = false)
    {
        if ($alternative) {
            return \Config::get('app.freescout_alt_api').$path;
        } else {
            return \Config::get('app.freescout_api').$path;
            
        }
    }

    /**
     * API request.
     */
    public static function request($method, $endpoint, $params = [], $alternative_api = false)
    {
        self::$lastError = null;

        $options = ['connect_timeout' => 7]; // seconds

        try {
            $response = Zttp::withOptions($options)->$method(self::url($endpoint, $alternative_api), $params);
        } catch (\Exception $e) {
            if (!$alternative_api) {
                return self::request($method, $endpoint, $params, true);
            }
            self::$lastError = [
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            return [];
        }

        // https://guzzle3.readthedocs.io/http-client/response.html
        if ($response->status() < 500) {
            $json = $response->json();
            if (!empty($json['code']) && !empty($json['message']) &&
                !empty($json['data']) && !empty($json['data']['status']) && $json['data']['status'] != 200
            ) {
                self::$lastError = $json;
                // Maybe log error here
                return [];
            } else {
                return $json;
            }
        } else {
            return [];
        }
    }

    /**
     * Get modules.
     */
    public static function getModules()
    {
        return self::request(self::METHOD_GET, self::ENDPOINT_MODULES);
    }

    /**
     * Check module license.
     */
    public static function checkLicense($params)
    {
        $params['action'] = self::ACTION_CHECK_LICENSE;

        $endpoint = self::ENDPOINT_MODULES;

        if (!empty($params['module_alias'])) {
            $endpoint .= '/'.$params['module_alias'];
        }

        return self::request(self::METHOD_POST, $endpoint, $params);
    }

    /**
     * Activate module license.
     */
    public static function activateLicense($params)
    {
        $params['action'] = self::ACTION_ACTIVATE_LICENSE;

        $endpoint = self::ENDPOINT_MODULES;

        if (!empty($params['module_alias'])) {
            $endpoint .= '/'.$params['module_alias'];
        }

        return self::request(self::METHOD_POST, $endpoint, $params);
    }

    /**
     * Deactivate module license.
     */
    public static function deactivateLicense($params)
    {
        $params['action'] = self::ACTION_DEACTIVATE_LICENSE;

        $endpoint = self::ENDPOINT_MODULES;

        if (!empty($params['module_alias'])) {
            $endpoint .= '/'.$params['module_alias'];
        }

        return self::request(self::METHOD_POST, $endpoint, $params);
    }

    /**
     * Get license details.
     */
    public static function getVersion($params)
    {
        $params['action'] = self::ACTION_GET_VERSION;

        $endpoint = self::ENDPOINT_MODULES;

        if (!empty($params['module_alias'])) {
            $endpoint .= '/'.$params['module_alias'];
        }

        return self::request(self::METHOD_POST, $endpoint, $params);
    }
}
