<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Superadmin Emails (Allowlist)
    |--------------------------------------------------------------------------
    |
    | Daftar email yang dianggap "superadmin" (dikontrol lewat kode).
    | Anda dapat mengatur daftar ini lewat file config atau menggunakan
    | variabel lingkungan `SUPERADMIN_EMAILS` (CSV) untuk kemudahan deploy.
    |
    */
    'emails' => (function () {
        $default = [
            'gilangaryatama12@gmail.com',
            'nurmasani@amikom.ac.id',
            'fajri@amikom.ac.id',
            'rofni@amikom.ac.id',
        ];

        $env = env('SUPERADMIN_EMAILS', null);

        if ($env !== null && trim((string) $env) !== '') {
            $items = array_map(function ($v) {
                return strtolower(trim((string) $v));
            }, explode(',', $env));

            $items = array_values(array_filter($items, fn($v) => $v !== ''));

            if (!empty($items)) {
                return $items;
            }
        }

        return $default;
    })(),
];
