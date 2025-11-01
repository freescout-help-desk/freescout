<?php

return [
  'accepted'        => 'יש לאשר את השדה :attribute.',
  'active_url'      => 'השדה :attribute אינו כתובת URL חוקית.',
  'after'           => 'השדה :attribute חייב להיות תאריך לאחר :date.',
  'after_or_equal'  => 'השדה :attribute חייב להיות תאריך לאחר או שווה ל-:date.',
  'alpha'           => 'השדה :attribute יכול להכיל אותיות בלבד.',
  'alpha_dash'      => 'השדה :attribute יכול להכיל רק אותיות, מספרים, מקפים וקווים תחתונים.',
  'alpha_num'       => 'השדה :attribute יכול להכיל אותיות ומספרים בלבד.',
  'array'           => 'השדה :attribute חייב להיות מערך.',
  'before'          => 'השדה :attribute חייב להיות תאריך לפני :date.',
  'before_or_equal' => 'השדה :attribute חייב להיות תאריך לפני או שווה ל-:date.',
  'between'         => [
    'numeric' => 'השדה :attribute חייב להיות בין :min ל-:max.',
    'file'    => 'השדה :attribute חייב להיות בין :min ל-:max קילובייט.',
    'string'  => 'השדה :attribute חייב להיות בין :min ל-:max תווים.',
    'array'   => 'השדה :attribute חייב להכיל בין :min ל-:max פריטים.',
  ],
  'boolean'        => 'השדה :attribute חייב להיות אמת או שקר.',
  'confirmed'      => 'אימות :attribute אינו תואם.',
  'date'           => 'השדה :attribute אינו תאריך חוקי.',
  'date_format'    => 'השדה :attribute אינו תואם את הפורמט :format.',
  'different'      => 'השדה :attribute ו-:other חייבים להיות שונים.',
  'digits'         => 'השדה :attribute חייב להיות באורך :digits ספרות.',
  'digits_between' => 'השדה :attribute חייב להיות באורך של בין :min ל-:max ספרות.',
  'dimensions'     => 'לשדה :attribute יש מימדי תמונה לא חוקיים.',
  'distinct'       => 'לשדה :attribute יש ערך כפול.',
  'email'          => 'השדה :attribute חייב להיות כתובת אימייל חוקית.',
  'exists'         => 'ה-:attribute שנבחר אינו חוקי.',
  'file'           => 'השדה :attribute חייב להיות קובץ.',
  'filled'         => 'שדה :attribute הוא שדה חובה.',
  'image'          => 'השדה :attribute חייב להיות תמונה.',
  'in'             => 'ה-:attribute שנבחר אינו חוקי.',
  'in_array'       => 'השדה :attribute לא קיים ב-:other.',
  'integer'        => 'השדה :attribute חייב להיות מספר שלם.',
  'ip'             => 'השדה :attribute חייב להיות כתובת IP חוקית.',
  'ipv4'           => 'השדה :attribute חייב להיות כתובת IPv4 חוקית.',
  'ipv6'           => 'השדה :attribute חייב להיות כתובת IPv6 חוקית.',
  'json'           => 'השדה :attribute חייב להיות מחרוזת JSON חוקית.',
  'max'            => [
    'numeric' => 'השדה :attribute לא יכול להיות גדול מ-:max.',
    'file'    => 'השדה :attribute לא יכול להיות גדול מ-:max קילובייט.',
    'string'  => 'השדה :attribute לא יכול להיות ארוך מ-:max תווים.',
    'array'   => 'השדה :attribute לא יכול להכיל יותר מ-:max פריטים.',
  ],
  'mimes'     => 'השדה :attribute חייב להיות קובץ מסוג: :values.',
  'mimetypes' => 'השדה :attribute חייב להיות קובץ מסוג: :values.',
  'min'       => [
    'numeric' => 'השדה :attribute חייב להיות לפחות :min.',
    'file'    => 'השדה :attribute חייב להיות לפחות :min קילובייט.',
    'string'  => 'השדה :attribute חייב להיות באורך של :min תווים לפחות.',
    'array'   => 'השדה :attribute חייב להכיל לפחות :min פריטים.',
  ],
  'not_in'               => 'ה-:attribute שנבחר אינו חוקי.',
  'numeric'              => 'השדה :attribute חייב להיות מספר.',
  'present'              => 'השדה :attribute חייב להיות נוכח.',
  'regex'                => 'הפורמט של השדה :attribute אינו חוקי.',
  'required'             => 'שדה :attribute הוא שדה חובה.',
  'required_if'          => 'שדה :attribute הוא חובה כאשר :other הוא :value.',
  'required_unless'      => 'שדה :attribute הוא חובה אלא אם :other נמצא ב-:values.',
  'required_with'        => 'שדה :attribute הוא חובה כאשר :values קיים.',
  'required_with_all'    => 'שדה :attribute הוא חובה כאשר :values קיימים.',
  'required_without'     => 'שדה :attribute הוא חובה כאשר :values לא קיים.',
  'required_without_all' => 'שדה :attribute הוא חובה כאשר אף אחד מ-:values לא קיימים.',
  'same'                 => 'השדה :attribute ו-:other חייבים להיות זהים.',
  'size'                 => [
    'numeric' => 'השדה :attribute חייב להיות :size.',
    'file'    => 'השדה :attribute חייב להיות :size קילובייט.',
    'string'  => 'השדה :attribute חייב להיות באורך :size תווים.',
    'array'   => 'השדה :attribute חייב להכיל :size פריטים.',
  ],
  'string'   => 'השדה :attribute חייב להיות מחרוזת.',
  'timezone' => 'השדה :attribute חייב להיות אזור זמן חוקי.',
  'unique'   => 'ה-:attribute כבר תפוס.',
  'uploaded' => 'העלאת :attribute נכשלה.',
  'url'      => 'הפורמט של :attribute אינו חוקי.',

  /*
  |--------------------------------------------------------------------------
  | Custom Validation Language Lines
  |--------------------------------------------------------------------------
  |
  | Here you may specify custom validation messages for attributes using the
  | convention "attribute.rule" to name the lines. This makes it quick to
  | specify a specific custom language line for a given attribute rule.
  |
  */

  'custom' => [
    'attribute-name' => [
      'rule-name' => 'custom-message',
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Custom Validation Attributes
  |--------------------------------------------------------------------------
  |
  | The following language lines are used to swap attribute place-holders
  | with something more reader friendly such as E-Mail Address instead
  | of "email". This simply helps us make messages a little cleaner.
  |
  */

  'attributes' => [],

];