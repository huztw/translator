<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Translator Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration value allows you to customize the storage options
    | for Translator, such as the database connection that should be used
    | by Translator's internal database models which store translations, etc.
    |
     */

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
        ],
    ],
];
