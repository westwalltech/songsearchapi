<?php

namespace NewSong\SongSearch\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpotifyService
{
    protected Client $client;
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected string $baseUrl = 'https://api.spotify.com/v1/';

    public function __construct()
    {
        $this->clientId = config('song-search.spotify.client_id');
        $this->clientSecret = config('song-search.spotify.client_secret');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => config('song-search.search.timeout', 10),
        ]);
    }

    /**
     * Check if the service is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->clientId) && ! empty($this->clientSecret);
    }

    /**
     * Get access token using Client Credentials flow (cached for 1 hour)
     */
    protected function getAccessToken(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return Cache::remember('spotify_song_search_token', 3600, function () {
            try {
                $client = new Client;
                $response = $client->post('https://accounts.spotify.com/api/token', [
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                    ],
                    'headers' => [
                        'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                return $data['access_token'] ?? null;
            } catch (\Exception $e) {
                Log::error('Spotify Auth Error: '.$e->getMessage());

                return null;
            }
        });
    }

    /**
     * Search for songs by query
     *
     * @param  string  $query  Search query (song title, artist, etc.)
     * @return array Normalized song results
     */
    public function searchSongs(string $query): array
    {
        $token = $this->getAccessToken();
        if (! $token) {
            Log::warning('Spotify: Unable to get access token');

            return [];
        }

        try {
            $response = $this->client->get('search', [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'query' => [
                    'q' => $query,
                    'type' => 'track',
                    'limit' => config('song-search.search.max_results', 20),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $tracks = $data['tracks']['items'] ?? [];

            return array_map(function ($track) {
                // Get the largest album artwork available
                $artworkUrl = null;
                $images = $track['album']['images'] ?? [];
                if (! empty($images)) {
                    // Images are sorted by size descending
                    $artworkUrl = $images[0]['url'];
                }

                return [
                    'title' => $track['name'],
                    'artist' => implode(', ', array_column($track['artists'], 'name')),
                    'album' => $track['album']['name'] ?? '',
                    'artwork_url' => $artworkUrl,
                    'spotify_url' => $track['external_urls']['spotify'] ?? null,
                    'apple_music_url' => null, // Spotify doesn't provide Apple Music URLs
                    'source' => 'spotify',
                    'spotify_id' => $track['id'],
                    'isrc' => $track['external_ids']['isrc'] ?? null,
                ];
            }, $tracks);
        } catch (\Exception $e) {
            Log::error('Spotify Search Error: '.$e->getMessage());

            return [];
        }
    }
}
