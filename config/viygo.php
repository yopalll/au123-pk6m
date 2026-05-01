<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Support / contact email
    |--------------------------------------------------------------------------
    | Surfaced on the Help Centre and Contact Us static pages.
    | Override in .env via VIYGO_SUPPORT_EMAIL=…
    */
    'support_email' => env('VIYGO_SUPPORT_EMAIL', 'support@viygo.com'),
    'help_email'    => env('VIYGO_HELP_EMAIL', 'help@viygo.com'),

    /*
    |--------------------------------------------------------------------------
    | Social media URLs
    |--------------------------------------------------------------------------
    | Used by the public footer.
    */
    'social' => [
        'instagram' => env('VIYGO_INSTAGRAM_URL', 'https://instagram.com/viygo'),
        'facebook'  => env('VIYGO_FACEBOOK_URL',  'https://facebook.com/viygo'),
        'tiktok'    => env('VIYGO_TIKTOK_URL',    'https://tiktok.com/@viygo'),
    ],

];
