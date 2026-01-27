<?php

namespace NewSong\SongSearch\Services;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AppleMusicService
{
    protected Client $client;
    protected ?string $teamId;
    protected ?string $keyId;
    protected ?string $privateKeyPath;
    protected string $baseUrl = 'https://api.music.apple.com/v1/';

    public function __construct()
    {
        $this->teamId = config('song-search.apple_music.team_id');
        $this->keyId = config('song-search.apple_music.key_id');

        // Resolve the private key path - handle both absolute and relative paths
        $configPath = config('song-search.apple_music.private_key_path');
        if ($configPath && ! str_starts_with($configPath, '/')) {
            // Relative path - resolve from base path
            $this->privateKeyPath = base_path($configPath);
        } else {
            $this->privateKeyPath = $configPath;
        }

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => config('song-search.search.timeout', 10),
        ]);
    }

    /**
     * Check if Apple Music API is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->teamId)
            && ! empty($this->keyId)
            && ! empty($this->privateKeyPath)
            && file_exists($this->privateKeyPath);
    }

    /**
     * Generate or retrieve cached Apple Music Developer JWT token
     */
    protected function getToken(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cacheSeconds = config('song-search.apple_music.token_cache_seconds', 60 * 60 * 24 * 150);

        return Cache::remember('apple_music_developer_token', $cacheSeconds, function () {
            return $this->generateJWT();
        });
    }

    /**
     * Generate Apple Music Developer JWT
     *
     * Uses ES256 algorithm with Apple's private key (.p8 file)
     */
    protected function generateJWT(): ?string
    {
        try {
            if (! file_exists($this->privateKeyPath)) {
                Log::error('Apple Music private key file not found: '.$this->privateKeyPath);

                return null;
            }

            $privateKey = file_get_contents($this->privateKeyPath);

            if ($privateKey === false) {
                Log::error('Apple Music: Unable to read private key file');

                return null;
            }

            $now = time();
            // Token valid for 180 days (max is 6 months)
            $exp = $now + (60 * 60 * 24 * 180);

            $payload = [
                'iss' => $this->teamId,
                'iat' => $now,
                'exp' => $exp,
            ];

            // JWT::encode requires: payload, key, algorithm, keyId
            return JWT::encode($payload, $privateKey, 'ES256', $this->keyId);
        } catch (\Exception $e) {
            Log::error('Apple Music JWT Generation Error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Search for songs by query
     *
     * @param  string  $query  Search query (song title, artist, etc.)
     * @param  string|null  $storefront  Country code (default from config or 'us')
     * @return array Normalized song results
     */
    public function searchSongs(string $query, ?string $storefront = null): array
    {
        $token = $this->getToken();
        if (! $token) {
            Log::warning('Apple Music: Unable to get developer token');

            return [];
        }

        $storefront = $storefront ?? config('song-search.apple_music.storefront', 'us');

        try {
            $response = $this->client->get("catalog/{$storefront}/search", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'query' => [
                    'term' => $query,
                    'types' => 'songs',
                    'limit' => config('song-search.search.max_results', 20),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $songs = $data['results']['songs']['data'] ?? [];

            return array_map(function ($song) {
                $attributes = $song['attributes'] ?? [];

                // Get high-res artwork URL (replace {w}x{h} with actual size)
                $artworkUrl = null;
                if (! empty($attributes['artwork']['url'])) {
                    $size = config('song-search.artwork.preferred_size', 640);
                    $artworkUrl = str_replace(
                        ['{w}', '{h}'],
                        [$size, $size],
                        $attributes['artwork']['url']
                    );
                }

                return [
                    'title' => $attributes['name'] ?? '',
                    'artist' => $attributes['artistName'] ?? '',
                    'album' => $attributes['albumName'] ?? '',
                    'artwork_url' => $artworkUrl,
                    'spotify_url' => null, // Apple Music doesn't provide Spotify URLs
                    'apple_music_url' => $attributes['url'] ?? null,
                    'source' => 'apple_music',
                    'apple_music_id' => $song['id'] ?? null,
                    'isrc' => $attributes['isrc'] ?? null,
                ];
            }, $songs);
        } catch (\Exception $e) {
            Log::error('Apple Music Search Error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Clear the cached token (useful for debugging)
     */
    public function clearTokenCache(): void
    {
        Cache::forget('apple_music_developer_token');
    }
}
