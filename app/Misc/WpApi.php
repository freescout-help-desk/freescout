<?php

namespace App\Misc;

class WpApi
{
    const ENDPOINT_MODULES = 'freescout/v1/modules';

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    const ACTION_CHECK_LICENSE = 'check_license';
    const ACTION_CHECK_LICENSES = 'check_licenses';
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

    public static function httpRequest($method, $url, $params)
    {
        $client = new \GuzzleHttp\Client();

        if ($method == self::METHOD_POST) {
            if (strstr($url, '?')) {
                $url .= '&';
            } else {
                $url .= '?';
            }
            $url .= 'v='.config('app.version');
            return $client->request('POST', $url, \Helper::setGuzzleDefaultOptions([
                'connect_timeout' => 10,
                'form_params' => $params,
            ]));
        } else {
            $params['v'] = config('app.version');
            return $client->request('GET', $url, \Helper::setGuzzleDefaultOptions([
                'connect_timeout' => 10,
                'query' => $params,
            ]));
        }
    }

    /**
     * API request.
     */
    public static function request($method, $endpoint, $params = [], $alternative_api = false)
    {
        self::$lastError = null;

        try {
            $response = self::httpRequest($method, self::url($endpoint, $alternative_api), $params);
        } catch (\Exception $e) {
            if (!$alternative_api) {
                return self::request($method, $endpoint, $params, true);
            }
            \Helper::logException($e, 'WpApi');
            self::$lastError = [
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            return [];
        }

        // https://guzzle3.readthedocs.io/http-client/response.html
        if ($response->getStatusCode() < 500) {
            $json = \Helper::jsonToArray($response->getBody());

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
     * Check module license.
     */
    public static function checkLicenses($params)
    {
        $params['action'] = self::ACTION_CHECK_LICENSES;

        $endpoint = self::ENDPOINT_MODULES;

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
