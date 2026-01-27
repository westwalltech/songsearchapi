<?php

use NewSong\SongSearch\Services\SpotifyService;

describe('isConfigured', function () {
    it('returns true when client_id and client_secret are set', function () {
        config([
            'song-search.spotify.client_id' => 'test-id',
            'song-search.spotify.client_secret' => 'test-secret',
        ]);

        $service = new SpotifyService();
        expect($service->isConfigured())->toBeTrue();
    });

    it('returns false when client_id is empty', function () {
        config([
            'song-search.spotify.client_id' => '',
            'song-search.spotify.client_secret' => 'test-secret',
        ]);

        $service = new SpotifyService();
        expect($service->isConfigured())->toBeFalse();
    });

    it('returns false when client_secret is empty', function () {
        config([
            'song-search.spotify.client_id' => 'test-id',
            'song-search.spotify.client_secret' => '',
        ]);

        $service = new SpotifyService();
        expect($service->isConfigured())->toBeFalse();
    });

    it('returns false when both are null', function () {
        config([
            'song-search.spotify.client_id' => null,
            'song-search.spotify.client_secret' => null,
        ]);

        $service = new SpotifyService();
        expect($service->isConfigured())->toBeFalse();
    });
});
