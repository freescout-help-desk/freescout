<?php

namespace App;

use App\Email;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Watson\Rememberable\Rememberable;

class Customer extends Model
{
    use Rememberable;
    // This is obligatory.
    public $rememberCacheDriver = 'array';

    const PHOTO_DIRECTORY = 'customers';
    const PHOTO_SIZE = 64; // px
    const PHOTO_QUALITY = 77;

    /**
     * Genders.
     */
    const GENDER_UNKNOWN = 1;
    const GENDER_MALE = 2;
    const GENDER_FEMALE = 3;

    /**
     * For API.
     */
    public static $genders = [
        self::GENDER_UNKNOWN => 'unknown',
        self::GENDER_MALE    => 'male',
        self::GENDER_FEMALE  => 'female',
    ];

    /**
     * Phone types.
     */
    const PHONE_TYPE_WORK = 1;
    const PHONE_TYPE_HOME = 2;
    const PHONE_TYPE_OTHER = 3;
    const PHONE_TYPE_MOBILE = 4;
    const PHONE_TYPE_FAX = 5;
    const PHONE_TYPE_PAGER = 6;

    /**
     * For API.
     */
    public static $phone_types = [
        self::PHONE_TYPE_WORK   => 'work',
        self::PHONE_TYPE_HOME   => 'home',
        self::PHONE_TYPE_MOBILE => 'mobile',
        self::PHONE_TYPE_FAX    => 'fax',
        self::PHONE_TYPE_PAGER  => 'pager',
        self::PHONE_TYPE_OTHER  => 'other',
    ];

    /**
     * Photo types.
     */
    const PHOTO_TYPE_UKNOWN = 1;
    const PHOTO_TYPE_GRAVATAR = 2;
    const PHOTO_TYPE_TWITTER = 3;
    const PHOTO_TYPE_FACEBOOK = 4;
    const PHOTO_TYPE_GOOGLEPROFILE = 5;
    const PHOTO_TYPE_GOOGLEPLUS = 6;
    const PHOTO_TYPE_LINKEDIN = 7;
    const PHOTO_TYPE_VK = 8; // Extra

    /**
     * For API.
     */
    public static $photo_types = [
        self::PHOTO_TYPE_UKNOWN        => 'unknown',
        self::PHOTO_TYPE_GRAVATAR      => 'gravatar',
        self::PHOTO_TYPE_TWITTER       => 'twitter',
        self::PHOTO_TYPE_FACEBOOK      => 'facebook',
        self::PHOTO_TYPE_GOOGLEPROFILE => 'googleprofile',
        self::PHOTO_TYPE_GOOGLEPLUS    => 'googleplus',
        self::PHOTO_TYPE_LINKEDIN      => 'linkedin',
        self::PHOTO_TYPE_VK            => 'vk', // Extra
    ];

    /**
     * Chat types.
     */
    // const CHAT_TYPE_AIM = 1;
    // const CHAT_TYPE_GTALK = 2;
    // const CHAT_TYPE_ICQ = 3;
    // const CHAT_TYPE_XMPP = 4;
    // const CHAT_TYPE_MSN = 5;
    // const CHAT_TYPE_SKYPE = 6;
    // const CHAT_TYPE_YAHOO = 7;
    // const CHAT_TYPE_QQ = 8;
    // const CHAT_TYPE_WECHAT = 10;
    // const CHAT_TYPE_OTHER = 9;

    /**
     * For API.
     */
    // public static $chat_types = [
    //     self::CHAT_TYPE_AIM    => 'aim',
    //     self::CHAT_TYPE_GTALK  => 'gtalk',
    //     self::CHAT_TYPE_ICQ    => 'icq',
    //     self::CHAT_TYPE_XMPP   => 'xmpp',
    //     self::CHAT_TYPE_MSN    => 'msn',
    //     self::CHAT_TYPE_SKYPE  => 'skype',
    //     self::CHAT_TYPE_YAHOO  => 'yahoo',
    //     self::CHAT_TYPE_QQ     => 'qq',
    //     self::CHAT_TYPE_WECHAT => 'wechat', // Extra
    //     self::CHAT_TYPE_OTHER  => 'other',
    // ];

    // public static $chat_type_names = [
    //     self::CHAT_TYPE_AIM    => 'AIM',
    //     self::CHAT_TYPE_GTALK  => 'Google+',
    //     self::CHAT_TYPE_ICQ    => 'ICQ',
    //     self::CHAT_TYPE_XMPP   => 'XMPP',
    //     self::CHAT_TYPE_MSN    => 'MSN',
    //     self::CHAT_TYPE_SKYPE  => 'Skype',
    //     self::CHAT_TYPE_YAHOO  => 'Yahoo',
    //     self::CHAT_TYPE_QQ     => 'QQ',
    //     self::CHAT_TYPE_WECHAT => 'WeChat', // Extra
    //     self::CHAT_TYPE_OTHER  => 'Other',
    // ];

    /**
     * Social types.
     */
    const SOCIAL_TYPE_TWITTER = 1;
    const SOCIAL_TYPE_FACEBOOK = 2;
    const SOCIAL_TYPE_TELEGRAM = 14;
    const SOCIAL_TYPE_LINKEDIN = 3;
    const SOCIAL_TYPE_ABOUTME = 4;
    const SOCIAL_TYPE_GOOGLE = 5;
    const SOCIAL_TYPE_GOOGLEPLUS = 6;
    const SOCIAL_TYPE_TUNGLEME = 7;
    const SOCIAL_TYPE_QUORA = 8;
    const SOCIAL_TYPE_FOURSQUARE = 9;
    const SOCIAL_TYPE_YOUTUBE = 10;
    const SOCIAL_TYPE_FLICKR = 11;
    const SOCIAL_TYPE_VK = 13; // Extra
    const SOCIAL_TYPE_OTHER = 12;

    public static $social_types = [
        self::SOCIAL_TYPE_TWITTER    => 'twitter',
        self::SOCIAL_TYPE_FACEBOOK   => 'facebook',
        self::SOCIAL_TYPE_TELEGRAM   => 'telegram',
        self::SOCIAL_TYPE_LINKEDIN   => 'linkedin',
        self::SOCIAL_TYPE_ABOUTME    => 'aboutme',
        self::SOCIAL_TYPE_GOOGLE     => 'google',
        self::SOCIAL_TYPE_GOOGLEPLUS => 'googleplus',
        self::SOCIAL_TYPE_TUNGLEME   => 'tungleme',
        self::SOCIAL_TYPE_QUORA      => 'quora',
        self::SOCIAL_TYPE_FOURSQUARE => 'foursquare',
        self::SOCIAL_TYPE_YOUTUBE    => 'youtube',
        self::SOCIAL_TYPE_FLICKR     => 'flickr',
        self::SOCIAL_TYPE_VK         => 'vk', // Extra
        self::SOCIAL_TYPE_OTHER      => 'other',
    ];

    public static $social_type_names = [
        self::SOCIAL_TYPE_TWITTER    => 'Twitter',
        self::SOCIAL_TYPE_FACEBOOK   => 'Facebook',
        self::SOCIAL_TYPE_TELEGRAM   => 'Telegram',
        self::SOCIAL_TYPE_LINKEDIN   => 'Linkedin',
        self::SOCIAL_TYPE_ABOUTME    => 'About.me',
        self::SOCIAL_TYPE_GOOGLE     => 'Google',
        self::SOCIAL_TYPE_GOOGLEPLUS => 'Google+',
        self::SOCIAL_TYPE_TUNGLEME   => 'Tungle.me',
        self::SOCIAL_TYPE_QUORA      => 'Quora',
        self::SOCIAL_TYPE_FOURSQUARE => 'Foursquare',
        self::SOCIAL_TYPE_YOUTUBE    => 'YouTube',
        self::SOCIAL_TYPE_FLICKR     => 'Flickr',
        self::SOCIAL_TYPE_VK         => 'VK',
        self::SOCIAL_TYPE_OTHER      => 'Other',
    ];

    /**
     * Search filters.
     */
    public static $search_filters = [
        'mailbox',
    ];

    /**
     * Countries list.
     */
    public static $countries = [
        'US' => 'United States',
        'AU' => 'Australia',
        'CA' => 'Canada',
        'DK' => 'Denmark',
        'FR' => 'France',
        'DE' => 'Germany',
        'IT' => 'Italy',
        'JP' => 'Japan',
        'MX' => 'Mexico',
        'ES' => 'Spain',
        'SE' => 'Sweden',
        'GB' => 'United Kingdom',
        'AF' => 'Afghanistan',
        'AX' => 'Åland Islands',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua and Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BQ' => 'Bonaire',
        'BA' => 'Bosnia and Herzegowina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'BN' => 'Brunei Darussalam',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CD' => 'Congo, DR',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => "Cote D'Ivoire",
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CW' => 'Curacao',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands (Malvinas)',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GG' => 'Guernsey',
        'GN' => 'Guinea',
        'GW' => 'Guinea-bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard and Mc Donald Islands',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran (Islamic Republic of)',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IM' => 'Isle of Man',
        'IL' => 'Israel',
        'JM' => 'Jamaica',
        'JE' => 'Jersey',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KP' => "Korea, Democratic People's Republic of",
        'KR' => 'Korea, Republic of',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => "Lao People's Democratic Republic",
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macao',
        'MK' => 'Macedonia, The Former Yugoslav Republic of',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'FM' => 'Micronesia, Federated States of',
        'MD' => 'Moldova, Republic of',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        '00' => 'None Available',
        'NF' => 'Norfolk Island',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PS' => 'Palestine, State of',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'RW' => 'Rwanda',
        'BL' => 'Saint Barthelemy',
        'KN' => 'Saint Kitts and Nevis',
        'LC' => 'Saint Lucia',
        'MF' => 'Saint Martin (French part)',
        'VC' => 'Saint Vincent and the Grenadines',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome and Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SX' => 'Sint Maarten (Dutch part)',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia and the South Sandwich Islands',
        'SS' => 'South Sudan',
        'LK' => 'Sri Lanka',
        'SH' => 'St. Helena',
        'PM' => 'St. Pierre and Miquelon',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard and Jan Mayen',
        'SZ' => 'Swaziland',
        'CH' => 'Switzerland',
        'SY' => 'Syrian Arab Republic',
        'TW' => 'Taiwan, Province of China',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania, United Republic of',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad and Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks and Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'UM' => 'United States Minor Outlying Islands',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VA' => 'Vatican City State (Holy See)',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'VG' => 'Virgin Islands (British)',
        'VI' => 'Virgin Islands (U.S.)',
        'WF' => 'Wallis and Futuna Islands',
        'EH' => 'Western Sahara',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Attributes which are not fillable using fill() method.
     */
    protected $guarded = ['id'];

    /**
     * Attributes fillable using fill() method.
     *
     * @var [type]
     */
    protected $fillable = ['first_name', 'last_name', 'company', 'job_title', 'address', 'city', 'state', 'zip', 'country', 'photo_url', 'age', 'gender', 'notes', 'channel', 'channel_id', 'social_profiles'];

    /**
     * Fields stored as JSON.
     */
    protected $json_fields = ['phones', 'websites', 'social_profiles'];

    /**
     * Get customer emails.
     */
    public function emails()
    {
        return $this->hasMany('App\Email');
    }

    /**
     * Get customer emails.
     */
    public function emails_cached()
    {
        return $this->hasMany('App\Email')->rememberForever();
    }

    /**
     * Get customer conversations.
     */
    public function conversations()
    {
        return $this->hasMany('App\Conversation');
    }

    /**
     * Get main email.
     */
    public function getMainEmail()
    {
        return optional($this->emails_cached()->first())->email.'';
    }

    /**
     * Get main email.
     */
    public static function getMainEmailStatic($customer_id)
    {
        return Email::select('email')->where('customer_id', $customer_id)->pluck('email');
    }

    /**
     * Get customer full name.
     *
     * @return string
     */
    public function getFullName($email_if_empty = false, $first_part_from_email = false)
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name.' '.$this->last_name;
        } elseif (!$this->last_name && $this->first_name) {
            return $this->first_name;
        } elseif (!$this->first_name && $this->last_name) {
            return $this->last_name;
        } elseif ($email_if_empty) {
            $email = $this->getMainEmail();
            if ($first_part_from_email) {
                return $this->getNameFromEmail($email);
            } else {
                return $email;
            }
        }

        return '';
    }

    /**
     * Get customer first name.
     *
     * @return string
     */
    public function getFirstName($email_if_empty = false)
    {
        if ($this->first_name) {
            return $this->first_name;
        } elseif ($email_if_empty) {
            return $this->getNameFromEmail();
        }

        return '';
    }

    /**
     * Get first part of the email.
     *
     * @return string
     */
    public function getNameFromEmail($email = '')
    {
        if (!$email) {
            $email = optional($this->emails_cached()->first())->email;
        }
        if ($email) {
            return explode('@', $email)[0];
        } else {
            return '';
        }
    }

    /**
     * Set customer emails.
     *
     * @param array $emails
     */
    public function syncEmails($emails)
    {
        if (is_array($emails)) {
            $deleted_emails = [];
            foreach ($this->emails as $email) {
                foreach ($emails as $email_address) {
                    if (Email::sanitizeEmail($email->email) == Email::sanitizeEmail($email_address)) {
                        continue 2;
                    }
                }
                $deleted_emails[] = $email;
            }
            foreach ($emails as $email_address) {
                $email_address = Email::sanitizeEmail($email_address);
                if (!$email_address) {
                    continue;
                }
                $email = Email::where('email', $email_address)->first();
                $new_emails = [];
                if ($email) {
                    // Assign email to current customer
                    if ($email->customer_id != $this->id) {
                        $email->customer()->associate($this);
                        $email->save();
                    }
                } else {
                    $new_emails[] = new Email(['email' => $email_address]);
                }
                if ($new_emails) {
                    $this->emails()->saveMany($new_emails);
                }
            }

            foreach ($deleted_emails as $email) {
                if (Conversation::where('customer_email', $email->email)->exists()) {
                    // Create customers for deleted emails
                    // if there is a conversation with 'customer_email'.
                    $customer = new self();
                    $customer->save();
                    $email->customer()->associate($customer);
                    $email->save();
                } else {
                    // Simply delete an email.
                    $email->delete();
                }
            }
        }
    }

    /**
     * Add new email to customer.
     */
    public function addEmail($email_address, $check_if_exists = false)
    {
        // Check if email already exists and belongs to another customer.
        if ($check_if_exists) {
            $email = Email::where('email', $email_address)->first();
            if ($email && !empty($email->customer_id)) {
                return false;
            }
        }
        $new_email = new Email(['email' => $email_address]);
        $this->emails()->save($new_email);
    }

    /**
     * Get customers phones as array.
     *
     * @return array
     */
    public function getPhones($dummy_if_empty = false)
    {
        $phones = json_decode($this->phones ?? '', true);

        if (is_array($phones) && count($phones)) {
            return $phones;
        } elseif ($dummy_if_empty) {
            return [[
                'type' => self::PHONE_TYPE_WORK,
                'value' => '',
            ]];
        } else {
            return [];
        }
    }

    public function getMainPhoneValue()
    {
        return $this->getMainPhoneNumber();
    }

    public function getMainPhoneNumber()
    {
        $phones = $this->getPhones();
        return $phones[0]['value'] ?? '';
    }

    /**
     * Set phones as JSON.
     *
     * @param array $phones_array
     */
    public function setPhones(array $phones_array)
    {
        $phones_array = self::formatPhones($phones_array);

        // Remove dubplicates.
        $list = [];
        foreach ($phones_array as $i => $data) {
            if (in_array($data['value'], $list)) {
                unset($phones_array[$i]);
            } else {
                $list[] = $data['value'];         
            }
        }

        $this->phones = \Helper::jsonEncodeUtf8($phones_array);
    }

    /**
     * Sanitize phones array.
     *
     * @param array $phones_array [description]
     *
     * @return array [description]
     */
    public static function formatPhones(array $phones_array)
    {
        $phones = [];

        foreach ($phones_array as $phone) {
            if (is_array($phone)) {
                if (!empty($phone['value'])) {
                    if (empty($phone['type']) || !in_array($phone['type'], array_keys(self::$phone_types))) {
                        $phone['type'] = self::PHONE_TYPE_WORK;
                    }
                    $phones[] = [
                        'value' => (string) $phone['value'],
                        'type'  => (int) $phone['type'],
                        'n'     => (string)\Helper::phoneToNumeric($phone['value']),
                    ];
                }
            } else {
                $phones[] = [
                    'value' => (string) $phone,
                    'type'  => self::PHONE_TYPE_WORK,
                    'n'     => (string)\Helper::phoneToNumeric($phone),
                ];
            }
        }

        return $phones;
    }

    /**
     * Add website.
     */
    public function addPhone($phone, $type = self::PHONE_TYPE_WORK)
    {
        if (is_string($phone)) {
            $this->setPhones(array_merge(
                $this->getPhones(),
                [['value' => $phone, 'type' => $type]]
            ));
        } else {
            $this->setPhones(array_merge(
                $this->getPhones(),
                [$phone]
            ));
        }
    }

    /**
     * Find customer by phone number.
     */
    public static function findByPhone($phone)
    {
        return Customer::byPhone($phone)->first();
    }

    /**
     * Get query.
     */
    public static function byPhone($phone)
    {
        $phone_numeric = \Helper::phoneToNumeric($phone);
        return Customer::where('phones', 'LIKE', '%"'.$phone_numeric.'"%');
    }

    /**
     * Get customers social profiles as array.
     *
     * @return array
     */
    public function getSocialProfiles($dummy_if_empty = false)
    {
        $social_profiles = json_decode($this->social_profiles ?? '', true);

        if (is_array($social_profiles) && count($social_profiles)) {
            return json_decode($this->social_profiles, true);
        } elseif ($dummy_if_empty) {
            return [[
                'type' => '',
                'value' => '',
            ]];
        } else {
            return [];
        }
    }

    /**
     * Get customers social profiles as array.
     *
     * @return array
     */
    public function getWebsites($dummy_if_empty = false)
    {
        $websites = json_decode($this->websites ?? '', true);
        if (is_array($websites) && count($websites)) {
            return $websites;
        } elseif ($dummy_if_empty) {
            return [''];
        } else {
            return [];
        }
    }

    public function getMainWebsite()
    {
        $websites = $this->getWebsites();
        return $websites[0] ?? '';
    }

    /**
     * Set websites as JSON.
     *
     * @param array $websites_array
     */
    public function setWebsites(array $websites_array)
    {
        $websites = [];
        foreach ($websites_array as $key => $value) {
            // FILTER_SANITIZE_URL cuts some symbols.
            //$value = filter_var((string) $value, FILTER_SANITIZE_URL);
            if (isset($value['value'])) {
                $value = $value['value'];
            }
            if (!$value || preg_match("/^http(s)?:?\/?\/?$/i", $value)) {
                continue;
            }
            if (!preg_match("/http(s)?:\/\//i", $value)) {
                $value = 'http://'.$value;
            }
            $websites[] = (string) $value;
        }
        $this->websites = \Helper::jsonEncodeUtf8(array_unique($websites));
    }

    /**
     * Add website.
     */
    public function addWebsite($website)
    {
        $websites = $this->getWebsites();
        if (isset($website['value'])) {
            $website = $website['value'];
        }
        array_push($websites, $website);
        $this->setWebsites($websites);
    }

    /**
     * Sanitize social profiles.
     *
     * @param array $list [description]
     *
     * @return array [description]
     */
    public static function formatSocialProfiles(array $list)
    {
        $social_profiles = [];
        foreach ($list as $social_profile) {
            if (is_array($social_profile)) {
                if (!empty($social_profile['value']) && !empty($social_profile['type'])) {

                    $type = null;

                    if (is_numeric($social_profile['type']) && in_array($social_profile['type'], array_keys(self::$social_types))) {
                        $type = (int)$social_profile['type'];
                    } else {
                        // Find type.
                        foreach (self::$social_types as $type_id => $type_name) {
                            if ($type_name == strtolower($social_profile['type'])) {
                                $type = $type_id;
                            }
                        }
                    }

                    if (!$type) {
                        continue;
                    }

                    $social_profiles[] = [
                        'value' => (string) $social_profile['value'],
                        'type'  => $type,
                    ];
                }
            } else {
                $social_profiles[] = [
                    'value' => (string) $social_profile,
                    'type'  => self::SOCIAL_TYPE_OTHER,
                ];
            }
        }

        return $social_profiles;
    }

    /**
     * Set social profiles as JSON.
     *
     * @param array $websites_array
     */
    public function setSocialProfiles(array $sp_array)
    {
        $sp_array = self::formatSocialProfiles($sp_array);

        // Remove dubplicates.
        $list = [];
        foreach ($sp_array as $i => $data) {
            if (in_array($data['value'], $list)) {
                unset($sp_array[$i]);
            } else {
                $list[] = $data['value'];
            }
        }

        $this->social_profiles = \Helper::jsonEncodeUtf8($sp_array);
    }

    /**
     * Create customer or get existing and fill empty fields.
     *
     * @param string $email
     * @param array  $data  [description]
     *
     * @return [type] [description]
     */
    public static function create($email, $data = [])
    {
        $new = false;

        $email = Email::sanitizeEmail($email);
        if (!$email) {
            return null;
        }
        $email_obj = Email::where('email', $email)->first();
        if ($email_obj) {
            $customer = $email_obj->customer;

            // In case somehow the email has no customer.
            if (!$customer) {
                // Customer will be saved and connected to the email later.
                $customer = new self();
            }

            // Update name if empty.
            /*if (empty($customer->first_name) && !empty($data['first_name'])) {
                $customer->first_name = $data['first_name'];
                if (empty($customer->last_name) && !empty($data['last_name'])) {
                    $customer->last_name = $data['last_name'];
                }
                $customer->save();
            }*/
        } else {
            $customer = new self();
            $email_obj = new Email();
            $email_obj->email = $email;

            $new = true;
        }

        // Set empty fields
        if ($customer->setData($data, false) || !$customer->id) {
            $customer->save();
        }

        if (empty($email_obj->id) || !$email_obj->customer_id || $email_obj->customer_id != $customer->id) {
            // Email may have been set in setData().
            $save_email = true;
            if (!empty($data['emails']) && is_array($data['emails'])) {
                foreach ($data['emails'] as $data_email) {
                    if (is_string($data_email) && $data_email == $email) {
                        $save_email = false;
                        break;
                    }
                    if (is_array($data_email) && !empty($data_email['value']) && $data_email['value'] == $email) {
                        $save_email = false;
                        break;
                    }
                }
            }
            if ($save_email) {
                $email_obj->customer()->associate($customer);
                $email_obj->save();
            }
        }

        // Todo: check phone uniqueness.

        if ($new) {
            \Eventy::action('customer.created', $customer);
        }

        return $customer;
    }

    /**
     * Set empty fields.
     */
    public function setData($data, $replace_data = true, $save = false)
    {
        $result = false;

        // todo: photoUrl.
        if (isset($data['photo_url'])) {
            unset($data['photo_url']);
        }

        if (!empty($data['background']) && empty($data['notes'])) {
            $data['notes'] = $data['background'];
        }

        if ($replace_data) {
            // Replace data.
            $data_prepared = $data;
            foreach ($data_prepared as $i => $value) {
                if (is_array($value)) {
                    unset($data_prepared[$i]);
                }
            }
            $this->fill($data_prepared);
            $result = true;
        } else {
            // Update empty fields.
            foreach ($data as $key => $value) {
                if (in_array($key, $this->fillable) && empty($this->$key)) {
                    $this->$key = $value;
                    $result = true;
                }
            }
        }

        // Set JSON values.
        if (!empty($data['phone'])) {
            $this->addPhone($data['phone']);
        }
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->json_fields) && $key != 'emails') {
                continue;
            }
            if ($key == 'phones') {
                if (isset($value['value'])) {
                    $this->addPhone($value);
                } else {
                    $this->setPhones($value);
                    // foreach ($value as $phone_value) {
                    //     $this->addPhone($phone_value);
                    // }
                }
                $result = true;
            }
            if ($key == 'websites') {
                if (is_array($value)) {
                    $this->setWebsites($value);
                    // foreach ($value as $website) {
                    //     $this->addWebsite($website);
                    // }
                } else {
                    $this->addWebsite($value);
                }
                $result = true;
            }
            if ($key == 'social_profiles') {
                $this->setSocialProfiles($value);
                $result = true;
            }
            if ($key == 'country') {
                if (array_search($this->country, Customer::$countries)) {
                    $this->country = array_search($this->country, Customer::$countries);
                }
                $this->country = strtoupper(mb_substr($this->country, 0, 2));
                $result = true;
            }
        }

        // Emails must be processed the last as they need to save object.
        foreach ($data as $key => $value) {
            if ($key == 'emails') {
                foreach ($value as $email_data) {
                    if (is_string($email_data)) {
                        if (!$this->id) {
                            $this->save();
                        }
                        $email_created = Email::create($email_data, $this->id, Email::TYPE_WORK);

                        if ($email_created) {
                            $result = true;
                        }
                    } elseif (!empty($email_data['value'])) {
                        if (!$this->id) {
                            $this->save();
                        }
                        $email_created = Email::create($email_data['value'], $this->id, $email_data['type']);

                        if ($email_created) {
                            $result = true;
                        }
                    }
                }
                break;
            }
        }
        // Maybe Todo: check phone uniqueness.
        // Same phone can be written in many ways, so it's almost useless to chek uniqueness.

        \Eventy::action('customer.set_data', $this, $data, $replace_data);

        if ($save) {
            $this->save();
        }

        return $result;
    }

    /**
     * Create a customer, email is not required.
     * For phone conversations.
     */
    public static function createWithoutEmail($data = [])
    {
        $customer = new self();
        $customer->setData($data);

        $customer->save();

        \Eventy::action('customer.created', $customer);

        return $customer;
    }

    /**
     * Get customer URL.
     *
     * @return string
     */
    public function url()
    {
        return route('customers.update', ['id'=>$this->id]);
    }

    /**
     * Get view customer URL.
     *
     * @return string
     */
    public function urlView()
    {
        return route('customers.conversations', ['id'=>$this->id]);
    }

    /**
     * Format date according to customer's timezone.
     *
     * @param Carbon $date
     * @param string $format
     *
     * @return string
     */
    public static function dateFormat($date, $format = 'M j, Y H:i')
    {
        return $date->format($format);
    }

    /**
     * Get full representation of customer.
     */
    public function getEmailAndName()
    {
        // Email can be fetched using query.
        $text = $this->email;
        if (!$text) {
            $text = $this->getMainEmail();
        }
        if ($this->getFullName()) {
            if ($text) {
                $text .= ' ('.$this->getFullName().')';
            } else {
                $text .= $this->getFullName();
            }
        }
        return $text;
    }

    public function getNameAndEmail()
    {
        // Email can be fetched using query.
        $text = $this->getFullName();
        $email = $this->email;
        if (!$email) {
            $email = $this->getMainEmail();
        }
        if ($email) {
            if ($text) {
                $text .= ' <'.$email.'>';
            } else {
                $text .= $email;
            }
        }
        return $text;
    }

    /**
     * Get customers info for the list of emails.
     */
    public static function emailsToCustomers($list)
    {
        $result = [];

        $data = Customer::select(['emails.email', 'customers.first_name', 'customers.last_name'])
            ->join('emails', function ($join) {
                $join->on('emails.customer_id', '=', 'customers.id');
            })
            ->whereIn('emails.email', $list)
            //->groupby('customers.id')
            ->get()
            ->toArray();

        foreach ($list as $email) {
            // Dummy customer.
            $customer = new Customer();
            $customer->email = $email;

            foreach ($data as $values) {
                if (strtolower($values['email']) == strtolower($email)) {
                    $customer->first_name = $values['first_name'];
                    $customer->last_name = $values['last_name'];
                    break;
                }
            }
            $result[$email] = $customer->getNameAndEmail();
        }

        return $result;
    }

    /**
     * Get customer by email.
     */
    public static function getByEmail($email)
    {
        return Customer::select('customers.*')
            ->where('emails.email', $email)
            ->join('emails', function ($join) {
                $join->on('emails.customer_id', '=', 'customers.id');
            })->first();
    }

    /**
     * Get email or phone if email is empty.
     */
    public function getEmailOrPhone()
    {
        if (!empty($this->email)) {
            // Email can be selected with query.
            return $this->email;
        } elseif ($main_email = $this->getMainEmail()) {
            return $main_email;
        } elseif ($phones = $this->getPhones() && !empty($phones[0]['value'])) {
            return $phones[0]['value'];
        }

        return '';
    }

    public function getPhotoUrl($default_if_empty = true)
    {
        if (!empty($this->photo_url) || !$default_if_empty) {
            if (!empty($this->photo_url)) {
                return self::getPhotoUrlByFileName($this->photo_url);
            } else {
                return '';
            }
        } else {
            return \Eventy::filter('customer.default_avatar', asset('/img/default-avatar.png'), $this);
        }
    }

    public static function getPhotoUrlByFileName($file_name)
    {
        return Storage::url(self::PHOTO_DIRECTORY.DIRECTORY_SEPARATOR.$file_name);
    }

    /**
     * Resize and save photo.
     */
    public function savePhoto($real_path, $mime_type)
    {
        $resized_image = \App\Misc\Helper::resizeImage($real_path, $mime_type, self::PHOTO_SIZE, self::PHOTO_SIZE);

        if (!$resized_image) {
            return false;
        }

        $file_name = md5(Hash::make($this->id)).'.jpg';
        $dest_path = Storage::path(self::PHOTO_DIRECTORY.DIRECTORY_SEPARATOR.$file_name);

        $dest_dir = pathinfo($dest_path, PATHINFO_DIRNAME);
        if (!file_exists($dest_dir)) {
            \File::makeDirectory($dest_dir, \Helper::DIR_PERMISSIONS);
        }

        // Remove current photo
        if ($this->photo_url) {
            Storage::delete(self::PHOTO_DIRECTORY.DIRECTORY_SEPARATOR.$this->photo_url);
        }

        imagejpeg($resized_image, $dest_path, self::PHOTO_QUALITY);

        return $file_name;
    }

    /**
     * Remove user photo.
     */
    public function removePhoto()
    {
        if ($this->photo_url) {
            Storage::delete(self::PHOTO_DIRECTORY.DIRECTORY_SEPARATOR.$this->photo_url);
        }
        $this->photo_url = '';
    }

    public function getCountryName()
    {
        if ($this->country && !empty(self::$countries[$this->country])) {
            return self::$countries[$this->country];
        } else {
            return '';
        }
    }

    /**
     * Get first and last name.
     */
    public static function parseName($name)
    {
        $data = [];

        if (!$name) {
            return $data;
        }

        $name_parts = explode(' ', $name, 2);
        $data['first_name'] = $name_parts[0];
        if (!empty($name_parts[1])) {
            $data['last_name'] = $name_parts[1];
        }

        return $data;
    }

    public static function formatSocialProfile($sp)
    {
        if (empty($sp['type']) || !isset(self::$social_type_names[$sp['type']])) {
            $sp['type'] = self::SOCIAL_TYPE_OTHER;
        }

        $sp['type_name'] = self::$social_type_names[$sp['type']];

        $sp['value_url'] = $sp['value'];

        if (!preg_match("/^https?:\/\//i", $sp['value_url'])) {
            switch ($sp['type']) {
                case self::SOCIAL_TYPE_TELEGRAM:
                    $sp['value_url'] = 'https://t.me/'.$sp['value'];
                    break;
                
                default:
                    $sp['value_url'] = 'http://'.$sp['value_url'];
                    break;
            }
        }
        if (empty($sp['value_url'])) {
            $sp['value_url'] = '';
        }

        return $sp;
    }

    public function setPhotoFromRemoteFile($url)
    {
        $headers = get_headers($url);

        if (!preg_match("/200/", $headers[0])) {
            return false;
        }

        $image_data = file_get_contents($url);

        if (!$image_data) {
            return false;
        }

        $temp_file = \Helper::getTempFileName();

        \File::put($temp_file, $image_data);

        $photo_url = $this->savePhoto($temp_file, \File::mimeType($temp_file));

        if ($photo_url) {
            $this->photo_url = $photo_url;
            return true;
        } else {
            return false;
        }
    }

    public function getChannelName()
    {
        return \Eventy::filter('channel.name', '', $this->channel);
    }

    /**
     * Get thread meta value.
     */
    public function getMeta($key, $default = null)
    {
        if (isset($this->meta[$key])) {
            return $this->meta[$key];
        } else {
            return $default;
        }
    }

    /**
     * Set thread meta value.
     */
    public function setMeta($key, $value)
    {
        $meta = $this->meta;
        $meta[$key] = $value;
        $this->meta = $meta;
    }

    public static function getPhoneTypeName($code)
    {
        $phone_types = [
            self::PHONE_TYPE_WORK   => __('Work'),
            self::PHONE_TYPE_HOME   => __('Home'),
            self::PHONE_TYPE_OTHER  => __('Other'),
            self::PHONE_TYPE_MOBILE => __('Mobile'),
            self::PHONE_TYPE_FAX    => __('Fax'),
            self::PHONE_TYPE_PAGER  => __('Pager'),
        ];

        return $phone_types[$code] ?? '';
    }

    public static function isDefaultPhoneType($code)
    {
        return (self::PHONE_TYPE_WORK == $code);
    }

    // Method does not check if the customer
    // has conversations.
    public function deleteCustomer()
    {
        // Delete emails.
        Email::where('customer_id', $this->id)->delete();
        $this->delete();
    }
}
