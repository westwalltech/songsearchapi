<?php

namespace NewSong\SongSearch\Tests;

use NewSong\SongSearch\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up default config values for testing
        config([
            'song-search.spotify.client_id' => 'test-client-id',
            'song-search.spotify.client_secret' => 'test-client-secret',
            'song-search.apple_music.team_id' => 'test-team-id',
            'song-search.apple_music.key_id' => 'test-key-id',
            'song-search.apple_music.private_key_path' => null,
            'song-search.search.timeout' => 10,
            'song-search.search.max_results' => 20,
        ]);
    }
}
