<?php

namespace NewSong\SongSearch;

use Statamic\Providers\AddonServiceProvider;
use NewSong\SongSearch\Fieldtypes\SongSearch;

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
}
