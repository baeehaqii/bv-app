<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'scrapecreators' => [
        'api_key' => env('SCRAPECREATORS_API_KEY'),
    ],

    'google' => [
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
        'share_with_email' => env('GOOGLE_SHARE_WITH_EMAIL'),

        // Service account untuk membaca Google Spreadsheet privat (Migrasi Data).
        // Berkas JSON-nya rahasia; storage/app/google sudah masuk .gitignore.
        'credentials' => env('GOOGLE_SERVICE_ACCOUNT', storage_path('app/google/service-account.json')),

        // Domain-Wide Delegation: service account menyamar jadi user Workspace ini
        // supaya bisa membaca SEMUA sheet miliknya tanpa share satu per satu.
        // Kosongkan bila sheet-nya cukup di-share manual ke client_email SA.
        'impersonate' => env('GOOGLE_IMPERSONATE_EMAIL'),
    ],

    'webhook' => [
        'n8n' => env('N8N_WEBHOOK_URL', ''),
    ],

    'notification' => [
        'wa_group_main'    => env('NOTIFY_WA_GROUP_MAIN', ''),
        'influencer_phones' => array_filter(explode(',', env('NOTIFY_INFLUENCER_PHONES', ''))),
        'social_media_phones' => array_filter(explode(',', env('NOTIFY_SOCIAL_MEDIA_PHONES', ''))),
        'influencer_emails' => array_filter(explode(',', env('NOTIFY_INFLUENCER_EMAILS', ''))),
        'social_media_emails' => array_filter(explode(',', env('NOTIFY_SOCIAL_MEDIA_EMAILS', ''))),
    ],

];
