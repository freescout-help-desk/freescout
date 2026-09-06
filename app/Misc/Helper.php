<?php

/**
 * Class for defining common app functions.
 */

namespace App\Misc;

use Carbon\Carbon;
use App\Email;
use App\Option;
use App\User;
use App\CustomerChannel;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\BufferedOutput;

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
     * MariaDB may not work with more than 999 elements in IN clause.
     * https://github.com/freescout-help-desk/freescout/issues/4623
     */
    const IN_LIMIT = 999;

    /**
     * Permissions for directories.
     */
    const DIR_PERMISSIONS = 0755;

    const DB_INT_MAX = 2147483647;

    const UPLOAD_MODE_DEFAULT = 'default';
    const UPLOAD_MODE_BY_CUSTOMER = 'customer';

    const EXCEPTION_UNSAFE_URL = 10000;
    const EXCEPTION_NOT_ALLOWED_FILE_EXTENSION = 10001;
    const EXCEPTION_NOT_ALLOWED_FILE_MIME_TYPE = 10002;

    public static $csp_nonce = null;

    /**
     * Stores list of global entities (for caching).
     */
    public static $global_entities = [];

    /**
     * Flat allowing not to include datepicker JS and CSS twice.
     */
    public static $datepicker_included = false;

    /**
     * Files with such extensions are being renamed on upload.
     */
    public static $restricted_extensions = [
        'php.*',
        // https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-27vp-fpg8-j8wv
        'pht',
        'phtm',
        'phps',
        'sh',
        'pl',
        'phtml',
        'phar',
        //'htaccess',
        //'user.ini',
        'shtml',
        'cgi',
        'asp',
        'aspx',
        'jsp',
        'config',
    ];

    /**
     * Hosts that are restricted blocked by SSRF protection.
     * Items must be in lowercase.
     */
    public static $restricted_ssrf_hosts = [
        '::1', // IPv6 loopback
        '0:0:0:0:0:0:0:1', // IPv6 loopback
        '0000:0000:0000:0000:0000:0000:0000:0001', // IPv6 loopback
        '::ffff:127.0.0.1', // IPv4-mapped IPv6
        '169.254.169.254', // AWS/GCP/Azure metadata
        '::ffff:169.254.169.254', // AWS/GCP/Azure metadata
        'fd00:ec2::254', // AWS/GCP/Azure metadata
        '::ffff:a9fe:a9fe', // AWS/GCP/Azure metadata
        '0000:0000:0000:0000:0000:ffff:a9fe:a9fe', // AWS/GCP/Azure metadata
        '0x00000000000000000000ffffa9fea9fe', // AWS/GCP/Azure metadata
        'fd00::/8', // IPv6 ULA
        '10.0.0.0/8', // RFC1918
        '172.16.0.0/12', // RFC1918
        'fd00::/8', // RFC1918
        '192.168.0.0/16',
        '0.0.0.0',
        '127.0.0.0/8', // Loopback addresses
        '127.0.0.1',
        'localhost',
        '100.64.0.0/10', // Carrier-grade NAT
        '169.254.0.0/16', // link-local
        '::ffff:0:0/96', // IPv4-mapped IPv6
        'fc00::/7', // all IPv6 ULA; fd00::/8 covers only half
        'fe80::/10', // IPv6 link-local
        '198.18.0.0/15', // benchmarking network
        '224.0.0.0/4', // multicast
        '::/128', // reserved
        '192.0.0.0/29',
        '192.0.2.0/24',
        '192.88.99.0/24',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '240.0.0.0/4',
        // IPv6 transition mechanisms that embed IPv4 addresses.
        // Prefix-only blocking here is a first line of defense; the
        // embedded IPv4 address is separately decoded and re-checked
        // against this same list in isSafeHost() via extractEmbeddedIPv4().
        // https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-v5xm-qw3f-qm98
        '2002::/16', // 6to4 (RFC 3056)
        '2001:0000::/32', // Teredo (RFC 4380)
        '64:ff9b::/96', // NAT64 Well-Known Prefix (RFC 6052)
        '64:ff9b:1::/48', // NAT64 Local-Use Prefix (RFC 8215)
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
        'az' => ['name'          => 'Azərbaycan',
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
        'zh-CN' => ['name'          => '简体中文 (Simplified Chinese)',
                    'name_en'       => 'Chinese (Simplified)',
        ],
        'zh-SG' => ['name'          => '简体中文 (Singapore)',
                    'name_en'       => 'Chinese (Singapore)',
        ],
        'zh-TW' => ['name'          => '繁體中文 (Traditional Chinese)',
                    'name_en'       => 'Chinese (Traditional)',
        ],
        'zh-HK' => ['name'          => '繁體中文 (Hong Kong)',
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
        'kz' => ['name'          => 'қазақ тілі',
                 'name_en'       => 'Kazakh',
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
     * Strip all tags, double spaces, etc to show HTML as text.
     * Differences from strip_tags():
     * - Strips new lines
     * - Strips double spaces
     * - Strips <script> and <style> elements along with their content
     */
    public static function stripTags($text)
    {
        // Remove all kinds of spaces after tags.
        // https://stackoverflow.com/questions/3230623/filter-all-types-of-whitespace-in-php
        // 
        // Initially this function was used to prepare short previews for conversations.
        // Now it's not clear why this preg_replace() was needed.
        // 
        // Keep in mind that preg_replace() may return NULL if "u" flag is used.
        //$text = preg_replace("/^(.*)>[\r\n]*\s+/mu", '$1>', $text ?? '');

        // Remove <script> and <style> blocks.
        $text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $text ?? '');
        $text = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $text ?? '');

        // Remove tags.
        $text = strip_tags($text ?? '');
        $text = preg_replace('/\s+/mu', ' ', $text ?? '');

        // Trim
        $text = trim($text ?? '');
        $text = preg_replace('/^\s+/mu', '', $text ?? '');

        // Causes "General error: 1366 Incorrect string value"
        // Remove "undetectable" whitespaces
        // $whitespaces = ['%81', '%7F', '%C5%8D', '%8D', '%8F', '%C2%90', '%C2', '%90', '%9D', '%C2%A0', '%A0', '%C2%AD', '%AD', '%08', '%09', '%0A', '%0D'];
        // $text = urlencode($text);
        // foreach ($whitespaces as $char) {
        //     $text = str_replace($char, ' ', $text);
        // }
        // $text = urldecode($text);

        // Remove double spaces.
        $text = trim(preg_replace('/[ ]+/', ' ', $text ?? ''));

        return $text;
    }

    public static function stripTagsFromArray($data, $fields = [])
    {
        if (empty($fields)) {
            $fields = array_keys($data);
        }
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            if (is_array($data[$field])) {
                foreach ($data[$field] as $sub_field => $sub_data) {
                    $data[$field][$sub_field] = self::stripTagsFromArray($sub_data);
                }
            } else {
                if ($data[$field] !== null) {
                    $data[$field] = \Helper::stripTags($data[$field]);
                }
            }
        }

        return $data;
    }

    /**
     * Strips dangerous tags along with their content.
     */
    public static function stripDangerousTags($html, $allowed_tags = [])
    {
        // <script src="/storage/attachment/8/1/1/test.js?id=7&token=c4786c4497db3c6254a0c310623a43c3">
        // <iframe src="/storage/attachment/8/1/1/1.html?id=95&token=3dced8dc80305031b358119f3d156204"></iframe>
        // <object data="/storage/attachment/8/1/1/1.html?id=95&token=3dced8dc80305031b358119f3d156204" type="text/html"></object>
        $unsafe_tags = [
            'script',
            'form',
            'iframe',
            'link',
            'object',
            'meta',
            'embed',
            'applet',
            // https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-fh99-wr77-pxq3
            'style',
        ];
        $attrs = 'src|data';

        $tags = array_diff($unsafe_tags, $allowed_tags);

        foreach ($tags as $tag) {
            // https://github.com/freescout-help-desk/freescout/issues/5424
            //$html = preg_replace('#<'.$tag.'(.*?)>(.*?)<\s*/\s*'.$tag.'\s*>#is', '', $html ?? '');
            $new = preg_replace('#<'.$tag.'\b[^>]*>(.*?)<\s*/\s*'.$tag.'\s*>#is', '', $html ?? '');
            if ($new !== null) {
                $html = $new;
            }

            // Remove unclosed restricted tags.
            // And keep removing the tag until it's not found anymore.
            // https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-jpq8-j69f-mj98
            //$html = preg_replace('#<'.$tag.'(.*?)>#is', '', $html ?? '');
            do {
                $found = false;
                $new = preg_replace('#<'.$tag.'\b[^>]*>#is', '', $html ?? '');
                if ($new !== null) {
                    if ($new != $html) {
                        $found = true;
                    }
                    $html = $new;
                }
            } while ($found);

        }

        // Make sure that attributes do not point to the file on the current server.
        foreach ($unsafe_tags as $tag) {
            // Skip some tags.
            if (in_array($tag, ['iframe'])) {
                continue;
            }
            $html = preg_replace_callback('#<'.$tag.'(.*?)>#is', 
                function ($matches) use ($attrs) {
                    preg_match("/(src|data)\s*=\s*['\"]([^'\"]+)['\"]/i", $matches[1], $attr_match);
                    if (!empty($attr_match[2])) {
                        $url = trim($attr_match[2]);
                        $parts = parse_url($url);

                        // Remove tag.
                        if (!preg_match("#^(https?:)?//#i", $url)
                            || empty($parts['host'])
                            || (strtolower($parts['host']) == strtolower(self::getDomain()))
                            || preg_match("#/storage/attachment/.*token#", $parts['host'])
                            || preg_match("#/storage/uploads/.*\.#", $parts['host'])
                        ) {
                            return '';
                        }
                    }

                    return $matches[0];
                },
                $html
            );
        }

        return $html;
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
        // Replace <br> with spaces.
        $text = preg_replace('/<br\s*\/?>/i', ' ', $text);

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
    public static function resizeImage($file, $mime_type, $thumb_width, $thumb_height, $transparency = false)
    {
        list($width, $height) = getimagesize($file);
        if (!$width) {
            return false;
        }

        if (preg_match('/png/i', $mime_type)) {
            $src = imagecreatefrompng($file);

            if (!$transparency) {
                $kek = imagecolorallocate($src, 255, 255, 255);
                imagefill($src, 0, 0, $kek);
            }
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
        if ($transparency && preg_match('/png/i', $mime_type)) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }
        // Resize and crop
        imagecopyresampled(
            $thumb,
            $src,
            ceil(0 - ($new_width - $thumb_width) / 2), // Center the image horizontally
            ceil(0 - ($new_height - $thumb_height) / 2), // Center the image vertically
            0, 0,
            ceil($new_width),
            ceil($new_height),
            $width, $height
        );
        if (PHP_VERSION_ID < 80000) {
            imagedestroy($src);
        }

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

    public static function denyAccess($msg = '', $display_to_visitor = false)
    {
        $msg = $msg ?: 'This action is unauthorized.';

        if ($display_to_visitor) {
            $msg .= '[display]';
        }

        abort(403, $msg);
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
        // Sanitize URL.
        try {
            if (!self::sanitizeRemoteUrl($url)) {
                throw new \Exception('URL points to the local host', 1);
            }
        } catch (\Exception $e) {
            \Helper::logException($e, 'Error downloading a remote file ('.$url.'): ');
            return false;
        }

        $client = new \GuzzleHttp\Client();

        try {
            $client->request('GET', $url, \Helper::setGuzzleDefaultOptions([
                'sink' => $destinationFilePath,
                'timeout' => 300, // seconds
                'connect_timeout' => 7,
            ]));
        } catch (\Exception $e) {
            self::logException($e);
            return false;
        }

        return true;
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

    public static function encrypt($value, $password = null)
    {
        $encrypted_value = $value;

        try {
            if (!$password) {
                $encrypted_value = encrypt($value);
            } else {
                // Derive a 32-byte key (AES-256 requires 32 bytes).
                $key = hash('sha256', $password, true);
                // Create encrypter instance.
                $encrypter = new \Illuminate\Encryption\Encrypter($key, 'AES-256-CBC');
                // Encrypt text.
                $encrypted_value = $encrypter->encrypt($value);

                // $data = [
                //     'v' => $value,
                //     'p' => hash('sha256', $password),
                // ];
                // $value = encrypt($data);
            }
        } catch (\Exception $e) {
            //self::logException($e);
        }

        return $encrypted_value;
    }

    /**
     * Decrypt value with or without a password.
     */
    public static function decrypt($value, $password = null, $force_unserialize = false, $return_value_on_error = false)
    {
        $decrypted_value = $value;

        try {
            if (!$password) {
                $decrypted_value = app('encrypter')->decrypt($value, false);
            } else {
                $key = hash('sha256', $password, true);
                $encrypter = new \Illuminate\Encryption\Encrypter($key, 'AES-256-CBC');
                // Decrypt.
                $decrypted_value = $encrypter->decrypt($value);

                // $decrypted = decrypt($value);
                // if (is_array($decrypted)
                //     && !empty($decrypted['v'])
                //     && !empty($decrypted['p'])
                //     && hash('sha256', $password) === $decrypted['p']
                // ) {
                //     $value = $decrypted['v'];
                // }
            }

            // If the decrypted_value is scalar - unserialize it,
            // Otherwise - do not, as objects may contain dangerous code.
            if (preg_match("#^[idsa]:#", $decrypted_value) || $force_unserialize) {
                $decrypted_value = unserialize($decrypted_value, ['allowed_classes' => false]);
            }
        } catch (\Exception $e) {
            //self::logException($e);
            if ($return_value_on_error) {
                return $value;
            } else {
                return '';
            }
        }

        return $decrypted_value;
    }

    /**
     * This version of decrypt() returns original value if decryption error occurs.
     */
    public static function decryptSoft($value, $password = null)
    {
        return self::decrypt($value, $password, $force_unserialize = false, $return_value_on_error = true);
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

    public static function setUserLocale($user_locale = '')
    {
        if (!$user_locale) {
            $user_locale = \Eventy::filter('locale', session('user_locale'));
        }
        if ($user_locale) {
            \Helper::setLocale($user_locale);
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
            return null;
        }

        if ($param) {
            if (isset(self::$locales[$locale])) {
                return self::$locales[$locale][$param];
            } else {
                return null;
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

        $value = preg_replace("#[\r\n\t]#", '', $value);

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

    public static function getAppLocale()
    {
        return config('app.locale');
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
            $text = preg_replace('/<img\b[^>]*src=\"([^>"]+)\"[^>]*>/i', "<div>$1</div>", $text);
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
        } catch (\Exception $e) {
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
        $space = html_entity_decode('&nbsp;');

        $str = strtr($str, [
            '、' => '、'.$space,
            '。' => '。'.$space,
            // '.' => '.'.$space,
            // ',' => ','.$space,
            //':' => ':'.$space,
            // '—' => '—'.$space,
            // '।' => '।'.$space,
        ]);

        $array_words = explode($space, $str);

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
     * Json encode to avoid "Unable to JSON encode payload. Error code: 5"
     */
    public static function jsonEncodeSafe($value, $options = 0, $depth = 512, $attempt = 1)
    {
        $msg = '';
        
        $encoded = json_encode($value, $options, $depth);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $encoded;
            case JSON_ERROR_DEPTH:
                $msg = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $msg = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $msg = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $msg = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $clean = self::utf8ize($value);
                if ($attempt > 1) {
                    $msg = 'UTF8 encoding error';
                } else {
                    return self::jsonEncodeSafe($clean, $options, $depth, 2);
                }
                break;
            // default:
            //     return '';
        }
        throw new \Exception("Could not encode JSON: ".$msg, 1);
        //return '';
    }

    public static function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = self::utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return self::utf8Encode($mixed);
        }
        return $mixed;
    }

    /**
     * Replacement for utf8_encode().
     */
    public static function utf8Encode($string)
    {
        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }

    /**
     * Check if host is available on the port specified.
     */
    public static function checkPort($host, $port)
    {
        // Sanitize URL.
        self::sanitizeRemoteUrl('https://'.$host, true);

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
     * Check if the password consists of asterisks only.
     */
    public static function isSafePassword($password)
    {
        return preg_match("/^\*+$/", $password ?? '');
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
        $value = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links) {
            return '<' . array_push($links, $match[1]) . '>';
        }, $value ?? '') ?: $value;

        $value = $value ?? '';

        // Extract text links for each protocol
        foreach ((array)$protocols as $protocol) {
            switch ($protocol) {
                case 'http':
                case 'https':
                    //$value = preg_replace_callback('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { 
                    //$value = preg_replace_callback('%(\b(([\w-]+)://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))%s', function ($match) use ($protocol, &$links, $attr) { 
                    // https://github.com/freescout-helpdesk/freescout/issues/3402
                    $nbsp = html_entity_decode('&nbsp;');
                    // Use a tempered greedy token (?:(?!&(?:gt|lt);)[^\s()<>])+ so the URL
                    // body stops before an HTML angle-bracket entity (&gt; / &lt;) that
                    // purifyHtml() introduces for a literal > or < at the end of a plain-text
                    // URL. The original [^\s()<>]+ treats &, g, t, ; as ordinary URL characters
                    // (only the literal > is excluded), so &gt; ended up in the href (issue #5423).
                    // $value = preg_replace_callback('%([>\r\n\s:;\( '.$nbsp.']|^)((([\w-]+)://?|www[.])(?:(?!&(?:gt|lt);)[^\s()<>])+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))%s', function ($match) use ($protocol, &$links, $attr) {
                    $value = preg_replace_callback('%([>\r\n\s:;\( '.$nbsp.']|^)((([\w-]+)://?|www[.])(?:(?!&(?:gt|lt);)[^\s()<>])+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))%s', function ($match) use ($protocol, &$links, $attr) {
                        if ($match[4]) {
                            $protocol = $match[4];
                        }
                        $link = $match[2];
                        $link = substr($link, strlen($match[3]));
                        // https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-49pm-xwqj-vwjp
                        //return '<' . array_push($links, "<a $attr href=\"$protocol://$link\">$protocol://$link</a>") . '>';
                        $href = self::encodeQuotes($protocol.'://'.$link, ENT_QUOTES, 'UTF-8');
                        $link_text = self::encodeQuotes($match[2], ENT_QUOTES, 'UTF-8');
                        return $match[1].'<' . array_push($links, "<a $attr href=\"".$href."\">".$link_text."</a>") . '>';
                    }, $value) ?: $value;
                    break;
                case 'mail':
                    $value = preg_replace_callback('~([^\s<>]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:\)])~', function ($match) use (&$links, $attr) {
                        $href = self::encodeQuotes($match[1]);
                        $link_text = $href;
                        return '<' . array_push($links, "<a $attr href=\"mailto:{$href}\">{$link_text}</a>") . '>';
                    }, $value) ?: $value;
                    break;
                default:
                    $value = preg_replace_callback('~' . preg_quote($protocol, '~') . '://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) {
                        $href = self::encodeQuotes("$protocol://{$match[1]}", ENT_QUOTES, 'UTF-8');
                        $link_text = self::encodeQuotes("$protocol://{$match[1]}", ENT_QUOTES, 'UTF-8');
                        return '<' . array_push($links, "<a $attr href=\"{$href}\">{$link_text}</a>") . '>';
                    }, $value) ?: $value;
                    break;
            }
        }

        // Insert all links
        return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links) { 
            return $links[$match[1] - 1];
        }, $value ?? '') ?: $value;
    }

    public static function encodeQuotes($str)
    {
        return str_replace('"', '&quot;', $str);
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
    public static function isInApp($request = null)
    {
        if (!$request) {
            $request = app('request');
        }
        return (int)$request->cookie('in_app');
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
            $processes = preg_split("/[\r\n]/", \Helper::shellExec("ps aux | grep '".$search."'"));
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

    /**
     * Upload file into /storage/app/public/uploads.
     */
    public static function uploadFile($file, $allowed_exts = [], $allowed_mimes = [])
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (empty($allowed_exts)) {
            $allowed_exts = config('app.allowed_extensions');
        }

        /*if ($allowed_exts) {
            if (!in_array($ext, $allowed_exts)) {
                throw new \Exception(__('Unsupported file type').' (.env » APP_ALLOWED_EXTENSIONS="...")', self::EXCEPTION_NOT_ALLOWED_FILE_EXTENSION);
            }
        }*/

        $mime_type = $file->getMimeType();

        if ($allowed_mimes) {
            if (!in_array($mime_type, $allowed_mimes)) {
                throw new \Exception(__('Unsupported file type'), self::EXCEPTION_NOT_ALLOWED_FILE_MIME_TYPE);
            }
        }
        $file_name = \Str::random(25).'.'.$ext;

        $file_name = \Helper::sanitizeUploadedFileName($file_name, $file, null, $mime_type, self::UPLOAD_MODE_DEFAULT, $allowed_exts);

        $file->storeAs('uploads', $file_name);

        self::sanitizeUploadedFileData('uploads'.DIRECTORY_SEPARATOR.$file_name, self::getPublicStorage());

        return self::uploadedFilePath($file_name);
    }

    public static function sanitizeUploadedFileData($file_path, $storage, $content = null)
    {
        // Remove <script>, href="", iframe, etc from SVG files.
        // Any image can be interpreted as SVG by browser,
        // so checking extension is not enough.
        if ($storage->exists($file_path)
            && ($storage->mimeType($file_path) == 'image/svg+xml' 
                || strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) == 'svg')
        ) {
            if (!$content) {
                $content = $storage->get($file_path);
            }
            if ($content) {
                // Remove comments from SVG content.
                // https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-cvr8-cw5c-5pfw
                $content = preg_replace('/<!--(.|\s)*?-->/', '', $content);

                $svg_sanitizer = new \enshrined\svgSanitize\Sanitizer();
                $clean_content = $svg_sanitizer->sanitize($content);
                // If XML parsing fails, remove <script> tags. Other attributes will be protected by CSP.
                if (!$clean_content)  {
                    $clean_content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
                }
                $storage->put($file_path, $clean_content);
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

    public static function sqlLikeOperator()
    {
        return self::isPgSql() ? 'ilike' : 'like';
    }

    // Escape special characters for a LIKE query.
    public static function sqlEscapeLike($value, $char = '\\')
    {
        return str_replace(
            [$char, '%', '_'],
            [$char.$char, $char.'%', $char.'_'],
            $value
        );
    }

    // PostgreSQL truncates string if it contains \u0000 symbol starting from this symbol.
    // https://stackoverflow.com/questions/31671634/handling-unicode-sequences-in-postgresql
    // https://github.com/freescout-helpdesk/freescout/issues/3485
    public static function sqlSanitizeString($string)
    {
        return str_replace(json_decode('"\u0000"'), "", $string);
    }

    public static function humanFileSize($size, $unit = "")
    {
        if ((!$unit && $size >= 1<<30) || $unit == "GB") {
            return number_format($size/(1<<30), 2)."GB";
        }
        if ((!$unit && $size >= 1<<20) || $unit == "MB") {
            return number_format($size/(1<<20), 2)."MB";
        }
        //if ((!$unit && $size >= 1<<10) || $unit == "KB") {
        return number_format($size/(1<<10), 2)."KB";
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

    public static function downloadRemoteFileAsTmp($uri, $follow_redirects = true)
    {
        try {
            // Sanitize URL first.
            if (!self::sanitizeRemoteUrl($uri)) {
                throw new \Exception('URL points to the local host', 1);
            }
            $contents = self::getRemoteFileContents($uri, $follow_redirects);

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
    public static function getRemoteFileContents($url, $follow_redirects = true)
    {
        try {
            // Sanitize URL first.
            if (!self::sanitizeRemoteUrl($url)) {
                throw new \Exception('URL points to the local host', 1);
            }

            $headers = get_headers($url);

            // 307 - Temporary Redirect.
            if (!preg_match("/(200|301|302|307)/", $headers[0])) {
                throw new \Exception('HTTP Status Code: '.$headers[0], 1);
                //return false;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            if ($follow_redirects) {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            \Helper::setCurlDefaultOptions($ch);
            curl_setopt($ch, CURLOPT_TIMEOUT, 180);
            $contents = curl_exec($ch);

            $curl_errno = curl_errno($ch);

            if ($curl_errno) {
                throw new \Exception('Curl Error Number: '.$curl_errno, 1);
            }

            $https_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (PHP_VERSION_ID < 80000) {
                \curl_close($ch);
            }

            if ($contents == '') {
                throw new \Exception('Empty Response. Curl Error Number: '.$curl_errno.'. Response Status Code: '.$https_status, 1);
            } elseif ($https_status != 200) {
                throw new \Exception('Curl HTTP Code: '.$https_status, 1);
            }

            return $contents;

        } catch (\Exception $e) {

            \Helper::logException($e, 'Error downloading a remote file ('.$url.'): ');

            return false;
        }
    }

    public static function sanitizeRemoteUrl($url, $throw_exception = false, $follow_redirects = true)
    {
        if (!self::checkUrlIpAndHost($url, $throw_exception)) {
            return '';
        }

        // Follow redirects and check all IPs/hosts.
        if ($follow_redirects) {
            for ($i = 0; $i < 20; $i++) { 
                $redirected_url = self::curlGetNextRedirectedUrl($url);

                if ($redirected_url == $url || !$redirected_url) {
                    break;
                }

                if ($redirected_url != $url) {
                    if (!self::checkUrlIpAndHost($redirected_url, $throw_exception)) {
                        return '';
                    }
                }
                $url = $redirected_url;
            }
        }

        return $url;
    }

    /**
     * If URL contains IPv6 and port the IPv6 address MUST be in brackets.
     * If there is no port specified the function will be able to digest the URL.
     * Accepts only canonical IPs (inputs like 127.1 or 2130706433 are rejected).
     * 
     * Returns URL if host can not be extracted.
     */
    public static function checkUrlIpAndHost($url, $throw_exception = false)
    {
        $url = self::normalizeIPv6InUrl($url ?? '');

        $parts = parse_url($url);

        // Sanitize protocol to avoid access to local files.
        if (empty($parts['scheme']) || !in_array($parts['scheme'], ['http', 'https'])) {
            return '';
        }

        // Sanitize host.
        if (empty($parts['host'])) {
            return '';
        }

        $host_white_list_str = str_replace(' ', '', mb_strtolower(config('app.remote_host_white_list')));
        $host_white_list = explode(',', $host_white_list_str);

        // Sanitize host name.
        $host = mb_strtolower($parts['host'] ?? '');
        $hostname = gethostname();
        $host_ip = gethostbyname($hostname);

        $restricted_hosts = [
            $hostname,
            $host_ip,
            mb_strtolower(self::getDomain()),
            $_SERVER['SERVER_ADDR'] ?? '',
            $_SERVER['LOCAL_ADDR'] ?? '',
        ];

        $restricted_hosts = array_merge($restricted_hosts, self::$restricted_ssrf_hosts);

        // IPv6 URLs look like http://[::1].
        // Remove square brackets from hosts (for IPv6 addresses).
        $host = str_replace(['[', ']'], '', $host);

        // Host could not be determined.
        if (!$host) {
            return $url;
        }

        $hosts_to_check = [$host];

        // If this is hexadecimal representation of the IP address.
        $host_hex_to_ip = self::hexToIp($host);
        if ($host_hex_to_ip && $host_hex_to_ip != $host) {
            $hosts_to_check[] = $host_hex_to_ip;
        }

        // Check only if host name is passed in URL.
        if (!self::isValidIp($host)) {

            // Sanitize host IP address.
            $remote_host_ip = gethostbyname($host);
            if ($remote_host_ip && !in_array($remote_host_ip, $hosts_to_check)) {
                $hosts_to_check[] = $remote_host_ip;
            }

            // Resolve DNS records.
            $dns_records = [];
            try {
                $dns_records = dns_get_record($host, DNS_A | DNS_AAAA);
            } catch (\Exception $e) {
                // Do nothing.
            }
            if ($dns_records) {
                foreach ($dns_records as $dns_record) {
                    if (!empty($dns_record['ip']) && !in_array($dns_record['ip'], $hosts_to_check)) {
                        $hosts_to_check[] = $dns_record['ip'];
                    }
                    if (!empty($dns_record['ipv6']) && !in_array($dns_record['ipv6'], $hosts_to_check)) {
                        $hosts_to_check[] = $dns_record['ipv6'];
                    }
                }
            }
        }

        foreach ($hosts_to_check as $host_item) {
            if (!self::isSafeHost($host_item, $restricted_hosts, $host_white_list)) {
                if ($throw_exception) {
                    throw new \Exception(__('Domain or IP address is not allowed: :%host%. Whitelist it via APP_REMOTE_HOST_WHITE_LIST .env parameter.', ['%host%' => $host_item]), self::EXCEPTION_UNSAFE_URL);
                } else {
                    return '';
                }
            }
        }

        return $url;
    }

    public static function isSafeHost($host, $restricted_hosts = [], $host_white_list = [])
    {
        if (in_array($host, $host_white_list) || self::checkIpByMask($host, $host_white_list)) {
            return true;
        }

        // Is IP restricted.
        if (in_array($host, $restricted_hosts) || self::checkIpByMask($host, $restricted_hosts)) {
            return false;
        }

        // IPv6 transition mechanisms (6to4, Teredo, NAT64) embed an IPv4
        // address inside an IPv6 address. Prefix-only blocking of
        // 2002::/16, 2001:0000::/32, 64:ff9b::/96, etc. above only stops
        // this host from routing the address as a transition address —
        // it does not stop a downstream proxy/CDN/gateway that DOES
        // support 6to4/NAT64/Teredo from resolving it back to the
        // embedded internal IPv4 address. Decode and re-check it here.
        foreach (self::extractEmbeddedIPv4($host) as $embedded_ip) {
            if (in_array($embedded_ip, $restricted_hosts) || self::checkIpByMask($embedded_ip, $restricted_hosts)) {
                return false;
            }
        }

        // Hex integer or 0x7f.0x0.0x0.0x1
        if (substr($host, 0, 2) == '0x') {
            return false;
        }

        if (self::isValidIp($host)) {
            // Is IP canonical.
            // This accepts only standard IPv4/IPv6 text forms.
            // Values like 127.1 or 2130706433 are rejected.
            if (inet_pton($host) === false) {
                return false;
            }
        } else {
            // If $host is an IP address, make sure that it's canonical.

            // Convert IDN to ASCII.
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if ($ascii === false || filter_var($ascii, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
                // This is NOT a host name, this is IP - make sure that it's canonical.
                if (inet_pton($host) === false) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function isValidIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    // Normalize URLs with IPv6 addresses by adding brackets.
    // URLs with port must already have brackets.
    public static function normalizeIPv6InUrl($url)
    {
        // Skip if already has brackets
        if (preg_match('/\[[a-fA-F0-9:\.]+\]/', $url)) {
            return $url;
        }

        $pattern = '/^(https?:\/\/)([^\/?#]+:[^\/?#]+)([\/?#]+|$)/';

        return preg_replace($pattern, '$1[$2]$3', $url);
    }

    // Get next redicred URL.
    public static function curlGetNextRedirectedUrl($url, $throw_exception = false)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        //curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        curl_setopt($ch, CURLOPT_URL, $url);
        \Helper::setCurlDefaultOptions($ch);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        $curl_errno = curl_errno($ch);

        //$redirected_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        //if ($curl_errno && $curl_errno != CURLE_TOO_MANY_REDIRECTS && !$redirected_url) {
        if ($curl_errno) {
            if ($throw_exception) {
                throw new \Exception('Could not check URL contents by following redirects: '.$curl_errno, 1);
            } else {
                return '';
            }
        }

        //$info = curl_getinfo($ch);
        //if ($info['http_code'] >= 300 && $info['http_code'] < 400) {

        // Extract Location header from response.
        $redirected_url = '';

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response ?? '', 0, $header_size);
        preg_match('/Location: (.*)/i', $headers, $matches);

        if (isset($matches[1])) {
            $redirected_url = trim($matches[1]);
        }
        //}

        if (PHP_VERSION_ID < 80000) {
            \curl_close($ch);
        }

        return $redirected_url;
    }

    // Returns mask or false.
    public static function checkIpByMask($ip, $masks = [])
    {
        foreach ($masks as $mask) {
            if (!strstr($mask, '/')) {
                continue;
            }
            if (\Symfony\Component\HttpFoundation\IpUtils::checkIp($ip, $mask)) {
                return $mask;
            }
        }
        return false;
    }

    /**
     * Extract IPv4 address(es) embedded in an IPv6 transition-mechanism
     * address (6to4, Teredo, NAT64), so the embedded address can be
     * re-checked against the SSRF blocklist.
     *
     * https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-v5xm-qw3f-qm98
     *
     * @param  string $ip
     * @return array List of embedded IPv4 addresses found (may be empty;
     *                Teredo can yield two: server and client).
     */
    public static function extractEmbeddedIPv4($ip)
    {
        $embedded = [];

        // Strip brackets / zone id (e.g. 'fe80::1%eth0') before parsing.
        $ip = trim($ip ?? '', '[]');
        $ip = preg_replace('/%.*$/', '', $ip);

        if (strpos($ip, ':') === false) {
            return $embedded; // Not IPv6, nothing to extract.
        }

        $packed = @inet_pton($ip);
        if ($packed === false || strlen($packed) !== 16) {
            return $embedded; // Not a valid, canonical IPv6 address.
        }

        $hex = bin2hex($packed); // 32 hex chars = 128 bits.

        // 6to4 (2002::/16): 2002:WWXX:YYZZ::/16 - the IPv4 is packed into
        // the 32 bits immediately following the 2002 prefix.
        if (substr($hex, 0, 4) === '2002') {
            $v4 = self::hexToIPv4(substr($hex, 4, 8));
            if ($v4) {
                $embedded[] = $v4;
            }
        }

        // Teredo (2001:0000::/32): 2001:0000:SERVER-V4:FLAGS:PORT:CLIENT-V4
        // (client IPv4 is XOR-obfuscated with 0xFFFFFFFF per RFC 4380).
        // Both the server and client addresses are attacker-influenced
        // input here, so check both.
        if (substr($hex, 0, 8) === '20010000') {
            $server_v4 = self::hexToIPv4(substr($hex, 8, 8));
            if ($server_v4) {
                $embedded[] = $server_v4;
            }

            $client_hex = substr($hex, 24, 8);
            if (strlen($client_hex) === 8) {
                $client_int = hexdec($client_hex) ^ 0xFFFFFFFF;
                $embedded[] = long2ip($client_int);
            }
        }

        // NAT64 Well-Known Prefix (64:ff9b::/96, RFC 6052):
        // 64:ff9b:0000:0000:0000:0000:xxxx:xxxx - last 32 bits = IPv4.
        if (substr($hex, 0, 24) === '0064ff9b0000000000000000') {
            $v4 = self::hexToIPv4(substr($hex, 24, 8));
            if ($v4) {
                $embedded[] = $v4;
            }
        }

        // NAT64 Local-Use Prefix (64:ff9b:1::/48, RFC 8215). Operators may
        // choose any prefix length from /32 to /96 within this block, so
        // the exact position of the embedded IPv4 isn't fixed. As a
        // conservative heuristic, decode the last 32 bits whenever the
        // /48 local-use prefix matches.
        if (substr($hex, 0, 12) === '0064ff9b0001') {
            $v4 = self::hexToIPv4(substr($hex, 24, 8));
            if ($v4) {
                $embedded[] = $v4;
            }
        }

        return array_unique($embedded);
    }

    /**
     * Convert 8 hex characters (32 bits) to dotted-quad IPv4 notation.
     *
     * @param  string $hex
     * @return string|null
     */
    public static function hexToIPv4($hex)
    {
        if (strlen($hex) !== 8 || !ctype_xdigit($hex)) {
            return null;
        }

        return implode('.', [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
            hexdec(substr($hex, 6, 2)),
        ]);
    }

    /**
     * Convert hexadecimal representation to IP address (IPv4 or IPv6):
     * - 0x7f000001 >> 127.0.0.1
     * 
     * @param string $hex Hexadecimal string (with or without '0x' prefix)
     * @return string|false IP address, or false on failure
     */
    public static function hexToIp($hex)
    {
        // Remove '0x' or '0X' prefix if present
        $clean_hex = preg_replace('/^0x/i', '', $hex);

        // Validate hex string
        if (!preg_match('/^[0-9a-f]+$/i', $clean_hex)) {
            return false;
        }

        // Pad to even length for bin2hex compatibility
        if (strlen($clean_hex) % 2 !== 0) {
            $clean_hex = '0' . $clean_hex;
        }

        $packed = hex2bin($clean_hex);
        if ($packed === false) {
            return false;
        }

        $ip = inet_ntop($packed);

        return $ip !== false ? $ip : false;
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
    public static function downloadRemoteFileAsTmpFile($uri, $follow_redirects = true)
    {
        $file_path = self::downloadRemoteFileAsTmp($uri, $follow_redirects);
        if ($file_path) {
            return new \Illuminate\Http\UploadedFile(
                $file_path, basename($file_path),
                null, null, true
            );
        } else {
            return null;
        }
    }

    public static function sanitizeUploadedFileName($file_name, $uploaded_file = null, $contents = null, $mime_type = '', $upload_mode = self::UPLOAD_MODE_DEFAULT, $allowed_exts = [])
    {
        $pdf_mime_types = [
            'application/pdf', 'application/x-pdf', 'application/acrobat',
            'applications/vnd.pdf', 'text/pdf', 'text/x-pdf',
        ];
        // Remove illegal chars.
        $illegal_chars = [
            // Unix.
                '/',
                chr(0),
            // Windows.
                '<',
                '>',
                ':',
                '"',
                '/',
                '\\',
                '|',
                '?',
                '*',
            // Macos.
                ':',
        ];
        // 0-31 (ASCII control characters) for Windows.
        for ($i = 0; $i < 32; $i++) {
            $illegal_chars[] = chr($i);
        }

        $escaped_regex = preg_quote(implode('', $illegal_chars), '/');

        // https://github.com/freescout-helpdesk/freescout/issues/3377
        $file_name = mb_convert_encoding($file_name, 'UTF-8', 'UTF-8');
        $file_name = preg_replace('/[' . $escaped_regex . ']/', '_', $file_name);
        $file_name = preg_replace("/[\t\r\n]/", '', $file_name);
        // Remove unprintable characters and invalid unicode characters.
        $file_name = self::stripUnprintableAndUnsafeChars($file_name);

        // Check extension.
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Add underscore to the extension if file has restricted extension.
        $rename = false;
        if (preg_match('/^('.implode('|', self::$restricted_extensions).')$/', $ext) 
            || ($allowed_exts && !preg_match('/^('.implode('|', $allowed_exts).')$/', $ext) )
            || mb_substr($file_name, 0, 1) == '.'
        ) {
            $rename = true;
        } elseif ($upload_mode == self::UPLOAD_MODE_BY_CUSTOMER) {
            $customer_allowed_extensions = config('app.customer_allowed_extensions') ?? [];
            $customer_allowed_mime_types = config('app.customer_allowed_mime_types') ?? [];

            if (!in_array($ext, $customer_allowed_extensions) || ($mime_type && !in_array($mime_type, $customer_allowed_mime_types))) {
                $rename = true;
            }
        }

        if ($rename) { 
            $file_name = $file_name.'_';
        } elseif ($ext == 'pdf' || in_array(strtolower($mime_type), $pdf_mime_types)) {
            // Rename PDF to avoid running embedded JavaScript.
            if ($uploaded_file && !$contents) {
                $contents = file_get_contents($uploaded_file->getRealPath() ?: $uploaded_file->getPathname());
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
        return hash_hmac('sha512', 'web_cron_hash', config('app.key'));
    }

    public static function getProtocol($url = '')
    {
        return mb_strtolower(parse_url($url ?: config('app.url'), PHP_URL_SCHEME) ?: 'http');
    }

    // Check if APP_URL contains https:// protocol.
    public static function isHttps($url = '')
    {
        if (\Helper::isInstaller()) {
            // In the Installer we determine HTTPS from URL.
            return self::isCurrentUrlHttps();
        } else {
            return self::getProtocol($url) == 'https';
        }
    }

    public static function isInstaller()
    {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $request_uri = preg_replace("#\?.*#", '', $request_uri);

        return strstr($request_uri, '/install/') || preg_match("#/install$#", $request_uri);
    }

    public static function isCurrentUrlHttps()
    {
        if (in_array(strtolower($_SERVER['X_FORWARDED_PROTO'] ?? ''), array('https', 'on', 'ssl', '1'), true)
            || strtolower($_SERVER['HTTPS'] ?? '') == 'on' 
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') == 'https'
            || ($_SERVER['HTTP_CF_VISITOR'] ?? '') == '{"scheme":"https"}'
        ) {
            return true;
        } else {
            return false;
        }
    }

    public static function fixProtocol($url)
    {
        if (self::getProtocol() == 'http' && parse_url($url, PHP_URL_SCHEME) != 'http') {
            return str_replace('https://', 'http://', $url);
        }

        if (self::getProtocol() == 'https' && parse_url($url, PHP_URL_SCHEME) != 'https') {
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
        $required_extensions = \Config::get('installer.requirements.php');

        // Optional.
        $required_extensions[] = 'intl';

        foreach ($required_extensions as $extension_name) {
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

        // Required in console.
        if (self::isConsole() || !function_exists('shell_exec')) {
            $pcntl_enabled = extension_loaded('pcntl');
        } else {
            $pcntl_enabled = preg_match("/enable/m", \Helper::shellExec("php -i | grep pcntl") ?? '');
        }
        $php_extensions['pcntl (console PHP)'] = $pcntl_enabled;

        return $php_extensions;
    }

    public static function checkRequiredFunctions()
    {
        return [
            'shell_exec (PHP)' => function_exists('shell_exec'),
            'proc_open (PHP)'  => function_exists('proc_open'),
            'fpassthru (PHP)'  => function_exists('fpassthru'),
            'symlink (PHP)'    => function_exists('symlink'),
            'iconv (PHP)'      => function_exists('iconv'),
            'dns_get_record (PHP)'  => function_exists('dns_get_record'),
            'inet_pton (PHP)'  => function_exists('inet_pton'),
            // If posix_isatty() function is not enabled on the server the question in the
            // console command makes it wait infinitely and be aborted.
            // Commands should avoid using interctive functions or use special flags.
            //'posix_isatty (PHP)'  => function_exists('posix_isatty'),
            'pcntl_signal (console PHP)'    => function_exists('shell_exec') ? (int)\Helper::shellExec('php -r "echo (int)function_exists(\'pcntl_signal\');"') : false,
            'ps (shell)' => function_exists('shell_exec') ? \Helper::shellExec('ps') : false,
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

    public static function isCron()
    {
        if (!self::isConsole()) {
            return false;
        }
        if (php_sapi_name() == 'cli') {   
            if (isset($_SERVER['TERM'])) {   
                return false;
            } else {   
                return true;
            }   
        } else { 
            // The script was run from a webserver, or something else.
            return false;
        }
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
                'text'      => __('There is a problem processing outgoing mail queue — an admin should check :%a_begin%System Status:%a_end% and :%a_begin_recommendations%Recommendations:%a_end%', ['%a_begin%' => '<a href="'.route('system').'#cron" target="_blank">', '%a_end%' => '</a>', /*'%a_begin_logs%' => '<a href="'.route('logs', ['name' => 'send_errors']).'#cron" target="_blank">',*/ '%a_begin_recommendations%' => '<a href="'.config('app.freescout_repo').'/wiki/Background-Jobs" target="_blank">']),
                'unescaped' => true,
            ];
        }

        return $flashes;
    }

    public static function mbUcfirst($string, $encoding = 'UTF-8')
    {
        $first_char = mb_substr($string, 0, 1, $encoding);
        $then = mb_substr($string, 1, null, $encoding);
        return mb_strtoupper($first_char, $encoding) . $then;
    }

    /**
     * This is needed to allow using regexes for large texts.
     */
    public static function setPcreBacktrackLimit()
    {
        if ((int)ini_get('pcre.backtrack_limit') <= 1000000) {
            ini_set('pcre.backtrack_limit', 1000000000);
        }
        if ((int)ini_get('pcre.recursion_limit') <= 1000000) {
            ini_set('pcre.recursion_limit', 1000000000);
        }
    }

    /**
     * Get client IP address.
     */
    public static function getClientIp()
    {
        // Fix for CloudFlare: https://laracasts.com/discuss/channels/laravel/cloudflare-and-user-ip
        // But if CloudFlare is not used any value can be set to "Cf-Connecting-Ip" header.
        // if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        //     $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        // }
        return request()->ip();
    }

    public static function getTimeFormat()
    {
        $user = auth()->user();

        if ($user) {
            return $user->time_format;
        } else {
            return Option::get('time_format', User::TIME_FORMAT_24);
        }
    }

    public static function isTimeFormat24()
    {
        return self::getTimeFormat() == User::TIME_FORMAT_24;
    }

    /**
     * Runs artisan command and returns it's output.
     */
    public static function runCommand($command, $options = [])
    {
        $output_buffer = new BufferedOutput();
        \Artisan::call($command, $options, $output_buffer);
        
        return $output_buffer->fetch();
    }

    public static function setCurlDefaultOptions($ch)
    {
        // Curl has default CURLOPT_CONNECTTIMEOUT=30 seconds.
        curl_setopt($ch, CURLOPT_TIMEOUT, config('app.curl_timeout'));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, config('app.curl_connect_timeout'));
        curl_setopt($ch, CURLOPT_PROXY, config('app.proxy'));        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, config('app.curl_ssl_verifypeer'));
    }

    public static function setGuzzleDefaultOptions($params = [])
    {
        $default_params = [
            'timeout' => config('app.curl_timeout'),
            'connect_timeout' => config('app.curl_connect_timeout'),
            'proxy'  => config('app.proxy'),
            // https://docs.guzzlephp.org/en/6.5/request-options.html#verify
            'verify' => config('app.curl_ssl_verifypeer'),
        ];

        return array_merge($default_params, $params);
    }

    public static function cspNonce()
    {
        if (self::$csp_nonce === null) {
            self::$csp_nonce = \Str::random(25);
        }

        return self::$csp_nonce;
    }

    public static function cspMetaTag()
    {
        $csp = "<meta http-equiv=\"Content-Security-Policy\" content=\"".self::getCspValue()."\">";

        return $csp;
    }

    public static function getCspValue()
    {
        $nonce = \Helper::cspNonce();

        $script_src = config('app.csp_script_src').' '.\Eventy::filter('csp.script_src', '');

        $script_domains = '';
        $scripts = explode(' ', $script_src);

        foreach ($scripts as $url) {
            $url = trim($url);
            if (!preg_match("#^(http|//)#", $url)) {
                $url = '//'.$url;
            }
            $parts = parse_url($url);
            if (!empty($parts['host'])) {
                $domain = preg_replace("#['\"; \r\n]#", '', $parts['host']);
                $script_domains .= ' '.$domain;
            }
        }

        //  frame-src https://recaptcha.net; connect-src https://recaptcha.net;
        //  The frame-ancestors is ignored when delivered via a meta element.
        $csp = "base-uri 'none'; default-src 'self' ".self::sanitizeCsp($script_domains)."; img-src * 'self' data:; font-src * 'self' data:; style-src * 'self' 'unsafe-inline'; form-action 'self' ".self::sanitizeCsp(\Eventy::filter('csp.form_action', ''), true)."; frame-src * 'self'; script-src 'self' 'nonce-".$nonce."' "
            .self::sanitizeCsp($script_src).";"
            .self::sanitizeCsp(config('app.csp_custom').self::sanitizeCsp(\Eventy::filter('csp.custom', '')));

        return $csp;
    }

    // Strip 'unsafe-inline' to improve security.
    public static function sanitizeCsp($csp, $full_sanitizing = false)
    {
        $csp = str_ireplace("'unsafe-inline'", '', $csp);
        $csp = str_ireplace('unsafe-inline', '', $csp);
        $csp = preg_replace('#["<>]#', '', $csp);

        if ($full_sanitizing) {
            $csp = str_ireplace(';', '', $csp);
        }
        return $csp;
    }

    public static function cspNonceAttr()
    {
        // if (!config('app.csp_enabled')) {
        //     return '';
        // }

        return ' nonce="'.\Helper::cspNonce().'"';
    }

    public static function isChatModeAvailable()
    {
        return count(CustomerChannel::getChannels());
    }

    public static function isChatMode()
    {
        return (int)\Session::get('chat_mode', 0);
    }

    public static function setChatMode($is_on)
    {
        if ((int)$is_on) {
            \Session::put('chat_mode', 1);
        } else {
            \Session::forget('chat_mode');
        }
    }

    public static function detectCloudFlare()
    {
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])
            || !empty($_SERVER['HTTP_CF_CONNECTING_IP'])
            || !empty($_SERVER['HTTP_CF_VISITOR'])
            || !empty($_SERVER['HTTP_CF_RAY'])
            || ($_SERVER['HTTP_CDN_LOOP'] ?? '') == 'cloudflare'
        ) {
            return true;
        } else {
            return false;
        }
    }

    // Correct format: 2023-12-14 19:21
    // Datepicker with enableTime option enabled
    // may return value in different format on iOS Safari: 2023-12-14T11:25
    public static function sanitizeDatepickerDatetime($datetime)
    {
        return str_replace('T', ' ', $datetime);
    }

    // To catch possible exception: shell_exec(): Unable to execute
    // Return string or false.
    public static function shellExec($command)
    {
        try {
            return shell_exec($command) ?? '';
        } catch (\Throwable $e) {
            self::logException($e, '\Helper::shellExec() - ');
            return false;
        }
    }

    public static function startsiWith($text, $string)
    {
        return (stripos($text, $string) === 0);
    }

    // The iconv_mime_decode() may throw an error even with ICONV_MIME_DECODE_CONTINUE_ON_ERROR.
    // https://github.com/freescout-help-desk/freescout/issues/5265
    public static function iconvMimeDecode($string, $mode = ICONV_MIME_DECODE_CONTINUE_ON_ERROR, $encoding = "UTF-8")
    {
        try {
            return iconv_mime_decode($string, $mode, $encoding);
        } catch (\Exception $e) {
            self::logException($e);
            return $string;
        }
    }

    public static function stripUnprintableChars($string)
    {
        // Remove unprintable characters and invalid unicode characters.
        // https://github.com/freescout-help-desk/freescout/issues/4681
        $string = preg_replace("#\p{C}+#u", '', $string);
        // https://github.com/freescout-help-desk/freescout/issues/2123#issuecomment-2775392740
        $string = preg_replace("#\p{Cf}+#u", '', $string);

        return $string;
    }

    public static function stripUnsafeChars($string)
    {
        // Remove Unicode control characters and null bytes
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        return $string;
    }

    public static function stripUnprintableAndUnsafeChars($string)
    {
        $string = self::stripUnprintableChars($string);
        $string = self::stripUnsafeChars($string);

        return $string;
    }

    public static function filterArrayByKeys($list, $allowed_keys)
    {
        foreach ($list as $key => $value) {
            if (!in_array($key, $allowed_keys)) {
                unset($list[$key]);
            }
        }

        return $list;
    }

    /**
     * Check if the visitor's browser supports CSP (Content Security Policy).
     */
    public static function isCspSupported($ua = '')
    {
        if (!$ua) {
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }

        // Normalize
        $ua = strtolower($ua);

        // Internet Explorer (all versions) → NO proper CSP support
        if (strpos($ua, 'msie') !== false || strpos($ua, 'trident/') !== false) {
            return false;
        }

        // Chrome (CSP supported from version 25+ reliably)
        if (preg_match('/chrome\/(\d+)/', $ua, $matches)) {
            return (int)$matches[1] >= 25;
        }

        // Firefox (CSP supported from version 23+)
        if (preg_match('/firefox\/(\d+)/', $ua, $matches)) {
            return (int)$matches[1] >= 23;
        }

        // Safari (CSP supported from version 7+)
        if (preg_match('/version\/(\d+).+safari/', $ua, $matches)) {
            return (int)$matches[1] >= 7;
        }

        // Edge (EdgeHTML and Chromium-based both support CSP)
        if (preg_match('/edge\/(\d+)/', $ua, $matches) || preg_match('/edg\/(\d+)/', $ua, $matches)) {
            return (int)$matches[1] >= 12;
        }

        // Default to true for modern browsers.
        return true;
    }

    public static function checkBrowser($request = null)
    {
        $result = [
            'status' => 'success',
            'msg' => '',
        ];

        if (!$request) {
            $request = request();
        }

        $user_agent = $request->server('HTTP_USER_AGENT') ?? '';

        $allowed_user_agents = explode('|', strtolower(config('app.allowed_user_agents') ?? ''));

        if (in_array(strtolower($user_agent), $allowed_user_agents)) {
            return $result;
        }

        // Make sure that browser supports CSP (Content Security Policy).
        if (!\Helper::isCspSupported($user_agent)) {
            $result['status'] = 'error';
            //$result['msg'] = 'On '.$request->fullUrl().' page it was detected that your browser does not support Content Security Policy (CSP). Please use a modern browser or send to FreeScout Team ('.config('app.freescout_url').'/contact-us/) your browser info: '.$user_agent;
            $result['msg'] = 'On '.$request->fullUrl().' page it was detected that your browser does not support Content Security Policy (CSP): '.$user_agent.'. Please use a modern browser or contact administrator to disable the browser check.';
        }

        return $result;
    }

    public static function hmacHash($data, $key = null)
    {
        if ($key === null) {
            $key = config('app.key');
        }
        return hash_hmac('sha512', $data, $key);
    }

    /**
     * Sanitize email address.
     */
    public static function sanitizeEmail($email)
    {
        return Email::sanitizeEmail($email);
    }

    public static function createCarbonDateFromFormat($string, $format = 'Y-m-d H:i:s')
    {
        if (empty($string)) {
            return null;
        }
        try {
            return Carbon::createFromFormat($format, $string);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function carbonDateParse($string)
    {
        if (empty($string)) {
            return null;
        }
        try {
            return Carbon::parse($string);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function isCarbon($date)
    {
        if (!is_object($date)) {
            return false;
        }
        return get_class($date) == 'Carbon\Carbon';
    }

    public static function hashEquals($known_string, $string_to_check)
    {
        return hash_equals((string)$known_string, (string)$string_to_check);
    }

    public static function curlClose($ch)
    {
        try {
            if (PHP_VERSION_ID >= 80000) {
                unset($ch);
            } else {
                curl_close($ch);
            }
        } catch (\Exception $e) {
            // Do nothing.
        }
    }
}
