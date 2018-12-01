<?php

/**
 * Class for defining common app functions.
 */

namespace App\Misc;

class Helper
{
    /**
     * Default query cache time in seconds for remember() function.
     */
    const QUERY_CACHE_TIME = 1000;

    /**
     * Text preview max length.
     */
    const PREVIEW_MAXLENGTH = 255;

    /**
     * Menu structure used to display active menu item.
     * Array are mnemonic names, strings - route names.
     * Menu has 2 levels.
     */
    public static $menu = [
        'dashboard' => 'dashboard',
        'mailbox'   => [
            'mailboxes.view',
            'conversations.view',
            'conversations.create',
            'conversations.draft',
            'conversations.search',
        ],
        'manage' => [
            'settings'  => 'settings',
            'mailboxes' => [
                'mailboxes',
                'mailboxes.update',
                'mailboxes.create',
                'mailboxes.connection',
                'mailboxes.connection.incoming',
                'mailboxes.permissions',
                'mailboxes.auto_reply',
            ],
            'users' => [
                'users',
                'users.create',
                'users.profile',
                'users.permissions',
                'users.notifications',
                'users.password',
            ],
            'logs' => [
                'logs',
                'logs.app',
            ],
            'system' => [
                'system',
                'system.tools',
            ],
        ],
        // No menu item selected
        'customers' => [],
    ];

    /**
     * Locales data.
     *
     * @var [type]
     */
    public static $locales = [
        'af' => ['name'                  => 'Afrikaans',
                 'name_en'               => 'Afrikaans',
        ],
        'sq' => ['name'          => 'Shqip',
                 'name_en'       => 'Albanian',
        ],
        'ar' => ['name'          => 'العربية',
                 'name_en'       => 'Arabic',
        ],
        'ar_IQ' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Iraq)',
        ],
        'ar_LY' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Libya)',
        ],
        'ar_MA' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Morocco)',
        ],
        'ar_OM' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Oman)',
        ],
        'ar_SY' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Syria)',
        ],
        'ar_LB' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Lebanon)',
        ],
        'ar_AE' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (U.A.E.)',
        ],
        'ar_QA' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Qatar)',
        ],
        'ar_SA' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Saudi Arabia)',
        ],
        'ar_EG' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Egypt)',
        ],
        'ar_DZ' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Algeria)',
        ],
        'ar_TN' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Tunisia)',
        ],
        'ar_YE' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Yemen)',
        ],
        'ar_JO' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Jordan)',
        ],
        'ar_KW' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Kuwait)',
        ],
        'ar_BH' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Bahrain)',
        ],
        'eu' => ['name'          => 'Euskara',
                 'name_en'       => 'Basque',
        ],
        'be' => ['name'          => 'Беларуская',
                 'name_en'       => 'Belarusian',
        ],
        'bn' => ['name'          => 'বাংলা',
                 'name_en'       => 'Bengali',
        ],
        'bg' => ['name'          => 'Български език',
                 'name_en'       => 'Bulgarian',
        ],
        'ca' => ['name'          => 'Català',
                 'name_en'       => 'Catalan',
        ],
        'zh_CN' => ['name'          => '简体中文',
                    'name_en'       => 'Chinese (Simplified)',
        ],
        'zh_SG' => ['name'          => '简体中文',
                    'name_en'       => 'Chinese (Singapore)',
        ],
        'zh_TW' => ['name'          => '简体中文',
                    'name_en'       => 'Chinese (Traditional)',
        ],
        'zh_HK' => ['name'          => '简体中文',
                    'name_en'       => 'Chinese (Hong Kong SAR)',
        ],
        'hr' => ['name'          => 'Hrvatski',
                 'name_en'       => 'Croatian',
        ],
        'cs' => ['name'          => 'Čeština',
                 'name_en'       => 'Czech',
        ],
        'da' => ['name'          => 'Dansk',
                 'name_en'       => 'Danish',
        ],
        'nl' => ['name'          => 'Nederlands',
                 'name_en'       => 'Dutch',
        ],
        //      'nl_BE' => ['name'     => 'Nederlands',
        //                  'name_en'       => 'Dutch (Belgium)',
        //      ],
        //      'en_US' => ['name'     => 'English',
        //                  'name_en'       => 'English (United States)',
        //      ],
        //      'en_AU' => ['name'     => '',
        //                  'name_en'       => 'English (Australia)',
        //      ],
        //      'en_NZ' => ['name'     => '',
        //                  'name_en'       => 'English (New Zealand)',
        //      ],
        //      'en_ZA' => ['name'     => '',
        //                  'name_en'       => 'English (South Africa)',
        //      ],
        'en' => ['name'          => 'English',
                 'name_en'       => 'English',
        ],
        //      'en_TT' => ['name'     => '',
        //                  'name_en'       => 'English (Trinidad)',
        //      ],
        //      'en_GB' => ['name'     => '',
        //                  'name_en'       => 'English (United Kingdom)',
        //      ],
        //      'en_CA' => ['name'     => '',
        //                  'name_en'       => 'English (Canada)',
        //      ],
        //      'en_IE' => ['name'     => '',
        //                  'name_en'       => 'English (Ireland)',
        //      ],
        //      'en_JM' => ['name'     => '',
        //                  'name_en'       => 'English (Jamaica)',
        //      ],
        //      'en_BZ' => ['name'     => '',
        //                  'name_en'       => 'English (Belize)',
        //      ],
        'et' => ['name'          => 'Eesti',
                 'name_en'       => 'Estonian',
        ],
        'fo' => ['name'          => 'Føroyskt',
                 'name_en'       => 'Faeroese',
        ],
        'fa' => ['name'          => 'فارسی',
                 'name_en'       => 'Farsi',
        ],
        'fi' => ['name'          => 'Suomi',
                 'name_en'       => 'Finnish',
        ],
        'fr' => ['name'          => 'Français',
                 'name_en'       => 'French',
        ],
        //      'fr_CA' => ['name'     => '',
        //                  'name_en'       => 'French (Canada)',
        //      ],
        //      'fr_LU' => ['name'     => '',
        //                  'name_en'       => 'French (Luxembourg)',
        //      ],
        //      'fr_BE' => ['name'     => '',
        //                  'name_en'       => 'French (Belgium)',
        //      ],
        //      'fr_CH' => ['name'     => '',
        //                  'name_en'       => 'French (Switzerland)',
        //      ],
        'gd' => ['name'          => 'Gàidhlig',
                 'name_en'       => 'Gaelic (Scotland)',
        ],
        'de' => ['name'          => 'Deutsch',
                 'name_en'       => 'German',
        ],
        //      'de_CH' => ['name'     => '',
        //                  'name_en'       => 'German (Switzerland)',
        //      ],
        //      'de_LU' => ['name'     => '',
        //                  'name_en'       => 'German (Luxembourg)',
        //      ],
        //      'de_AT' => ['name'     => '',
        //                  'name_en'       => 'German (Austria)',
        //      ],
        //      'de_LI' => ['name'     => '',
        //                  'name_en'       => 'German (Liechtenstein)',
        //      ],
        'el' => ['name'          => 'Ελληνικά',
                 'name_en'       => 'Greek',
        ],
        'he' => ['name'          => 'עברית',
                 'name_en'       => 'Hebrew',
        ],
        'hi' => ['name'          => 'हिन्दी',
                 'name_en'       => 'Hindi',
        ],
        'hu' => ['name'          => 'Magyar',
                 'name_en'       => 'Hungarian',
        ],
        'is' => ['name'          => 'Íslenska',
                 'name_en'       => 'Icelandic',
        ],
        'id' => ['name'          => 'Bahasa Indonesia',
                 'name_en'       => 'Indonesian',
        ],
        'ga' => ['name'          => 'Gaeilge',
                 'name_en'       => 'Irish',
        ],
        'it' => ['name'          => 'Italiano',
                 'name_en'       => 'Italian',
        ],
        //      'it_CH' => ['name'     => 'Italiano',
        //                  'name_en'       => 'Italian (Switzerland)',
        //      ],
        'ja' => ['name'          => '日本語',
                 'name_en'       => 'Japanese',
        ],
        'ko' => ['name'          => '한국어 (韓國語)',
                 'name_en'       => 'Korean (Johab)',
        ],
        'lv' => ['name'          => 'Latviešu valoda',
                 'name_en'       => 'Latvian',
        ],
        'lt' => ['name'          => 'Lietuvių kalba',
                 'name_en'       => 'Lithuanian',
        ],
        'mk' => ['name'          => 'Македонски јазик',
                 'name_en'       => 'Macedonian (FYROM)',
        ],
        'ms' => ['name'          => 'Bahasa Melayu, بهاس ملايو',
                 'name_en'       => 'Malay',
        ],
        'mt' => ['name'          => 'Malti',
                 'name_en'       => 'Maltese',
        ],
        'ne' => ['name'          => 'नेपाली',
                 'name_en'       => 'Nepali',
        ],
        'no' => ['name'          => 'Norsk bokmål',
                 'name_en'       => 'Norwegian (Bokmal)',
        ],
        'pl' => ['name'          => 'Polski',
                 'name_en'       => 'Polish',
        ],
        'pt' => ['name'          => 'Português',
                 'name_en'       => 'Portuguese (Portugal)',
        ],
        'pt_BR' => ['name'          => 'Português do Brasil',
                   'name_en'        => 'Portuguese (Brazil)',
        ],
        'ro' => ['name'          => 'Română',
                 'name_en'       => 'Romanian',
        ],
        //      'ro_MO' => ['name'     => 'Română',
        //                  'name_en'       => 'Romanian (Republic of Moldova)',
        //      ],
        'rm' => ['name'          => 'Rumantsch grischun',
                 'name_en'       => 'Romansh',
        ],
        'ru' => ['name'          => 'Русский',
                 'name_en'       => 'Russian',
        ],
        //      'ru_MO' => ['name'     => '',
        //                  'name_en'       => 'Russian (Republic of Moldova)',
        //      ],
        'sz' => ['name'          => 'Davvisámegiella',
                 'name_en'       => 'Sami (Lappish)',
        ],
        'sr' => ['name'          => 'Српски језик',
                 'name_en'       => 'Serbian (Latin)',
        ],
        'sk' => ['name'          => 'Slovenčina',
                 'name_en'       => 'Slovak',
        ],
        'sl' => ['name'          => 'Slovenščina',
                 'name_en'       => 'Slovenian',
        ],
        /*'sb' => ['name'     => 'Serbsce',
                 'name_en'       => 'Sorbian',
        ],*/
        'es' => ['name'          => 'Español',
                 'name_en'       => 'Spanish',
        ],
        //      'es_GT' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Guatemala)',
        //      ],
        //      'es_PA' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Panama)',
        //      ],
        //      'es_VE' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Venezuela)',
        //      ],
        //      'es_PE' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Peru)',
        //      ],
        //      'es_EC' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Ecuador)',
        //      ],
        //      'es_UY' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Uruguay)',
        //      ],
        //      'es_BO' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Bolivia)',
        //      ],
        //      'es_HN' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Honduras)',
        //      ],
        //      'es_PR' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Puerto Rico)',
        //      ],
        //      'es_MX' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Mexico)',
        //      ],
        //      'es_CR' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Costa Rica)',
        //      ],
        //      'es_DO' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Dominican Republic)',
        //      ],
        //      'es_CO' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Colombia)',
        //      ],
        //      'es_AR' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Argentina)',
        //      ],
        //      'es_CL' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Chile)',
        //      ],
        //      'es_PY' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Paraguay)',
        //      ],
        //      'es_SV' => ['name'     => '',
        //                  'name_en'       => 'Spanish (El Salvador)',
        //      ],
        //      'es_NI' => ['name'     => '',
        //                  'name_en'       => 'Spanish (Nicaragua)',
        //      ],
        'sv' => ['name'          => 'Svenska',
                 'name_en'       => 'Swedish',
        ],
        // unknown
        //      'sx' => ['name'     => '',
        //                  'name_en'       => 'Sutu',
        //      ],
        //      'sv_FI' => ['name'     => '',
        //                  'name_en'       => 'Swedish (Finland)',
        //      ],
        'th' => ['name'          => 'ไทย',
                 'name_en'       => 'Thai',
        ],
        'ts' => ['name'          => 'Xitsonga',
                 'name_en'       => 'Tsonga',
        ],
        'tn' => ['name'          => 'Setswana',
                 'name_en'       => 'Tswana',
        ],
        'tr' => ['name'          => 'Türkçe',
                 'name_en'       => 'Turkish',
        ],
        'uk' => ['name'          => 'українська',
                 'name_en'       => 'Ukrainian',
        ],
        'ur' => ['name'          => 'اردو',
                 'name_en'       => 'Urdu',
        ],
        've' => ['name'          => 'Tshivenḓa',
                 'name_en'       => 'Venda',
        ],
        'vi' => ['name'          => 'Tiếng Việt',
                 'name_en'       => 'Vietnamese',
        ],
        'xh' => ['name'          => 'isiXhosa',
                 'name_en'       => 'Xhosa',
        ],
        'ji' => ['name'          => 'ייִדיש',
                 'name_en'       => 'Yiddish',
        ],
        'zu' => ['name'          => 'isiZulu',
                 'name_en'       => 'Zulu',
        ],
    ];

    /**
     * Cache time of the DB query.
     */
    public static function cacheTime($enabled = true)
    {
        if ($enabled) {
            return self::QUERY_CACHE_TIME;
        } else {
            return 0;
        }
    }

    /**
     * Get preview of the text in a plain form.
     */
    public static function textPreview($text, $length = self::PREVIEW_MAXLENGTH)
    {
        // Remove all kinds of spaces after tags
        // https://stackoverflow.com/questions/3230623/filter-all-types-of-whitespace-in-php
        $text = preg_replace("/^(.*)>[\r\n]*\s+/mu", '$1>', $text);

        $text = strip_tags($text);
        $text = preg_replace('/\s+/mu', ' ', $text);

        // Trim
        $text = trim($text);
        $text = preg_replace('/^\s+/mu', '', $text);

        // Causes "General error: 1366 Incorrect string value"
        // Remove "undetectable" whitespaces
        // $whitespaces = ['%81', '%7F', '%C5%8D', '%8D', '%8F', '%C2%90', '%C2', '%90', '%9D', '%C2%A0', '%A0', '%C2%AD', '%AD', '%08', '%09', '%0A', '%0D'];
        // $text = urlencode($text);
        // foreach ($whitespaces as $char) {
        //     $text = str_replace($char, ' ', $text);
        // }
        // $text = urldecode($text);

        $text = trim(preg_replace('/[ ]+/', ' ', $text));

        $text = mb_substr($text, 0, $length);

        return $text;
    }

    /**
     * Check if passed route name equals to the current one.
     */
    public static function isCurrentRoute($route_name)
    {
        if (\Request::route()->getName() == $route_name) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if menu item is selected.
     * Each menu item has a mnemonic name.
     */
    public static function isMenuSelected($menu_item_name)
    {
        $current_route = \Request::route()->getName();

        $menu = \Eventy::filter('menu.selected', self::$menu);

        foreach ($menu as $primary_name => $primary_items) {
            if (!is_array($primary_items)) {
                if ($current_route == $primary_items) {
                    return $primary_name == $menu_item_name;
                }
                if ($primary_name == $menu_item_name) {
                    return false;
                }
                continue;
            }
            foreach ($primary_items as $secondary_name => $secondary_routes) {
                if (is_array($secondary_routes)) {
                    if (in_array($current_route, $secondary_routes)) {
                        return $secondary_name == $menu_item_name || $primary_name == $menu_item_name;
                    }
                } elseif (is_string($secondary_name)) {
                    if ($current_route == $secondary_routes) {
                        return $secondary_name == $menu_item_name || $primary_name == $menu_item_name;
                    }
                } else {
                    if ($current_route == $secondary_routes) {
                        return $primary_name == $menu_item_name;
                    }
                }
            }
        }

        return false;
    }

    public static function menuSelectedHtml($menu_item_name)
    {
        if (self::isMenuSelected($menu_item_name)) {
            return 'active';
        } else {
            return '';
        }
    }

    /**
     * Resize image without using Intervention package.
     */
    public static function resizeImage($file, $mime_type, $thumb_width, $thumb_height)
    {
        list($width, $height) = getimagesize($file);
        if (!$width) {
            return false;
        }

        if (preg_match('/png/i', $mime_type)) {
            $src = imagecreatefrompng($file);
        } elseif (preg_match('/gif/i', $mime_type)) {
            $src = imagecreatefromgif($file);
        } elseif (preg_match('/bmp/i', $mime_type)) {
            $src = imagecreatefrombmp($file);
        } else {
            $src = imagecreatefromjpeg($file);
        }

        $original_aspect = $width / $height;
        $thumb_aspect = $thumb_width / $thumb_height;
        if ($original_aspect == $thumb_aspect) {
            $new_height = $thumb_height;
            $new_width = $thumb_width;
        } elseif ($original_aspect > $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $thumb_height;
            $new_width = $width / ($height / $thumb_height);
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $height / ($width / $thumb_width);
        }

        $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
        // Resize and crop
        imagecopyresampled($thumb,
                           $src,
                           0 - ($new_width - $thumb_width) / 2, // Center the image horizontally
                           0 - ($new_height - $thumb_height) / 2, // Center the image vertically
                           0, 0,
                           $new_width, $new_height,
                           $width, $height);
        imagedestroy($src);

        return $thumb;
    }

    public static function jsonToArray($json, $exclude_array = [])
    {
        if ($json) {
            $array = json_decode($json, true);
            if (json_last_error()) {
                $array = [$json];
            }
            if ($array && $exclude_array) {
                $array = array_diff($array, $exclude_array);
            }

            return $array;
        } else {
            return [];
        }
    }

    public static function getDomain()
    {
        return parse_url(\Config::get('app.url'), PHP_URL_HOST);
    }

    /**
     * Create zip archive.
     * Source example: public/files/*
     * File name example: test.zip.
     */
    public static function createZipArchive($source, $file_name, $folder = '')
    {
        if (!$source || !$file_name) {
            return false;
        }
        $files = glob($source);

        //$dest_folder = storage_path().DIRECTORY_SEPARATOR.'app/zipper';
        // if (!file_exists($dest_folder)) {
        //     $mkdir_result = File::makeDirectory($dest_folder, 0755);
        //     if (!$mkdir_result) {
        //         return false;
        //     }
        // }

        $storage_path = 'app/zipper'.DIRECTORY_SEPARATOR.$file_name;
        $dest_path = storage_path().DIRECTORY_SEPARATOR.$storage_path;

        // If file exists it has to be deleted, otherwise Zipper will add file to the existing archive
        // By some reason \Storage not finding $storage_path file
        // todo: use \Storage
        if (\File::exists($dest_path)) {
            \File::delete($dest_path);
        }
        \Chumper\Zipper\Facades\Zipper::make($dest_path)->folder($folder)->add($files)->close();

        return $dest_path;
    }

    public static function formatException($e)
    {
        return 'Error: '.$e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')';
    }

    public static function denyAccess()
    {
        abort(403, 'This action is unauthorized.');
    }

    /**
     * Check if application version.
     *
     * @param [type] $ver [description]
     *
     * @return [type] [description]
     */
    public static function checkAppVersion($version2, $operator = '>=')
    {
        return version_compare(\Config::get('app.version'), $version2, $operator);
    }

    /**
     * Download remote file and save as file.
     */
    public static function downloadRemoteFile($url, $destinationFilePath)
    {
        $client = new \GuzzleHttp\Client();
        $client->request('GET', $url, [
            'sink' => $destinationFilePath,
        ]);
    }

    /**
     * Extract ZIP archive.
     * to: must be apsolute path, otherwise extracted into /public/$to.
     */
    public static function unzip($archive, $to)
    {
        \Chumper\Zipper\Facades\Zipper::make($archive)->extractTo($to);
    }

    public static function logException($e)
    {
        \Log::error(self::formatException($e));
    }

    /**
     * Safely decrypt.
     *
     * @param [type] $e [description]
     *
     * @return [type] [description]
     */
    public static function decrypt($value)
    {
        try {
            $value = decrypt($value);
        } catch (\Exception $e) {
            // Do nothing.
        }

        return $value;
    }

    /**
     * Log custom data to activity log.
     *
     * @param [type] $log_name [description]
     * @param [type] $data     [description]
     * @param [type] $code     [description]
     *
     * @return [type] [description]
     */
    public static function log($log_name, $description, $properties = [])
    {
        activity()
            ->withProperties($properties)
            ->useLog($log_name)
            ->log($description);
    }

    /**
     * Check if folder is writable.
     *
     * @param [type] $path [description]
     *
     * @return bool [description]
     */
    public static function isFolderWritable($path)
    {
        if (!file_exists($path)) {
            return false;
        }
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        try {
            $file = $path.DIRECTORY_SEPARATOR.'.writable_test';
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
     * Get locale's data.
     *
     * @param [type] $locale [description]
     * @param string $param  [description]
     *
     * @return [type] [description]
     */
    public static function getLocaleData($locale, $param = '')
    {
        if (is_string($locale) && isset(self::$locales[$locale])) {
            $data = self::$locales[$locale];
        } else {
            return;
        }

        if ($param) {
            if (isset(self::$locales[$locale])) {
                return self::$locales[$locale][$param];
            } else {
                return;
            }
        } else {
            return $data;
        }
    }

    /**
     * Clear application cache.
     *
     * @return [type] [description]
     */
    public static function clearCache()
    {
        \Artisan::call('freescout:clear-cache');
    }

    /**
     * Set variable in .evn file.
     */
    public static function setEnvFileVar($key, $value)
    {
        $env_path = app()->environmentFilePath();
        $contents = file_get_contents($env_path);

        // Maybe validate value and add quotes.
        // str_contains($key, '=')
        // !preg_match('/^[a-zA-Z_]+$/', $key)

        $old_value = '';
        // Match the given key at the beginning of a line
        preg_match("/^{$key}=[^\r\n]*/m", $contents, $matches);
        if (count($matches)) {
            $old_value = substr($matches[0], strlen($key) + 1);
        }

        if ($old_value) {
            // Replace.
            $contents = str_replace("{$key}={$old_value}", "{$key}={$value}", $contents);
            \File::put($env_path, $contents);
        } else {
            // Add.
            $contents = $contents."\n{$key}={$value}\n";
            \File::put($env_path, $contents);
        }
    }

    /**
     * User may add an extra translation to the app on Translate page.
     *
     * @return [type] [description]
     */
    public static function getCustomLocales()
    {
        return \Barryvdh\TranslationManager\Models\Translation::distinct()->pluck('locale')->toArray();
    }

    /**
     *  app()->setLocale() in Localize middleware also changes config('app.locale'),
     *  so we are keeping real app locale in real_locale parameter.
     */
    public static function getRealAppLocale()
    {
        return config('app.real_locale');
    }
}
