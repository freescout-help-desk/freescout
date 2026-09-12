<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'        => 'Bu sahə qəbul edilməlidir.',
    'active_url'      => 'Bu düzgün URL deyil.',
    'after'           => 'Bu, :date tarixindən sonra olan tarix olmalıdır.',
    'after_or_equal'  => 'Bu, :date tarixinə bərabər və ya ondan sonra olan tarix olmalıdır.',
    'alpha'           => 'Bu sahə yalnız hərflərdən ibarət ola bilər.',
    'alpha_dash'      => 'Bu sahə yalnız hərflərdən, rəqəmlərdən, tirelərdən və alt xətlərdən ibarət ola bilər.',
    'alpha_num'       => 'Bu sahə yalnız hərflərdən və rəqəmlərdən ibarət ola bilər.',
    'array'           => 'Bu sahə massiv olmalıdır.',
    'before'          => 'Bu, :date tarixindən əvvəl olan tarix olmalıdır.',
    'before_or_equal' => 'Bu, :date tarixinə bərabər və ya ondan əvvəl olan tarix olmalıdır.',
    'between'         => [
        'numeric' => 'Bu dəyər :min ilə :max arasında olmalıdır.',
        'file'    => 'Bu fayl :min ilə :max kilobayt arasında olmalıdır.',
        'string'  => 'Bu mətn :min ilə :max simvol arasında olmalıdır.',
        'array'   => 'Bu məzmunda :min ilə :max arasında element olmalıdır.',
    ],
    'boolean'        => 'Bu sahə doğru və ya yanlış olmalıdır.',
    'confirmed'      => 'Təsdiq uyğun gəlmir.',
    'date'           => 'Bu düzgün tarix deyil.',
    'date_equals'    => 'Bu, :date tarixinə bərabər olan tarix olmalıdır.',
    'date_format'    => 'Bu, :format formatına uyğun gəlmir.',
    'different'      => 'Bu dəyər :other-dən fərqli olmalıdır.',
    'digits'         => 'Bu, :digits rəqəmdən ibarət olmalıdır.',
    'digits_between' => 'Bu, :min ilə :max rəqəm arasında olmalıdır.',
    'dimensions'     => 'Bu şəklin ölçüləri yanlışdır.',
    'distinct'       => 'Bu sahədə təkrarlanan dəyər var.',
    'email'          => 'Bu düzgün e-poçt ünvanı olmalıdır.',
    'ends_with'      => 'Bu, aşağıdakılardan biri ilə bitməlidir: :values.',
    'exists'         => 'Seçilmiş dəyər yanlışdır.',
    'file'           => 'Məzmun fayl olmalıdır.',
    'filled'         => 'Bu sahənin dəyəri olmalıdır.',
    'gt'             => [
        'numeric' => 'Bu dəyər :value-dən böyük olmalıdır.',
        'file'    => 'Bu fayl :value kilobaytdan böyük olmalıdır.',
        'string'  => 'Bu mətn :value simvoldan böyük olmalıdır.',
        'array'   => 'Bu məzmunda :value-dən çox element olmalıdır.',
    ],
    'gte' => [
        'numeric' => 'Bu dəyər :value-ə bərabər və ya ondan böyük olmalıdır.',
        'file'    => 'Bu fayl :value kilobayta bərabər və ya ondan böyük olmalıdır.',
        'string'  => 'Bu mətn :value simvola bərabər və ya ondan böyük olmalıdır.',
        'array'   => 'Bu məzmunda ən azı :value element olmalıdır.',
    ],
    'image'    => 'Bu şəkil olmalıdır.',
    'in'       => 'Seçilmiş dəyər yanlışdır.',
    'in_array' => 'Bu dəyər :other-də mövcud deyil.',
    'integer'  => 'Bu tam ədəd olmalıdır.',
    'ip'       => 'Bu düzgün IP ünvanı olmalıdır.',
    'ipv4'     => 'Bu düzgün IPv4 ünvanı olmalıdır.',
    'ipv6'     => 'Bu düzgün IPv6 ünvanı olmalıdır.',
    'json'     => 'Bu düzgün JSON mətni olmalıdır.',
    'lt'       => [
        'numeric' => 'Bu dəyər :value-dən kiçik olmalıdır.',
        'file'    => 'Bu fayl :value kilobaytdan kiçik olmalıdır.',
        'string'  => 'Bu mətn :value simvoldan kiçik olmalıdır.',
        'array'   => 'Bu məzmunda :value-dən az element olmalıdır.',
    ],
    'lte' => [
        'numeric' => 'Bu dəyər :value-ə bərabər və ya ondan kiçik olmalıdır.',
        'file'    => 'Bu fayl :value kilobayta bərabər və ya ondan kiçik olmalıdır.',
        'string'  => 'Bu mətn :value simvola bərabər və ya ondan kiçik olmalıdır.',
        'array'   => 'Bu məzmunda :value-dən çox element ola bilməz.',
    ],
    'max' => [
        'numeric' => 'Bu dəyər :max-dan böyük ola bilməz.',
        'file'    => 'Bu fayl :max kilobaytdan böyük ola bilməz.',
        'string'  => 'Bu mətn :max simvoldan böyük ola bilməz.',
        'array'   => 'Bu məzmunda :max-dan çox element ola bilməz.',
    ],
    'mimes'     => 'Bu, aşağıdakı növlərdən birinə aid fayl olmalıdır: :values.',
    'mimetypes' => 'Bu, aşağıdakı növlərdən birinə aid fayl olmalıdır: :values.',
    'min'       => [
        'numeric' => 'Bu dəyər ən azı :min olmalıdır.',
        'file'    => 'Bu fayl ən azı :min kilobayt olmalıdır.',
        'string'  => 'Bu mətn ən azı :min simvol olmalıdır.',
        'array'   => 'Bu dəyərdə ən azı :min element olmalıdır.',
    ],
    'not_in'               => 'Seçilmiş dəyər yanlışdır.',
    'not_regex'            => 'Bu format yanlışdır.',
    'numeric'              => 'Bu ədəd olmalıdır.',
    'password'             => 'Parol yanlışdır.',
    'present'              => 'Bu sahə mövcud olmalıdır.',
    'regex'                => 'Bu format yanlışdır.',
    'required'             => 'Bu sahə tələb olunur.',
    'required_if'          => 'Bu sahə :other dəyəri :value olduqda tələb olunur.',
    'required_unless'      => 'Bu sahə :other aşağıdakılardan biri olmadıqda tələb olunur: :values.',
    'required_with'        => 'Bu sahə :values mövcud olduqda tələb olunur.',
    'required_with_all'    => 'Bu sahə :values mövcud olduqda tələb olunur.',
    'required_without'     => 'Bu sahə :values mövcud olmadıqda tələb olunur.',
    'required_without_all' => 'Bu sahə :values-dən heç biri mövcud olmadıqda tələb olunur.',
    'same'                 => 'Bu sahənin dəyəri :other sahəsinin dəyəri ilə uyğun olmalıdır.',
    'size'                 => [
        'numeric' => 'Bu dəyər :size olmalıdır.',
        'file'    => 'Bu fayl :size kilobayt olmalıdır.',
        'string'  => 'Bu mətn :size simvol olmalıdır.',
        'array'   => 'Bu məzmun :size element daşımalıdır.',
    ],
    'starts_with' => 'Bu, aşağıdakılardan biri ilə başlamalıdır: :values.',
    'string'      => 'Bu mətn olmalıdır.',
    'timezone'    => 'Bu düzgün zaman qurşağı olmalıdır.',
    'unique'      => 'Bu artıq istifadə olunub.',
    'uploaded'    => 'Bu yüklənə bilmədi.',
    'url'         => 'Bu format yanlışdır.',
    'uuid'        => 'Bu düzgün UUID olmalıdır.',

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

];
