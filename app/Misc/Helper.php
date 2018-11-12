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

    public static $locales = [
                'af' => ['name'     => 'Afrikaans',
                 'en'               => 'Afrikaans',
        ],
        'sq' => ['name'     => 'Shqip',
                 'en'       => 'Albanian',
        ],
        'ar' => ['name'     => 'العربية',
                 'en'       => 'Arabic',
        ],
        'ar_IQ' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Iraq)',
        ],
        'ar_LY' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Libya)',
        ],
        'ar_MA' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Morocco)',
        ],
        'ar_OM' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Oman)',
        ],
        'ar_SY' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Syria)',
        ],
        'ar_LB' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Lebanon)',
        ],
        'ar_AE' => ['name'     => 'العربية',
                    'en'       => 'Arabic (U.A.E.)',
        ],
        'ar_QA' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Qatar)',
        ],
        'ar_SA' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Saudi Arabia)',
        ],
        'ar_EG' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Egypt)',
        ],
        'ar_DZ' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Algeria)',
        ],
        'ar_TN' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Tunisia)',
        ],
        'ar_YE' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Yemen)',
        ],
        'ar_JO' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Jordan)',
        ],
        'ar_KW' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Kuwait)',
        ],
        'ar_BH' => ['name'     => 'العربية',
                    'en'       => 'Arabic (Bahrain)',
        ],
        'eu' => ['name'     => 'Euskara',
                 'en'       => 'Basque',
        ],
        'be' => ['name'     => 'Беларуская',
                 'en'       => 'Belarusian',
        ],
        'bn' => ['name'     => 'বাংলা',
                 'en'       => 'Bengali',
        ],
        'bg' => ['name'     => 'Български език',
                 'en'       => 'Bulgarian',
        ],
        'ca' => ['name'     => 'Català',
                 'en'       => 'Catalan',
        ],
        'zh_CN' => ['name'     => '简体中文',
                    'en'       => 'Chinese (Simplified)',
        ],
        'zh_SG' => ['name'     => '简体中文',
                    'en'       => 'Chinese (Singapore)',
        ],
        'zh_TW' => ['name'     => '简体中文',
                    'en'       => 'Chinese (Traditional)',
        ],
        'zh_HK' => ['name'     => '简体中文',
                    'en'       => 'Chinese (Hong Kong SAR)',
        ],
        'hr' => ['name'     => 'Hrvatski',
                 'en'       => 'Croatian',
        ],
        'cs' => ['name'     => 'Čeština',
                 'en'       => 'Czech',
        ],
        'da' => ['name'     => 'Dansk',
                 'en'       => 'Danish',
        ],
        'nl' => ['name'     => 'Nederlands',
                 'en'       => 'Dutch',
        ],
        //      'nl_BE' => ['name'     => 'Nederlands',
        //                  'en'       => 'Dutch (Belgium)',
        //      ],
        //      'en_US' => ['name'     => 'English',
        //                  'en'       => 'English (United States)',
        //      ],
        //      'en_AU' => ['name'     => '',
        //                  'en'       => 'English (Australia)',
        //      ],
        //      'en_NZ' => ['name'     => '',
        //                  'en'       => 'English (New Zealand)',
        //      ],
        //      'en_ZA' => ['name'     => '',
        //                  'en'       => 'English (South Africa)',
        //      ],
        'en' => ['name'     => 'English',
                 'en'       => 'English',
        ],
        //      'en_TT' => ['name'     => '',
        //                  'en'       => 'English (Trinidad)',
        //      ],
        //      'en_GB' => ['name'     => '',
        //                  'en'       => 'English (United Kingdom)',
        //      ],
        //      'en_CA' => ['name'     => '',
        //                  'en'       => 'English (Canada)',
        //      ],
        //      'en_IE' => ['name'     => '',
        //                  'en'       => 'English (Ireland)',
        //      ],
        //      'en_JM' => ['name'     => '',
        //                  'en'       => 'English (Jamaica)',
        //      ],
        //      'en_BZ' => ['name'     => '',
        //                  'en'       => 'English (Belize)',
        //      ],
        'et' => ['name'     => 'Eesti',
                 'en'       => 'Estonian',
        ],
        'fo' => ['name'     => 'Føroyskt',
                 'en'       => 'Faeroese',
        ],
        'fa' => ['name'     => 'فارسی',
                 'en'       => 'Farsi',
        ],
        'fi' => ['name'     => 'Suomi',
                 'en'       => 'Finnish',
        ],
        'fr' => ['name'     => 'Français',
                 'en'       => 'French',
        ],
        //      'fr_CA' => ['name'     => '',
        //                  'en'       => 'French (Canada)',
        //      ],
        //      'fr_LU' => ['name'     => '',
        //                  'en'       => 'French (Luxembourg)',
        //      ],
        //      'fr_BE' => ['name'     => '',
        //                  'en'       => 'French (Belgium)',
        //      ],
        //      'fr_CH' => ['name'     => '',
        //                  'en'       => 'French (Switzerland)',
        //      ],
        'gd' => ['name'     => 'Gàidhlig',
                 'en'       => 'Gaelic (Scotland)',
        ],
        'de' => ['name'     => 'Deutsch',
                 'en'       => 'German',
        ],
        //      'de_CH' => ['name'     => '',
        //                  'en'       => 'German (Switzerland)',
        //      ],
        //      'de_LU' => ['name'     => '',
        //                  'en'       => 'German (Luxembourg)',
        //      ],
        //      'de_AT' => ['name'     => '',
        //                  'en'       => 'German (Austria)',
        //      ],
        //      'de_LI' => ['name'     => '',
        //                  'en'       => 'German (Liechtenstein)',
        //      ],
        'el' => ['name'     => 'Ελληνικά',
                 'en'       => 'Greek',
        ],
        'he' => ['name'     => 'עברית',
                 'en'       => 'Hebrew',
        ],
        'hi' => ['name'     => 'हिन्दी',
                 'en'       => 'Hindi',
        ],
        'hu' => ['name'     => 'Magyar',
                 'en'       => 'Hungarian',
        ],
        'is' => ['name'     => 'Íslenska',
                 'en'       => 'Icelandic',
        ],
        'id' => ['name'     => 'Bahasa Indonesia',
                 'en'       => 'Indonesian',
        ],
        'ga' => ['name'     => 'Gaeilge',
                 'en'       => 'Irish',
        ],
        'it' => ['name'     => 'Italiano',
                 'en'       => 'Italian',
        ],
        //      'it_CH' => ['name'     => 'Italiano',
        //                  'en'       => 'Italian (Switzerland)',
        //      ],
        'ja' => ['name'     => '日本語',
                 'en'       => 'Japanese',
        ],
        'ko' => ['name'     => '한국어 (韓國語)',
                 'en'       => 'Korean (Johab)',
        ],
        'lv' => ['name'     => 'Latviešu valoda',
                 'en'       => 'Latvian',
        ],
        'lt' => ['name'     => 'Lietuvių kalba',
                 'en'       => 'Lithuanian',
        ],
        'mk' => ['name'     => 'Македонски јазик',
                 'en'       => 'Macedonian (FYROM)',
        ],
        'ms' => ['name'     => 'Bahasa Melayu, بهاس ملايو',
                 'en'       => 'Malay',
        ],
        'mt' => ['name'     => 'Malti',
                 'en'       => 'Maltese',
        ],
        'ne' => ['name'     => 'नेपाली',
                 'en'       => 'Nepali',
        ],
        'no' => ['name'     => 'Norsk bokmål',
                 'en'       => 'Norwegian (Bokmal)',
        ],
        'pl' => ['name'     => 'Polski',
                 'en'       => 'Polish',
        ],
        'pt' => ['name'     => 'Português',
                 'en'       => 'Portuguese (Portugal)',
        ],
        'pt_BR' => ['name'     => 'Português do Brasil',
                   'en'        => 'Portuguese (Brazil)',
        ],
        'ro' => ['name'     => 'Română',
                 'en'       => 'Romanian',
        ],
        //      'ro_MO' => ['name'     => 'Română',
        //                  'en'       => 'Romanian (Republic of Moldova)',
        //      ],
        'rm' => ['name'     => 'Rumantsch grischun',
                 'en'       => 'Romansh',
        ],
        'ru' => ['name'     => 'Русский',
                 'en'       => 'Russian',
        ],
        //      'ru_MO' => ['name'     => '',
        //                  'en'       => 'Russian (Republic of Moldova)',
        //      ],
        'sz' => ['name'     => 'Davvisámegiella',
                 'en'       => 'Sami (Lappish)',
        ],
        'sr' => ['name'     => 'Српски језик',
                 'en'       => 'Serbian (Latin)',
        ],
        'sk' => ['name'     => 'Slovenčina',
                 'en'       => 'Slovak',
        ],
        'sl' => ['name'     => 'Slovenščina',
                 'en'       => 'Slovenian',
        ],
        /*'sb' => ['name'     => 'Serbsce',
                 'en'       => 'Sorbian',
        ],*/
        'es' => ['name'     => 'Español',
                 'en'       => 'Spanish',
        ],
        //      'es_GT' => ['name'     => '',
        //                  'en'       => 'Spanish (Guatemala)',
        //      ],
        //      'es_PA' => ['name'     => '',
        //                  'en'       => 'Spanish (Panama)',
        //      ],
        //      'es_VE' => ['name'     => '',
        //                  'en'       => 'Spanish (Venezuela)',
        //      ],
        //      'es_PE' => ['name'     => '',
        //                  'en'       => 'Spanish (Peru)',
        //      ],
        //      'es_EC' => ['name'     => '',
        //                  'en'       => 'Spanish (Ecuador)',
        //      ],
        //      'es_UY' => ['name'     => '',
        //                  'en'       => 'Spanish (Uruguay)',
        //      ],
        //      'es_BO' => ['name'     => '',
        //                  'en'       => 'Spanish (Bolivia)',
        //      ],
        //      'es_HN' => ['name'     => '',
        //                  'en'       => 'Spanish (Honduras)',
        //      ],
        //      'es_PR' => ['name'     => '',
        //                  'en'       => 'Spanish (Puerto Rico)',
        //      ],
        //      'es_MX' => ['name'     => '',
        //                  'en'       => 'Spanish (Mexico)',
        //      ],
        //      'es_CR' => ['name'     => '',
        //                  'en'       => 'Spanish (Costa Rica)',
        //      ],
        //      'es_DO' => ['name'     => '',
        //                  'en'       => 'Spanish (Dominican Republic)',
        //      ],
        //      'es_CO' => ['name'     => '',
        //                  'en'       => 'Spanish (Colombia)',
        //      ],
        //      'es_AR' => ['name'     => '',
        //                  'en'       => 'Spanish (Argentina)',
        //      ],
        //      'es_CL' => ['name'     => '',
        //                  'en'       => 'Spanish (Chile)',
        //      ],
        //      'es_PY' => ['name'     => '',
        //                  'en'       => 'Spanish (Paraguay)',
        //      ],
        //      'es_SV' => ['name'     => '',
        //                  'en'       => 'Spanish (El Salvador)',
        //      ],
        //      'es_NI' => ['name'     => '',
        //                  'en'       => 'Spanish (Nicaragua)',
        //      ],
        'sv' => ['name'     => 'Svenska',
                 'en'       => 'Swedish',
        ],
        // unknown
        //      'sx' => ['name'     => '',
        //                  'en'       => 'Sutu',
        //      ],
        //      'sv_FI' => ['name'     => '',
        //                  'en'       => 'Swedish (Finland)',
        //      ],
        'th' => ['name'     => 'ไทย',
                 'en'       => 'Thai',
        ],
        'ts' => ['name'     => 'Xitsonga',
                 'en'       => 'Tsonga',
        ],
        'tn' => ['name'     => 'Setswana',
                 'en'       => 'Tswana',
        ],
        'tr' => ['name'     => 'Türkçe',
                 'en'       => 'Turkish',
        ],
        'uk' => ['name'     => 'українська',
                 'en'       => 'Ukrainian',
        ],
        'ur' => ['name'     => 'اردو',
                 'en'       => 'Urdu',
        ],
        've' => ['name'     => 'Tshivenḓa',
                 'en'       => 'Venda',
        ],
        'vi' => ['name'     => 'Tiếng Việt',
                 'en'       => 'Vietnamese',
        ],
        'xh' => ['name'     => 'isiXhosa',
                 'en'       => 'Xhosa',
        ],
        'ji' => ['name'     => 'ייִדיש',
                 'en'       => 'Yiddish',
        ],
        'zu' => ['name'     => 'isiZulu',
                 'en'       => 'Zulu',
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
}
