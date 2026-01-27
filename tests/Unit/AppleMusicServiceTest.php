<?php

use NewSong\SongSearch\Services\AppleMusicService;

describe('isConfigured', function () {
    it('returns false when private key file does not exist', function () {
        config([
            'song-search.apple_music.team_id' => 'test-team',
            'song-search.apple_music.key_id' => 'test-key',
            'song-search.apple_music.private_key_path' => '/nonexistent/path/key.p8',
        ]);

        $service = new AppleMusicService();
        expect($service->isConfigured())->toBeFalse();
    });

    it('returns false when team_id is empty', function () {
        config([
            'song-search.apple_music.team_id' => '',
            'song-search.apple_music.key_id' => 'test-key',
            'song-search.apple_music.private_key_path' => __FILE__, // Use this file as a "key" for testing
        ]);

        $service = new AppleMusicService();
        expect($service->isConfigured())->toBeFalse();
    });

    it('returns false when key_id is empty', function () {
        config([
            'song-search.apple_music.team_id' => 'test-team',
            'song-search.apple_music.key_id' => '',
            'song-search.apple_music.private_key_path' => __FILE__,
        ]);

        $service = new AppleMusicService();
        expect($service->isConfigured())->toBeFalse();
    });

    it('returns false when private_key_path is null', function () {
        config([
            'song-search.apple_music.team_id' => 'test-team',
            'song-search.apple_music.key_id' => 'test-key',
            'song-search.apple_music.private_key_path' => null,
        ]);

        $service = new AppleMusicService();
        expect($service->isConfigured())->toBeFalse();
    });

    it('returns true when all credentials are set and file exists', function () {
        config([
            'song-search.apple_music.team_id' => 'test-team',
            'song-search.apple_music.key_id' => 'test-key',
            'song-search.apple_music.private_key_path' => __FILE__, // Use this file as stand-in
        ]);

        $service = new AppleMusicService();
        expect($service->isConfigured())->toBeTrue();
    });
});

describe('clearTokenCache', function () {
    it('clears the token cache without error', function () {
        $service = new AppleMusicService();

        // Should not throw
        $service->clearTokenCache();

        expect(true)->toBeTrue();
    });
});
