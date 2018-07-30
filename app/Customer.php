<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Email;

class Customer extends Model
{
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
        self::PHONE_TYPE_OTHER  => 'other',
        self::PHONE_TYPE_MOBILE => 'mobile',
        self::PHONE_TYPE_FAX    => 'Fax',
        self::PHONE_TYPE_PAGER  => 'pager',
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
    const CHAT_TYPE_AIM = 1;
    const CHAT_TYPE_GTALK = 2;
    const CHAT_TYPE_ICQ = 3;
    const CHAT_TYPE_XMPP = 4;
    const CHAT_TYPE_MSN = 5;
    const CHAT_TYPE_SKYPE = 6;
    const CHAT_TYPE_YAHOO = 7;
    const CHAT_TYPE_QQ = 8;
    const CHAT_TYPE_WECHAT = 10;
    const CHAT_TYPE_OTHER = 9;

    /**
     * For API.
     */
    public static $chat_types = [
        self::CHAT_TYPE_AIM    => 'aim',
        self::CHAT_TYPE_GTALK  => 'gtalk',
        self::CHAT_TYPE_ICQ    => 'icq',
        self::CHAT_TYPE_XMPP   => 'xmpp',
        self::CHAT_TYPE_MSN    => 'msn',
        self::CHAT_TYPE_SKYPE  => 'skype',
        self::CHAT_TYPE_YAHOO  => 'yahoo',
        self::CHAT_TYPE_QQ     => 'qq',
        self::CHAT_TYPE_WECHAT => 'wechat', // Extra
        self::CHAT_TYPE_OTHER  => 'other',
    ];

    public static $chat_type_names = [
        self::CHAT_TYPE_AIM    => 'AIM',
        self::CHAT_TYPE_GTALK  => 'Google+',
        self::CHAT_TYPE_ICQ    => 'ICQ',
        self::CHAT_TYPE_XMPP   => 'XMPP',
        self::CHAT_TYPE_MSN    => 'MSN',
        self::CHAT_TYPE_SKYPE  => 'Skype',
        self::CHAT_TYPE_YAHOO  => 'Yahoo',
        self::CHAT_TYPE_QQ     => 'QQ',
        self::CHAT_TYPE_WECHAT => 'WeChat', // Extra
        self::CHAT_TYPE_OTHER  => 'Other',
    ];

    /**
     * Social types.
     */
    const SOCIAL_TYPE_TWITTER = 1;
    const SOCIAL_TYPE_FACEBOOK = 2;
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

    /**
     * Attributes which are not fillable using fill() method.
     */
    protected $guarded = ['id'];

    /**
     * Attributes fillable using fill() method.
     *
     * @var [type]
     */
    protected $fillable = ['first_name', 'last_name', 'company', 'job_title', 'address', 'city', 'state', 'zip', 'country'];

    /**
     * Get customer emails.
     */
    public function emails()
    {
        return $this->hasMany('App\Email');
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
        return $this->emails()->first()->email;
    }

    /**
     * Get customer full name.
     *
     * @return string
     */
    public function getFullName($email_if_empty = false)
    {
        if ($this->first_name || $this->last_name) {
            return $this->first_name.' '.$this->last_name;
        } elseif ($email_if_empty) {
            if (count($this->emails)) {
                return explode('@', $this->emails[0]->email)[0];
            }
        }
        return '';
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
                    if (Email::sanatizeEmail($email->email) == Email::sanatizeEmail($email_address)) {
                        continue 2;
                    }
                }
                $deleted_emails[] = $email;
            }
            foreach ($emails as $email_address) {
                $email_address = Email::sanatizeEmail($email_address);
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
            // Create customers for deleted emails
            foreach ($deleted_emails as $email) {
                $customer = new self();
                $customer->save();
                $email->customer()->associate($customer);
                $email->save();
            }
        }
    }

    /**
     * Get customers phones as array.
     *
     * @return array
     */
    public function getPhones()
    {
        if ($this->phones) {
            return json_decode($this->phones);
        } else {
            return [];
        }
    }

    /**
     * Set phones as JSON.
     *
     * @param array $phones_array
     */
    public function setPhones(array $phones_array)
    {
        $this->phones = json_encode(self::formatPhones($phones));
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
            if (!empty($phone['value']) && !empty($phone['type']) && in_array($phone['type'], array_keys(self::$phone_types))) {
                $phones[] = [
                    'value' => (string) $phone['value'],
                    'type'  => (int) $phone['type'],
                ];
            }
        }

        return json_encode($phones);
    }

    /**
     * Get customers social profiles as array.
     *
     * @return array
     */
    public function getSocialProfiles()
    {
        if ($this->social_profiles) {
            return json_decode($this->social_profiles);
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
        if ($this->websites) {
            return json_decode($this->websites);
        } elseif ($dummy_if_empty) {
            return [''];
        } else {
            return [];
        }
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
            $value = filter_var((string) $value, FILTER_SANITIZE_URL);
            if (!preg_match("/http(s)?:\/\//i", $value)) {
                $value = 'http://'.$value;
            }
            $websites[] = (string) $value;
        }
        $this->websites = json_encode($websites);
    }

    /**
     * Create customer or get existing.
     * 
     * @param  string $email
     * @param  array  $data  [description]
     * @return [type]        [description]
     */
    public static function create($email, $data = [])
    {
        $email = Email::sanitizeEmail($email);
        if (!$email) {
            return null;
        }
        $email_obj = Email::where('email', $email)->first();
        if ($email_obj) {
            $customer = $email_obj->customer;
        } else {
            $customer = new Customer();
            $email_obj = new Email();
            $email_obj->email = $email;
        }
        $customer->fill($data);
        $customer->save();

        if (empty($email_obj->id) || !$email_obj->customer_id) {
            $email_obj->customer()->associate($customer);
            $email_obj->save();
        }

        return $customer;
    }
}
