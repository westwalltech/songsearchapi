<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Spotify API Configuration
    |--------------------------------------------------------------------------
    |
    | Get credentials from: https://developer.spotify.com/dashboard
    | Create an app and copy your Client ID and Client Secret.
    |
    */
    'spotify' => [
        'client_id' => env('SPOTIFY_CLIENT_ID'),
        'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Apple Music API Configuration
    |--------------------------------------------------------------------------
    |
    | Apple Music requires:
    | - Team ID: From your Apple Developer Account
    | - Key ID: From the MusicKit key you created
    | - Private Key: Path to your .p8 file
    |
    | Get these from: https://developer.apple.com/account/resources/authkeys/list
    | Create a MusicKit key and download the .p8 file.
    |
    */
    'apple_music' => [
        'team_id' => env('APPLE_MUSIC_TEAM_ID'),
        'key_id' => env('APPLE_MUSIC_KEY_ID'),
        'private_key_path' => env('APPLE_MUSIC_PRIVATE_KEY_PATH', storage_path('app/apple_music_key.p8')),
        // JWT tokens are valid for up to 6 months, cache for 5 months
        'token_cache_seconds' => 60 * 60 * 24 * 150, // 150 days
        // Storefront for search (country code)
        'storefront' => env('APPLE_MUSIC_STOREFRONT', 'us'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Artwork Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where album artwork should be stored.
    |
    */
    'artwork' => [
        'container' => 'assets',
        'folder' => 'albumartwork',
        'preferred_size' => 640, // Preferred image size in pixels
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    */
    'search' => [
        'max_results' => 20,
        'timeout' => 10, // API request timeout in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Field Mapping
    |--------------------------------------------------------------------------
    |
    | Default field handles that the song search fieldtype will populate.
    | These can be overridden in the fieldtype configuration.
    |
    */
    'field_mapping' => [
        'title' => 'title',
        'artist' => 'subtitle',
        'apple_music_url' => 'apple_music_url',
        'spotify_url' => 'spotify_url',
        'artwork_asset' => 'featured_media',
    ],
];
