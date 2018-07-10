<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Email;

class Customer extends Model
{
	/**
	 * Genders
	 */
    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';
    const GENDER_UNKNOWN = 'unknown';

    /**
     * Phone types
     */
    const PHONE_TYPE_WORK = 'work';
    const PHONE_TYPE_HOME = 'home';
    const PHONE_TYPE_MOBILE = 'mobile';
    const PHONE_TYPE_FAX = 'fax';
    const PHONE_TYPE_PAGER = 'pager';
    const PHONE_TYPE_OTHER = 'other';

    public static $phone_types = [
    	self::PHONE_TYPE_WORK => 'Work',
    	self::PHONE_TYPE_HOME => 'Home',
    	self::PHONE_TYPE_OTHER => 'Other',
    	self::PHONE_TYPE_MOBILE => 'Mobile',
    	self::PHONE_TYPE_FAX => 'Fax',
    	self::PHONE_TYPE_PAGER => 'Pager',
    ];

    /**
     * Photo types
     */
    const PHOTO_TYPE_UKNOWN = 'unknown';
    const PHOTO_TYPE_GRAVATAR = 'gravatar';
    const PHOTO_TYPE_TWITTER = 'twitter';
    const PHOTO_TYPE_FACEBOOK = 'facebook';
    const PHOTO_TYPE_GOOGLEPROFILE = 'googleprofile';
    const PHOTO_TYPE_GOOGLEPLUS = 'googleplus';
    const PHOTO_TYPE_LINKEDIN = 'linkedin';
    const PHOTO_TYPE_VK = 'vk'; // Extra

    /**
     * Chat types
     */
    public static $chat_types = [
    	'aim' => 'AIM',
		'gtalk' => 'Google+',
		'icq' => 'ICQ',
		'xmpp' => 'XMPP',
		'msn' => 'MSN',
		'skype' => 'Skype',
		'yahoo' => 'Yahoo',
		'qq' => 'QQ',
		'wechat' => 'WeChat', // Extra
		'other' => 'Other',
    ];
   
    const SOCIAL_TYPE_TWITTER = 'twitter';
    const SOCIAL_TYPE_FACEBOOK = 'facebook';
    const SOCIAL_TYPE_LINKEDIN = 'linkedin';
    const SOCIAL_TYPE_ABOUTME = 'aboutme';
    const SOCIAL_TYPE_GOOGLE = 'google';
    const SOCIAL_TYPE_GOOGLEPLUS = 'googleplus';
    const SOCIAL_TYPE_TUNGLEME = 'tungleme';
    const SOCIAL_TYPE_QUORA = 'quora';
    const SOCIAL_TYPE_FOURSQUARE = 'foursquare';
    const SOCIAL_TYPE_YOUTUBE = 'youtube';
    const SOCIAL_TYPE_FLICKR = 'flickr';
    const SOCIAL_TYPE_VK = 'vk'; // Extra
    const SOCIAL_TYPE_OTHER = 'other';

    public static $social_types = [
        self::SOCIAL_TYPE_TWITTER => 'Twitter',
        self::SOCIAL_TYPE_FACEBOOK => 'Facebook',
        self::SOCIAL_TYPE_LINKEDIN => 'Linkedin',
        self::SOCIAL_TYPE_ABOUTME => 'About.me',
        self::SOCIAL_TYPE_GOOGLE => 'Google',
        self::SOCIAL_TYPE_GOOGLEPLUS => 'Google+',
        self::SOCIAL_TYPE_TUNGLEME => 'Tungle.me',
        self::SOCIAL_TYPE_QUORA => 'Quora',
        self::SOCIAL_TYPE_FOURSQUARE => 'Foursquare',
        self::SOCIAL_TYPE_YOUTUBE => 'YouTube',
        self::SOCIAL_TYPE_FLICKR => 'Flickr',
        self::SOCIAL_TYPE_VK => 'VK',
        self::SOCIAL_TYPE_OTHER => 'Other',
    ];

    /**
     * Countries list
     */
    public static $countries = [
        "US" => "United States",
        "AU" => "Australia",
        "CA" => "Canada",
        "DK" => "Denmark",
        "FR" => "France",
        "DE" => "Germany",
        "IT" => "Italy",
        "JP" => "Japan",
        "MX" => "Mexico",
        "ES" => "Spain",
        "SE" => "Sweden",
        "GB" => "United Kingdom",
        "AF" => "Afghanistan",
        "AX" => "Åland Islands",
        "AL" => "Albania",
        "DZ" => "Algeria",
        "AS" => "American Samoa",
        "AD" => "Andorra",
        "AO" => "Angola",
        "AI" => "Anguilla",
        "AQ" => "Antarctica",
        "AG" => "Antigua and Barbuda",
        "AR" => "Argentina",
        "AM" => "Armenia",
        "AW" => "Aruba",
        "AT" => "Austria",
        "AZ" => "Azerbaijan",
        "BS" => "Bahamas",
        "BH" => "Bahrain",
        "BD" => "Bangladesh",
        "BB" => "Barbados",
        "BY" => "Belarus",
        "BE" => "Belgium",
        "BZ" => "Belize",
        "BJ" => "Benin",
        "BM" => "Bermuda",
        "BT" => "Bhutan",
        "BO" => "Bolivia",
        "BQ" => "Bonaire",
        "BA" => "Bosnia and Herzegowina",
        "BW" => "Botswana",
        "BV" => "Bouvet Island",
        "BR" => "Brazil",
        "IO" => "British Indian Ocean Territory",
        "BN" => "Brunei Darussalam",
        "BG" => "Bulgaria",
        "BF" => "Burkina Faso",
        "BI" => "Burundi",
        "KH" => "Cambodia",
        "CM" => "Cameroon",
        "CV" => "Cape Verde",
        "KY" => "Cayman Islands",
        "CF" => "Central African Republic",
        "TD" => "Chad",
        "CL" => "Chile",
        "CN" => "China",
        "CX" => "Christmas Island",
        "CC" => "Cocos (Keeling) Islands",
        "CO" => "Colombia",
        "KM" => "Comoros",
        "CG" => "Congo",
        "CD" => "Congo, DR",
        "CK" => "Cook Islands",
        "CR" => "Costa Rica",
        "CI" => "Cote D'Ivoire",
        "HR" => "Croatia",
        "CU" => "Cuba",
        "CW" => "Curacao",
        "CY" => "Cyprus",
        "CZ" => "Czech Republic",
        "DJ" => "Djibouti",
        "DM" => "Dominica",
        "DO" => "Dominican Republic",
        "EC" => "Ecuador",
        "EG" => "Egypt",
        "SV" => "El Salvador",
        "GQ" => "Equatorial Guinea",
        "ER" => "Eritrea",
        "EE" => "Estonia",
        "ET" => "Ethiopia",
        "FK" => "Falkland Islands (Malvinas)",
        "FO" => "Faroe Islands",
        "FJ" => "Fiji",
        "FI" => "Finland",
        "GF" => "French Guiana",
        "PF" => "French Polynesia",
        "TF" => "French Southern Territories",
        "GA" => "Gabon",
        "GM" => "Gambia",
        "GE" => "Georgia",
        "GH" => "Ghana",
        "GI" => "Gibraltar",
        "GR" => "Greece",
        "GL" => "Greenland",
        "GD" => "Grenada",
        "GP" => "Guadeloupe",
        "GU" => "Guam",
        "GT" => "Guatemala",
        "GG" => "Guernsey",
        "GN" => "Guinea",
        "GW" => "Guinea-bissau",
        "GY" => "Guyana",
        "HT" => "Haiti",
        "HM" => "Heard and Mc Donald Islands",
        "HN" => "Honduras",
        "HK" => "Hong Kong",
        "HU" => "Hungary",
        "IS" => "Iceland",
        "IN" => "India",
        "ID" => "Indonesia",
        "IR" => "Iran (Islamic Republic of)",
        "IQ" => "Iraq",
        "IE" => "Ireland",
        "IM" => "Isle of Man",
        "IL" => "Israel",
        "JM" => "Jamaica",
        "JE" => "Jersey",
        "JO" => "Jordan",
        "KZ" => "Kazakhstan",
        "KE" => "Kenya",
        "KI" => "Kiribati",
        "KP" => "Korea, Democratic People's Republic of",
        "KR" => "Korea, Republic of",
        "KW" => "Kuwait",
        "KG" => "Kyrgyzstan",
        "LA" => "Lao People's Democratic Republic",
        "LV" => "Latvia",
        "LB" => "Lebanon",
        "LS" => "Lesotho",
        "LR" => "Liberia",
        "LY" => "Libya",
        "LI" => "Liechtenstein",
        "LT" => "Lithuania",
        "LU" => "Luxembourg",
        "MO" => "Macao",
        "MK" => "Macedonia, The Former Yugoslav Republic of",
        "MG" => "Madagascar",
        "MW" => "Malawi",
        "MY" => "Malaysia",
        "MV" => "Maldives",
        "ML" => "Mali",
        "MT" => "Malta",
        "MH" => "Marshall Islands",
        "MQ" => "Martinique",
        "MR" => "Mauritania",
        "MU" => "Mauritius",
        "YT" => "Mayotte",
        "FM" => "Micronesia, Federated States of",
        "MD" => "Moldova, Republic of",
        "MC" => "Monaco",
        "MN" => "Mongolia",
        "ME" => "Montenegro",
        "MS" => "Montserrat",
        "MA" => "Morocco",
        "MZ" => "Mozambique",
        "MM" => "Myanmar",
        "NA" => "Namibia",
        "NR" => "Nauru",
        "NP" => "Nepal",
        "NL" => "Netherlands",
        "NC" => "New Caledonia",
        "NZ" => "New Zealand",
        "NI" => "Nicaragua",
        "NE" => "Niger",
        "NG" => "Nigeria",
        "NU" => "Niue",
        "00" => "None Available",
        "NF" => "Norfolk Island",
        "MP" => "Northern Mariana Islands",
        "NO" => "Norway",
        "OM" => "Oman",
        "PK" => "Pakistan",
        "PW" => "Palau",
        "PS" => "Palestine, State of",
        "PA" => "Panama",
        "PG" => "Papua New Guinea",
        "PY" => "Paraguay",
        "PE" => "Peru",
        "PH" => "Philippines",
        "PN" => "Pitcairn",
        "PL" => "Poland",
        "PT" => "Portugal",
        "PR" => "Puerto Rico",
        "QA" => "Qatar",
        "RE" => "Reunion",
        "RO" => "Romania",
        "RU" => "Russia",
        "RW" => "Rwanda",
        "BL" => "Saint Barthelemy",
        "KN" => "Saint Kitts and Nevis",
        "LC" => "Saint Lucia",
        "MF" => "Saint Martin (French part)",
        "VC" => "Saint Vincent and the Grenadines",
        "WS" => "Samoa",
        "SM" => "San Marino",
        "ST" => "Sao Tome and Principe",
        "SA" => "Saudi Arabia",
        "SN" => "Senegal",
        "RS" => "Serbia",
        "SC" => "Seychelles",
        "SL" => "Sierra Leone",
        "SG" => "Singapore",
        "SX" => "Sint Maarten (Dutch part)",
        "SK" => "Slovakia",
        "SI" => "Slovenia",
        "SB" => "Solomon Islands",
        "SO" => "Somalia",
        "ZA" => "South Africa",
        "GS" => "South Georgia and the South Sandwich Islands",
        "SS" => "South Sudan",
        "LK" => "Sri Lanka",
        "SH" => "St. Helena",
        "PM" => "St. Pierre and Miquelon",
        "SD" => "Sudan",
        "SR" => "Suriname",
        "SJ" => "Svalbard and Jan Mayen",
        "SZ" => "Swaziland",
        "CH" => "Switzerland",
        "SY" => "Syrian Arab Republic",
        "TW" => "Taiwan, Province of China",
        "TJ" => "Tajikistan",
        "TZ" => "Tanzania, United Republic of",
        "TH" => "Thailand",
        "TL" => "Timor-Leste",
        "TG" => "Togo",
        "TK" => "Tokelau",
        "TO" => "Tonga",
        "TT" => "Trinidad and Tobago",
        "TN" => "Tunisia",
        "TR" => "Turkey",
        "TM" => "Turkmenistan",
        "TC" => "Turks and Caicos Islands",
        "TV" => "Tuvalu",
        "UG" => "Uganda",
        "UA" => "Ukraine",
        "AE" => "United Arab Emirates",
        "UM" => "United States Minor Outlying Islands",
        "UY" => "Uruguay",
        "UZ" => "Uzbekistan",
        "VU" => "Vanuatu",
        "VA" => "Vatican City State (Holy See)",
        "VE" => "Venezuela",
        "VN" => "Vietnam",
        "VG" => "Virgin Islands (British)",
        "VI" => "Virgin Islands (U.S.)",
        "WF" => "Wallis and Futuna Islands",
        "EH" => "Western Sahara",
        "YE" => "Yemen",
        "ZM" => "Zambia",
        "ZW" => "Zimbabwe",
    ];

    /**
     * Attributes which are not fillable using fill() method
     */
    protected $guarded = ['id'];

    /**
     * Attributes fillable using fill() method
     * @var [type]
     */
    protected $fillable  = ['first_name', 'last_name', 'company', 'job_title', 'address', 'city', 'state', 'zip', 'country'];

    /**
     * Get customer emails
     */
    public function emails()
    {
        return $this->hasMany('App\Email');
    }

    /**
     * Prepare data for saving as JSON
     * 
     * @param  array $data  
     * @param  string $field 
     * @return string        JSON string
     */
    public static function formatJsonField(array $data, $field = '')
    {
        return json_encode($data);
    }

    /**
     * Get customer full name.
     * 
     * @return string
     */
    public function getFullName()
    {
        if ($this->first_name || $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        } elseif ($this->emails) {
            return explode('@', $this->emails[0]->email)[0];
        } else {
            return $this->id;
        }
    } 

    /**
     * Set customer emails
     * @param  array $emails
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
                $customer = new Customer();
                $customer->save();
                $email->customer()->associate($customer);
                $email->save();
            }
        }
    }
}
