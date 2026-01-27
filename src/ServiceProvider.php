<?php

namespace NewSong\SongSearch;

use Statamic\Providers\AddonServiceProvider;
use Statamic\Facades\Permission;
use NewSong\SongSearch\Fieldtypes\SongSearch;
use NewSong\SongSearch\Support\Logger;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        SongSearch::class,
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $vite = [
        'input' => [
            'resources/js/addon.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function bootAddon()
    {
        // Register permissions
        $this->registerPermissions();

        // Register logging channel
        $this->registerLoggingChannel();

        // Validate production configuration
        $this->validateProductionConfig();

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/song-search.php' => config_path('song-search.php'),
        ], 'song-search-config');

        // Publish fieldsets
        $this->publishes([
            __DIR__.'/../resources/fieldsets' => resource_path('fieldsets/vendor/song-search'),
        ], 'song-search-fieldsets');

        // Merge config from package
        $this->mergeConfigFrom(
            __DIR__.'/../config/song-search.php',
            'song-search'
        );
    }

    /**
     * Register addon permissions.
     */
    protected function registerPermissions(): void
    {
        Permission::group('song-search', 'Song Search', function () {
            Permission::register('access song search')
                ->label('Access Song Search');
        });
    }

    /**
     * Register the dedicated logging channel.
     */
    protected function registerLoggingChannel(): void
    {
        if (!config('song-search.logging.enabled', true)) {
            return;
        }

        $this->app['config']->set('logging.channels.song-search', [
            'driver' => 'daily',
            'path' => storage_path('logs/song-search.log'),
            'level' => config('song-search.logging.level', 'info'),
            'days' => 14,
        ]);
    }

    /**
     * Validate configuration for production environments.
     */
    protected function validateProductionConfig(): void
    {
        if (!$this->app->environment('production')) {
            return;
        }

        $warnings = [];

        // Check Spotify API
        if (empty(config('song-search.spotify.client_id')) || empty(config('song-search.spotify.client_secret'))) {
            $warnings[] = 'Spotify API credentials (SPOTIFY_CLIENT_ID, SPOTIFY_CLIENT_SECRET) not set';
        }

        // Check Apple Music API
        $appleMusicConfigured = !empty(config('song-search.apple_music.team_id')) &&
            !empty(config('song-search.apple_music.key_id')) &&
            !empty(config('song-search.apple_music.private_key_path'));

        if (!$appleMusicConfigured) {
            $warnings[] = 'Apple Music API credentials not fully configured';
        }

        if (!empty($warnings)) {
            Logger::warning('API credentials not fully configured', [
                'missing' => $warnings,
                'recommendation' => 'Set missing environment variables for full song search functionality.',
            ]);
        }
    }
}
