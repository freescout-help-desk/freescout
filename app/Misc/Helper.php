<?php

/**
 * Class for defining common app functions.
 */

namespace App\Misc;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

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
     * Deafult background job queue.
     */
    const QUEUE_DEFAULT = 'default';

    /**
     * Limit for IN queries.
     */
    const IN_LIMIT = 1000;

    /**
     * Permissions for directories.
     */
    const DIR_PERMISSIONS = 0755;

    /**
     * Stores list of global entities (for caching).
     */
    public static $global_entities = [];

    /**
     * Files with such extensions are being renamed on upload.
     */
    public static $restricted_extensions = [
        'php.*',
        'sh',
        'pl',
    ];

    /**
     * Menu structure used to display active menu item.
     * Array are mnemonic names, strings - route names.
     * Menu has 2 levels.
     */
    public static $menu = [
        'dashboard' => 'dashboard',
        'mailbox'   => [
            'mailboxes.view',
            'mailboxes.view.folder',
            'conversations.view',
            'conversations.create',
            'conversations.draft',
            //'conversations.search',
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
        'ar-IQ' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Iraq)',
        ],
        'ar-LY' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Libya)',
        ],
        'ar-MA' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Morocco)',
        ],
        'ar-OM' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Oman)',
        ],
        'ar-SY' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Syria)',
        ],
        'ar-LB' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Lebanon)',
        ],
        'ar-AE' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (U.A.E.)',
        ],
        'ar-QA' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Qatar)',
        ],
        'ar-SA' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Saudi Arabia)',
        ],
        'ar-EG' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Egypt)',
        ],
        'ar-DZ' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Algeria)',
        ],
        'ar-TN' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Tunisia)',
        ],
        'ar-YE' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Yemen)',
        ],
        'ar-JO' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Jordan)',
        ],
        'ar-KW' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Kuwait)',
        ],
        'ar-BH' => ['name'          => 'العربية',
                    'name_en'       => 'Arabic (Bahrain)',
        ],
        'az' => ['name'          => 'Azerbaijani',
                    'name_en'       => 'Azerbaijani',
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
        'zh-CN' => ['name'          => '简体中文',
                    'name_en'       => 'Chinese (Simplified)',
        ],
        'zh-SG' => ['name'          => '简体中文',
                    'name_en'       => 'Chinese (Singapore)',
        ],
        'zh-TW' => ['name'          => '简体中文',
                    'name_en'       => 'Chinese (Traditional)',
        ],
        'zh-HK' => ['name'          => '简体中文',
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
        'pt-PT' => ['name'          => 'Português',
                 'name_en'       => 'Portuguese (Portugal)',
        ],
        'pt-BR' => ['name'          => 'Português do Brasil',
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

    public static function setGlobalEntity($name, $entity)
    {
        self::$global_entities[$name] = $entity;
    }

    public static function getGlobalEntity($name)
    {
        return self::$global_entities[$name] ?? null;
    }

    /**
     * Remove from text all tags, double spaces, etc.
     */
    public static function stripTags($text)
    {
        // Remove all kinds of spaces after tags.
        // https://stackoverflow.com/questions/3230623/filter-all-types-of-whitespace-in-php
        $text = preg_replace("/^(.*)>[\r\n]*\s+/mu", '$1>', $text ?? '');

        // Remove <script> and <style> blocks.
        $text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $text);
        $text = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $text);

        // Remove tags.
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

        return $text;
    }

    /**
     * Get preview of the text in a plain form.
     */
    public static function textPreview($text, $length = self::PREVIEW_MAXLENGTH)
    {
        $text = strtr($text ?? '', [
            '</div>' => ' </div>',
            '</p>' => ' </p>'
        ]);

        $text = self::stripTags($text);

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
                        return $primary_name == $menu_item_name || $menu_item_name == $secondary_routes;
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

            $kek = imagecolorallocate($src, 255, 255, 255);
            imagefill($src, 0, 0, $kek);
        } elseif (preg_match('/gif/i', $mime_type)) {
            $src = imagecreatefromgif($file);

            $kek = imagecolorallocate($src, 255, 255, 255);
            imagefill($src, 0, 0, $kek);
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
                           ceil(0 - ($new_width - $thumb_width) / 2), // Center the image horizontally
                           ceil(0 - ($new_height - $thumb_height) / 2), // Center the image vertically
                           0, 0,
                           ceil($new_width),
                           ceil($new_height),
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
     * storage_path without app/
     */
    public static function createZipArchive($source, $file_name, $folder = '', $storage_file_path = '')
    {
        if (!$source || !$file_name) {
            return false;
        }
        $files = glob($source);

        if (!$storage_file_path) {
            $storage_file_path = 'zipper'.DIRECTORY_SEPARATOR.$file_name;
        } else {
            // if (!self::getPrivateStorage()->exists($storage_path)) {
            //     self::getPrivateStorage()->makeDirectory($storage_path);
            // }
        }
        $dest_path = storage_path().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.$storage_file_path;

        // If file exists it has to be deleted, otherwise Zipper will add file to the existing archive
        if (self::getPrivateStorage()->exists($storage_file_path)) {
            self::getPrivateStorage()->delete($storage_file_path);
        }

        \Chumper\Zipper\Facades\Zipper::make($dest_path)->folder($folder)->add($files)->close();

        return $dest_path;
    }

    public static function getPrivateStorage()
    {
        return \Storage::disk('private');
    }

    public static function getPublicStorage()
    {
        return \Storage::disk('public');
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

        try {
            $client->request('GET', $url, [
                'sink' => $destinationFilePath,
                'timeout' => 300, // seconds
                'connect_timeout' => 7,
                'proxy' => config('app.proxy'),
            ]);
        } catch (\Exception $e) {
            self::logException($e);
        }
    }

    /**
     * Extract ZIP archive.
     * to: must be apsolute path, otherwise extracted into /public/$to.
     */
    public static function unzip($archive, $to)
    {
        \Chumper\Zipper\Facades\Zipper::make($archive)->extractTo($to);
    }

    public static function logException($e, $prefix = '')
    {
        if ($prefix) {
            $prefix .= ' ';
        }
        \Log::error($prefix.self::formatException($e));
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
     * Log exception to activity log.
     */
    public static function logExceptionToActivityLog($e, $log_name, $description, $properties = [])
    {
        $properties['error'] = self::formatException($e);
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

    public static function setLocale($locale)
    {
        if (in_array($locale, config('app.locales'))) {
            app()->setLocale($locale);
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
    public static function clearCache($options = [])
    {
        \Artisan::call('freescout:clear-cache', $options);
    }

    /**
     * Set variable in .evn file.
     */
    public static function setEnvFileVar($key, $value)
    {
        $env_path = app()->environmentFilePath();
        $contents = file_get_contents($env_path);

        if (strstr($value, '"')) {
            // Escape quotes.
            $value = '"'.str_replace('"', '\"', $value).'"';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $value) && $value !== '') {
            // Add quotes.
            $value = '"'.$value.'"';
        }

        $old_value = '';
        // Match the given key at the beginning of a line
        preg_match("/^{$key}=[^\r\n]*/m", $contents, $matches);
        if (count($matches)) {
            $old_value = substr($matches[0], strlen($key) + 1);
        }

        if ($old_value) {
            // Replace.
            $contents = str_replace("{$key}={$old_value}", "{$key}={$value}", $contents);
        } else {
            // Add or empty value
            preg_match("/^{$key}=[\r\n]/m", $contents, $matches);
            if (count($matches)) {
                // Replace empty value
                $contents = str_replace("{$key}=", "{$key}={$value}", $contents);
            } else {
                // Add.
                $contents = $contents."\n{$key}={$value}\n";
            }
        }
        \File::put($env_path, $contents);
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
     * Get built in and custom locales.
     *
     * @return [type] [description]
     */
    public static function getAllLocales()
    {
        $app_locales = config('app.locales');

        // User may add an extra translation to the app on Translate page,
        // we should allow user to see his custom translations.
        $custom_locales = [];
        try {
            $custom_locales = \Helper::getCustomLocales();
        } catch (\Exception $e) {
            // During installation it throws an error as there is no tables yet.
        }

        if (count($custom_locales)) {
            $app_locales = array_unique(array_merge($app_locales, $custom_locales));
        }

        return $app_locales;
    }

    /**
     *  app()->setLocale() in Localize middleware also changes config('app.locale'),
     *  so we are keeping real app locale in real_locale parameter.
     */
    public static function getRealAppLocale()
    {
        return config('app.real_locale');
    }

    /**
     * Create a backgound job executing specified action.
     *
     * @return [type] [description]
     */
    public static function backgroundAction($action, $params, $delay = 0)
    {
        $delay = \Eventy::filter('backgound_action.dispatch_delay', $delay, $action, $params);

        $job = \App\Jobs\TriggerAction::dispatch($action, $params);
        if ($delay) {
            $job->delay($delay);
        }
        $job->onQueue('default');
    }

    /**
     * Convert HTML into the text with \n.
     *
     * @param [type] $text [description]
     */
    public static function htmlToText($text, $embed_images = false, $options = ['width' => 0])
    {
        // Process blockquotes.
        $text = str_ireplace('<blockquote>', '<div>', $text);
        $text = str_ireplace('</blockquote>', '</div>', $text);

        if ($embed_images) {
            // Replace embedded images with their urls.
            $text = preg_replace( '/<img\b[^>]*src=\"([^>"]+)\"[^>]*>/i', "<div>$1</div>", $text);
        }
        return (new \Html2Text\Html2Text($text, $options))->getText();
    }

    /**
     * Trim text removing non-breaking spaces also.
     *
     * @param  [type] $text [description]
     * @return [type]       [description]
     */
    public static function trim($text)
    {
        $text = preg_replace("/^\s+/u", '', $text);
        $text = preg_replace("/\s+$/u", '', $text);

        return $text;
    }

    /**
     * Unicode escape sequences like “\u00ed” to proper UTF-8 encoded characters.
     *
     * @param  [type] $text [description]
     * @return [type]       [description]
     */
    public static function entities2utf8($text)
    {
        try {
            return json_decode('"'.str_replace('"', '\\"', $text).'"');
        } catch(\Exception $e) {
            return $text;
        }
    }

    /**
     * Get app subdirectory in /subdirectory/1/2/ format.
     */
    public static function getSubdirectory($keep_trailing_slash = false, $keep_front_slash = false)
    {
        $subdirectory = '';

        $app_url = config('app.url');

        // Check host to ignore default values.
        $app_host = parse_url($app_url, PHP_URL_HOST);

        if ($app_url && !in_array($app_host, ['localhost', 'example.com'])) {
            $subdirectory = parse_url($app_url, PHP_URL_PATH);
        } else {
            // Before app is installed
            $subdirectory = $_SERVER['PHP_SELF'];

            $filename = basename($_SERVER['SCRIPT_FILENAME']);

            if (basename($_SERVER['SCRIPT_NAME']) === $filename) {
                $subdirectory = $_SERVER['SCRIPT_NAME'];
            } elseif (basename($_SERVER['PHP_SELF']) === $filename) {
                $subdirectory = $_SERVER['PHP_SELF'];
            } elseif (array_key_exists('ORIG_SCRIPT_NAME', $_SERVER) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
                $subdirectory = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
            } else {
                // Backtrack up the script_filename to find the portion matching
                // php_self
                $path = $_SERVER['PHP_SELF'];
                $file = $_SERVER['SCRIPT_FILENAME'];
                $segs = explode('/', trim($file, '/'));
                $segs = array_reverse($segs);
                $index = 0;
                $last = \count($segs);
                $subdirectory = '';
                do {
                    $seg = $segs[$index];
                    $subdirectory = '/'.$seg.$subdirectory;
                    ++$index;
                } while ($last > $index && (false !== $pos = strpos($path, $subdirectory)) && 0 != $pos);
            }
        }

        if ($subdirectory === null) {
            $subdirectory = '';
        }

        $subdirectory = str_replace('public/index.php', '', $subdirectory);
        $subdirectory = str_replace('index.php', '', $subdirectory);

        $subdirectory = trim($subdirectory, '/');
        if ($keep_trailing_slash) {
            $subdirectory .= '/';
        }

        if ($keep_front_slash && $subdirectory != '/') {
            $subdirectory = '/'.$subdirectory;
        }

        return $subdirectory;
    }

    /**
     * Check current route.
     */
    public static function isRoute($route_name)
    {
        $route = \Route::current();
        if (!$route) {
            return false;
        }
        $current = $route->getName();

        if (is_array($route_name)) {
            return in_array($current, $route_name);
        } else {
            return ($current == $route_name);
        }
    }

    /**
     * Check if passed app URL has default Laravel value.
     */
    public static function isDefaultAppUrl($app_url)
    {
        $app_host = parse_url($app_url, PHP_URL_HOST);

        if ($app_url && !in_array($app_host, ['localhost', 'example.com'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Stop all queue:work processes.
     */
    public static function queueWorkerRestart()
    {
        \Cache::forever('illuminate:queue:restart', Carbon::now()->getTimestamp());
        // In some systems queue:work runs on a separate file system,
        // so those queue:work processes may not get illuminate:queue:restart.
        $job_exists = \App\Job::where('queue', 'default')
            ->where('payload', 'like', '{"displayName":"App\\\\\\\\Jobs\\\\\\\\RestartQueueWorker"%')
            ->exists();
        if (!$job_exists) {
            \App\Jobs\RestartQueueWorker::dispatch()->onQueue('default');
        }
    }

    /**
     * UTF-8 split text into parts with max. length.
     */
    public static function strSplitKeepWords($str, $max_length = 75)
    {
        $array_words = explode(' ', $str);

        $currentLength = 0;

        $index = 0;

        $array_output = [''];

        foreach ($array_words as $word) {
            // +1 because the word will receive back the space in the end that it loses in explode()
            $wordLength = strlen($word) + 1;

            if (($currentLength + $wordLength) <= $max_length) {
                $array_output[$index] .= $word . ' ';

                $currentLength += $wordLength;
            } else {
                $index += 1;

                $currentLength = $wordLength;

                $array_output[$index] = $word;
            }
        }

        return $array_output;
    }

    /**
     * Replace new line with doble <br />.
     */
    public static function nl2brDouble($text)
    {
        return str_replace('<br />', '<br /><br />', nl2br($text));
    }

    /**
     * Decode \u00ed.
     */
    public static function decodeUnicode($str)
    {
        $str = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $str);

        return $str;
    }

    /**
     * Convert text into json without converting chars into \u0411.
     */
    public static function jsonEncodeUtf8($text)
    {
        return json_encode($text, JSON_UNESCAPED_UNICODE);
    }

    /**
     * It looks like this is not used anywhere.
     * Json encode to avoid "Unable to JSON encode payload. Error code: 5"
     */
    // public static function jsonEncodeSafe($value, $options = 0, $depth = 512, $utfErrorFlag = false)
    // {
    //     $encoded = json_encode($value, $options, $depth);
    //     switch (json_last_error()) {
    //         case JSON_ERROR_NONE:
    //             return $encoded;
    //         // case JSON_ERROR_DEPTH:
    //         //     return 'Maximum stack depth exceeded'; // or trigger_error() or throw new Exception()
    //         // case JSON_ERROR_STATE_MISMATCH:
    //         //     return 'Underflow or the modes mismatch'; // or trigger_error() or throw new Exception()
    //         // case JSON_ERROR_CTRL_CHAR:
    //         //     return 'Unexpected control character found';
    //         // case JSON_ERROR_SYNTAX:
    //         //     return 'Syntax error, malformed JSON'; // or trigger_error() or throw new Exception()
    //         case JSON_ERROR_UTF8:
    //             $clean = self::utf8ize($value);
    //             if ($utfErrorFlag) {
    //                 //return 'UTF8 encoding error'; // or trigger_error() or throw new Exception()
    //             }
    //             return self::jsonEncodeSafe($clean, $options, $depth, true);
    //         // default:
    //         //     return 'Unknown error'; // or trigger_error() or throw new Exception()

    //     }
    // }

    // public static function utf8ize($mixed)
    // {
    //     if (is_array($mixed)) {
    //         foreach ($mixed as $key => $value) {
    //             $mixed[$key] = self::utf8ize($value);
    //         }
    //     } else if (is_string ($mixed)) {
    //         return utf8_encode($mixed);
    //     }
    //     return $mixed;
    // }

    /**
     * Check if host is available on the port specified.
     */
    public static function checkPort($host, $port, $timeout = 10)
    {
        $connection = @fsockopen($host, $port);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        } else {
            return false;
        }
    }

    public static function purifyHtml($html)
    {
        if (!$html) {
            return $html;
        }

        $html = \Purifier::clean($html);

        // It's not clear why it was needed to remove spaces after tags.
        // 
        // Remove all kinds of spaces after tags
        // https://stackoverflow.com/questions/3230623/filter-all-types-of-whitespace-in-php
        //$html = preg_replace("/^(.*)>[\r\n]*\s+/mu", '$1>', $html);

        return $html;
    }

    /**
     * Replace password with asterisks.
     */
    public static function safePassword($password)
    {
        return str_repeat("*", mb_strlen($password ?? ''));
    }

    /**
     * Turn all URLs in clickable links.
     * Released under public domain
     * https://gist.github.com/jasny/2000705
     *
     * @param string $value
     * @param array  $protocols  http/https, ftp, mail
     * @param array  $attributes
     * @return string
     */
    public static function linkify($value, $protocols = ['http', 'mail'], array $attributes = [])
    {
        // Link attributes
        $attr = '';
        foreach ($attributes as $key => $val) {
            $attr .= ' ' . $key . '="' . htmlentities($val) . '"';
        }

        $links = array();

        // Extract existing links and tags
        $value = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links) { return '<' . array_push($links, $match[1]) . '>'; }, $value ?? '');

        $value = $value ?? '';

        // Extract text links for each protocol
        foreach ((array)$protocols as $protocol) {
            switch ($protocol) {
                case 'http':
                case 'https':   $value = preg_replace_callback('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { if ($match[1]) $protocol = $match[1]; $link = $match[2] ?: $match[3]; return '<' . array_push($links, "<a $attr href=\"$protocol://$link\">$protocol://$link</a>") . '>'; }, $value); break;
                case 'mail':    $value = preg_replace_callback('~([^\s<>]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr) { return '<' . array_push($links, "<a $attr href=\"mailto:{$match[1]}\">{$match[1]}</a>") . '>'; }, $value); break;
                default:        $value = preg_replace_callback('~' . preg_quote($protocol, '~') . '://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { return '<' . array_push($links, "<a $attr href=\"$protocol://{$match[1]}\">$protocol://{$match[1]}</a>") . '>'; }, $value); break;
            }
        }

        // Insert all link
        return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links) { return $links[$match[1] - 1]; }, $value ?? '');
    }

    /**
     * Generates unique ID of the application.
     */
    public static function getAppIdentifier()
    {
        $identifier = md5(config('app.key').parse_url(config('app.url'), PHP_URL_HOST));

        return $identifier;
    }

    /**
     * Are we in the mobile app.
     */
    public static function isInApp()
    {
        return (int)app('request')->cookie('in_app');
    }

    /**
     * Get identifier for queue:work
     */
    public static function getWorkerIdentifier($salt = '')
    {
        return md5((config('app.key') ?? '').$salt);
    }

    /**
     * Get pids of the specified processes.
     */
    public static function getRunningProcesses($search = '')
    {
        if (empty($search)) {
            $search = \Helper::getWorkerIdentifier();
        }

        $pids = [];

        try {
            $processes = preg_split("/[\r\n]/", shell_exec("ps aux | grep '".$search."'"));
            foreach ($processes as $process) {
                $process = trim($process);
                preg_match("/^[\S]+\s+([\d]+)\s+/", $process, $m);
                if (empty($m)) {
                    // Another format (used in Docker image).
                    // 1713 nginx     0:00 /usr/bin/php82...
                    preg_match("/^([\d]+)\s+[\S]+\s+/", $process, $m);
                }
                if (!preg_match("/(sh \-c|grep )/", $process) && !empty($m[1])) {
                    $pids[] = $m[1];
                }
            }
        } catch (\Exception $e) {
            // Do nothing
        }
        return $pids;
    }

    public static function uploadFile($file, $allowed_exts = [], $allowed_mimes = [])
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ($allowed_exts) {
            if (!in_array($ext, $allowed_exts)) {
                throw new \Exception(__('Unsupported file type'), 1);
            }
        }

        if ($allowed_mimes) {
            $mime_type = $file->getMimeType();
            if (!in_array($mime_type, $allowed_mimes)) {
                throw new \Exception(__('Unsupported file type'), 1);
            }
        }
        $file_name = \Str::random(25).'.'.$ext;

        $file_name = \Helper::sanitizeUploadedFileName($file_name, $file);

        $file->storeAs('uploads', $file_name);

        self::sanitizeUploadedFileData('uploads'.DIRECTORY_SEPARATOR.$file_name, self::getPublicStorage());

        return self::uploadedFilePath($file_name);
    }

    public static function sanitizeUploadedFileData($file_path, $storage, $content = null)
    {
        // Remove <script> from SVG files.
        if (strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) == 'svg'
            && $storage->exists($file_path)
        ) {
            if (!$content) {
                $content = $storage->get($file_path);
            }
            if ($content) {
                $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
                $storage->put($file_path, $content);
            }
        }
    }

    public static function uploadedFileRemove($name)
    {
        \Storage::delete('uploads/'.$name);
    }

    public static function uploadedFilePath($name)
    {
        return storage_path('uploads/'.$name);
    }

    public static function uploadedFileUrl($name)
    {
        return \Storage::url('uploads/'.$name);
    }

    public static function addSessionError($text, $key = 'default')
    {
        $errors = \Session::get('errors', new \Illuminate\Support\ViewErrorBag);

        if (! $errors instanceof \Illuminate\Support\ViewErrorBag) {
            $errors = new \Illuminate\Support\ViewErrorBag;
        }

        $message_bag = new \Illuminate\Support\MessageBag;
        $message_bag->add($key, $text);

        \Session::flash(
            'errors', $errors->put('default', $message_bag)
        );
    }

    public static function addFloatingFlash($text, $type = 'danger', $role = '')
    {
        $flashes = \Session::get('flashes_floating', []);

        $flashes[] = [
            'text' => $text,
            'type' => $type,
            'role' => $role,
        ];

        \Session::flash('flashes_floating', $flashes);
    }

    public static function isMySql()
    {
        return \DB::connection()->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'mysql';
    }

    public static function isPgSql()
    {
        return \DB::connection()->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'pgsql';
    }

    public static function humanFileSize($size, $unit="")
    {
        if ((!$unit && $size >= 1<<30) || $unit == "GB") {
            return number_format($size/(1<<30),2)."GB";
        }
        if ((!$unit && $size >= 1<<20) || $unit == "MB") {
            return number_format($size/(1<<20),2)."MB";
        }
        //if ((!$unit && $size >= 1<<10) || $unit == "KB") {
        return number_format($size/(1<<10),2)."KB";
        // }
        // return number_format($size)." bytes";
    }

    public static function isPrint()
    {
        return (bool)app('request')->input('print');
    }

    public static function isDev()
    {
        return config('app.env') != 'production';
    }

    public static function substrUnicode($str, $s, $l = null)
    {
        return join("", array_slice(preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY), $s, $l));
    }

    /**
     * Disable sql_require_primary_key option to avoid errors when migrating.
     * Only for MySQL.
     */
    public static function disableSqlRequirePrimaryKey()
    {
        if (!self::isMySql()) {
            return;
        }
        try {
            \DB::statement('SET SESSION sql_require_primary_key=0');
        } catch (\Exception $e) {
            // General error: 1193 Unknown system variable 'sql_require_primary_key'.
            // Do nothing.
        }
    }

    public static function downloadRemoteFileAsTmp($uri)
    {
        try {
            $contents = self::getRemoteFileContents($uri);

            if (!$contents) {
                return false;
            }

            $temp_file = self::getTempFileName();

            \File::put($temp_file, $contents);

            return $temp_file;

        } catch (\Exception $e) {

            \Helper::logException($e, 'Error downloading a remote file ('.$uri.'): ');

            return false;
        }
    }

    // Replacement for file_get_contents() as some hostings 
    // do not allow reading remote files via allow_url_fopen option.
    public static function getRemoteFileContents($url)
    {
        try {
            $headers = get_headers($url);

            // 307 - Temporary Redirect.
            if (!preg_match("/(200|301|302|307)/", $headers[0])) {
                return false;
            }

              $ch = curl_init();
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
              curl_setopt($ch, CURLOPT_URL, $url);
              $contents = curl_exec($ch);
              curl_close($ch);

            if (!$contents) {
                return false;
            }

            return $contents;

        } catch (\Exception $e) {

            \Helper::logException($e, 'Error downloading a remote file ('.$uri.'): ');

            return false;
        }
    }

    public static function getTempDir()
    {
        return sys_get_temp_dir() ?: '/tmp';
    }

    public static function getTempFileName()
    {
        return tempnam(self::getTempDir(), self::getTempFilePrefix());
    }

    public static function getTempFilePrefix()
    {
        return 'fs-'.substr(md5(config('app.key').'temp_prefix'), 0, 8).'_';
    }

    // Keep in mind that $uploaded_file->getClientMimeType() returns
    // incorrect mime type for images: application/octet-stream
    public static function downloadRemoteFileAsTmpFile($uri)
    {
        $file_path = self::downloadRemoteFileAsTmp($uri);
        if ($file_path) {
            return new \Illuminate\Http\UploadedFile(
                $file_path, basename($file_path),
                null, null, true
            );
        } else {
            return null;
        }
    }

    public static function sanitizeUploadedFileName($file_name, $uploaded_file = null, $contents = null)
    {
        // Check extension.
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (preg_match('/('.implode('|', self::$restricted_extensions).')/', $ext)) {
            // Add underscore to the extension if file has restricted extension.
            $file_name = $file_name.'_';
        } elseif ($ext == 'pdf') {
            // Rename PDF to avoid running embedded JavaScript.
            if ($uploaded_file && !$contents) {
                $contents = file_get_contents($uploaded_file->getRealPath());
            }
            if ($contents && strstr($contents, '/JavaScript')) {
                $file_name = $file_name.'_';
            }
        }

        return $file_name;
    }

    public static function remoteFileName($file_url)
    {
        return preg_replace("/\?.*/", '', basename($file_url));
    }

    public static function binaryDataMimeType($data)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($data);
    }

    /**
     * https://php.watch/versions/8.0/deprecated-reflectionparameter-methods
     */
    public static function getClass($param)
    {
        return $param->getType() && !$param->getType()->isBuiltin() ? new \ReflectionClass(method_exists($param->getType(), 'getName') ? $param->getType()->getName() : $param->getClass()->name) : null;
    }

    /**
     * https://php.watch/versions/8.0/deprecated-reflectionparameter-methods
     */
    public static function getClassName($param)
    {
        return $param->getType() && !$param->getType()->isBuiltin() ? method_exists($param->getType(), 'getName') ? $param->getType()->getName() : $param->getClass()->name : null;
    }

    public static function getWebCronHash()
    {
        return md5(config('app.key').'web_cron_hash');
    }

    public static function fixProtocol($url)
    {
        if (parse_url(config('app.url'), PHP_URL_SCHEME) == 'http' && parse_url($url, PHP_URL_SCHEME) != 'http') {
            return str_replace('https://', 'http://', $url);
        }

        if (parse_url(config('app.url'), PHP_URL_SCHEME) == 'https' && parse_url($url, PHP_URL_SCHEME) != 'https') {
            return str_replace('http://', 'https://', $url);
        }

        return $url;
    }

    /**
     * Fix and parse date to Carbon.
     */
    public static function parseDateToCarbon($date, $current_if_invalid = true)
    {
        if (preg_match('/\+0580/', $date)) {
            $date = str_replace('+0580', '+0530', $date);
        }
        $date = trim(rtrim($date));
        $date = preg_replace('/[<>]/', '', $date);
        $date = str_replace('_', ' ', $date);
        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            switch (true) {
                case preg_match('/([0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ UT)+$/i', $date) > 0:
                case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ UT)+$/i', $date) > 0:
                    $date .= 'C';
                    break;
                case preg_match('/([A-Z]{2,3}[\,|\ \,]\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}.*)+$/i', $date) > 0:
                case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4}\ \(.*)\)+$/i', $date) > 0:
                case preg_match('/([A-Z]{2,3}\, \ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4}\ \(.*)\)+$/i', $date) > 0:
                case preg_match('/([0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{2,4}\ [0-9]{2}\:[0-9]{2}\:[0-9]{2}\ [A-Z]{2}\ \-[0-9]{2}\:[0-9]{2}\ \([A-Z]{2,3}\ \-[0-9]{2}:[0-9]{2}\))+$/i', $date) > 0:
                    $array = explode('(', $date);
                    $array = array_reverse($array);
                    $date = trim(array_pop($array));
                    break;
            }
            try {
                return Carbon::parse($date);
            } catch (\Exception $_e) {
                if ($current_if_invalid) {
                    return Carbon::now();
                } else {
                    return null;
                }
            }
        }

        return null;
    }

    public static function urlHome()
    {
        return \Config::get('app.url');
        // $url = rtrim($url, "/");
        // return $url.'/home';
    }

    /**
     * Request::url() may return URL with incorrect protocol.
     * Use \Request::getRequestUri() instead.
     */
    /*public static function currentUrl()
    {
        $url = \Request::urlFull();
        if (\Str::startsWith(config('app.url'), 'http://') && !\Str::startsWith($url, 'http://')) {
            $url = str_replace('https://', 'http://', $url);
        }
        if (\Str::startsWith(config('app.url'), 'https://') && !\Str::startsWith($url, 'https://')) {
            $url = str_replace('http://', 'https://', $url);
        }
        return $url;
    }*/

    public static function isLocaleRtl(): bool
    {
        return in_array(app()->getLocale(), config("app.locales_rtl") ?? []);
    }

    public static function phoneToNumeric($phone)
    {
        $phone = preg_replace("/[^0-9]/", '', $phone);
        return (string)$phone;
    }

    public static function checkRequiredExtensions()
    {
        $php_extensions = [];
        foreach (\Config::get('installer.requirements.php') as $extension_name) {
            $alternatives = explode('/', $extension_name);
            if ($alternatives) {
                foreach ($alternatives as $alternative) {
                    $php_extensions[$extension_name] = extension_loaded(trim($alternative));
                    if ($php_extensions[$extension_name]) {
                        break;
                    }
                }
            } else {
                $php_extensions[$extension_name] = extension_loaded($extension_name);
            }
        }

        return $php_extensions;
    }

    public static function checkRequiredFunctions()
    {
        return [
            'shell_exec (PHP)' => function_exists('shell_exec'),
            'proc_open (PHP)' => function_exists('proc_open'),
            'fpassthru (PHP)' => function_exists('fpassthru'),
            'ps (shell)' => function_exists('shell_exec') ? shell_exec('ps') : false,
        ];
    }

    public static function isInstalled()
    {
        return file_exists(storage_path().DIRECTORY_SEPARATOR.'.installed');
    }

    public static function isConsole()
    {
        return app()->runningInConsole();
    }

    /**
     * Show a warning when background jobs sending emails
     * are not processed for some time.
     * https://github.com/freescout-helpdesk/freescout/issues/2808
     */
    public static function maybeShowSendingProblemsAlert()
    {
        $flashes = [];

        if (\Option::get('send_emails_problem')) {
            $flashes[] = [
                'type'      => 'warning',
                'text'      =>  __('There is a problem processing outgoing mail queue — an admin should check :%a_begin%System Status:%a_end% and :%a_begin_recommendations%Recommendations:%a_end%', ['%a_begin%' => '<a href="'.route('system').'#cron" target="_blank">', '%a_end%' => '</a>', /*'%a_begin_logs%' => '<a href="'.route('logs', ['name' => 'send_errors']).'#cron" target="_blank">',*/ '%a_begin_recommendations%' => '<a href="https://github.com/freescout-helpdesk/freescout/wiki/Background-Jobs" target="_blank">']),
                'unescaped' => true,
            ];
        }

        return $flashes;
    }
}
