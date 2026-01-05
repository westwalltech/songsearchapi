<?php

namespace NewSong\SongSearch\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NewSong\SongSearch\Services\SpotifyService;
use NewSong\SongSearch\Services\AppleMusicService;
use NewSong\SongSearch\Services\ArtworkDownloader;

class SongSearchController extends Controller
{
    protected SpotifyService $spotify;
    protected AppleMusicService $appleMusic;
    protected ArtworkDownloader $artworkDownloader;

    public function __construct(
        SpotifyService $spotify,
        AppleMusicService $appleMusic,
        ArtworkDownloader $artworkDownloader
    ) {
        $this->spotify = $spotify;
        $this->appleMusic = $appleMusic;
        $this->artworkDownloader = $artworkDownloader;
    }

    /**
     * Search for songs across all configured platforms
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required',
            ], 400);
        }

        $results = [];
        $errors = [];

        // Search Spotify
        if ($this->spotify->isConfigured()) {
            try {
                $spotifyResults = $this->spotify->searchSongs($query);
                $results = array_merge($results, $spotifyResults);
            } catch (\Exception $e) {
                $errors['spotify'] = 'Failed to search Spotify: ' . $e->getMessage();
            }
        } else {
            $errors['spotify'] = 'Spotify API is not configured';
        }

        // Search Apple Music
        if ($this->appleMusic->isConfigured()) {
            try {
                $appleResults = $this->appleMusic->searchSongs($query);
                $results = array_merge($results, $appleResults);
            } catch (\Exception $e) {
                $errors['apple_music'] = 'Failed to search Apple Music: ' . $e->getMessage();
            }
        } else {
            $errors['apple_music'] = 'Apple Music API is not configured';
        }

        // Merge results by ISRC if available, otherwise keep separate
        $mergedResults = $this->mergeResultsByISRC($results);

        // Filter out explicit content
        $filteredResults = $this->filterExplicitContent($mergedResults);

        return response()->json([
            'success' => true,
            'results' => array_values($filteredResults),
            'errors' => $errors,
            'total' => count($filteredResults),
        ]);
    }

    /**
     * Download artwork and return asset ID
     */
    public function downloadArtwork(Request $request): JsonResponse
    {
        $url = $request->input('url');
        $title = $request->input('title');
        $artist = $request->input('artist');

        if (empty($url) || empty($title) || empty($artist)) {
            return response()->json([
                'success' => false,
                'message' => 'URL, title, and artist are required',
            ], 400);
        }

        try {
            $assetId = $this->artworkDownloader->downloadAndCreateAsset($url, $title, $artist);

            if ($assetId) {
                return response()->json([
                    'success' => true,
                    'asset_id' => $assetId,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to download or create artwork asset',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading artwork: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Merge results from different sources by ISRC code
     * Songs with matching ISRC will be combined to have both URLs
     */
    protected function mergeResultsByISRC(array $results): array
    {
        $byISRC = [];
        $withoutISRC = [];

        foreach ($results as $result) {
            $isrc = $result['isrc'] ?? null;

            if ($isrc) {
                if (!isset($byISRC[$isrc])) {
                    $byISRC[$isrc] = $result;
                    // Mark that this song has both platforms if it comes from one
                    $byISRC[$isrc]['has_spotify'] = !empty($result['spotify_url']);
                    $byISRC[$isrc]['has_apple_music'] = !empty($result['apple_music_url']);
                } else {
                    // Merge URLs from different sources
                    if (!empty($result['spotify_url']) && empty($byISRC[$isrc]['spotify_url'])) {
                        $byISRC[$isrc]['spotify_url'] = $result['spotify_url'];
                        $byISRC[$isrc]['spotify_id'] = $result['spotify_id'] ?? null;
                        $byISRC[$isrc]['has_spotify'] = true;
                    }
                    if (!empty($result['apple_music_url']) && empty($byISRC[$isrc]['apple_music_url'])) {
                        $byISRC[$isrc]['apple_music_url'] = $result['apple_music_url'];
                        $byISRC[$isrc]['apple_music_id'] = $result['apple_music_id'] ?? null;
                        $byISRC[$isrc]['has_apple_music'] = true;
                    }
                    // Prefer Spotify artwork (usually higher quality)
                    if (empty($byISRC[$isrc]['artwork_url']) && !empty($result['artwork_url'])) {
                        $byISRC[$isrc]['artwork_url'] = $result['artwork_url'];
                    }
                    // Update source to indicate merged
                    if ($byISRC[$isrc]['has_spotify'] && $byISRC[$isrc]['has_apple_music']) {
                        $byISRC[$isrc]['source'] = 'both';
                    }
                }
            } else {
                $withoutISRC[] = $result;
            }
        }

        // Combine results, with ISRC-matched first (they have more data)
        return array_merge(array_values($byISRC), $withoutISRC);
    }

    /**
     * Filter out songs with explicit/profane content in title, artist, or album
     */
    protected function filterExplicitContent(array $results): array
    {
        // Common profanity/explicit words to filter (case-insensitive)
        // Note: "hell" and "damn" omitted as they appear in legitimate worship contexts
        $explicitWords = [
            'fuck', 'fucking', 'fucked', 'fucker', 'fuckin', 'motherfucker', 'wtf',
            'shit', 'shitting', 'bullshit', 'shitty',
            'bitch', 'bitches', 'bitching',
            'asshole', 'asses',
            'cunt', 'cunts',
            'dick', 'dicks',
            'cock', 'cocks',
            'pussy', 'pussies',
            'whore', 'whores',
            'nigga', 'nigger', 'niggas',
            'slut', 'sluts',
        ];

        // Build regex pattern with word boundaries
        $pattern = '/\b(' . implode('|', array_map('preg_quote', $explicitWords)) . ')\b/i';

        return array_filter($results, function ($result) use ($pattern) {
            $textToCheck = implode(' ', [
                $result['title'] ?? '',
                $result['artist'] ?? '',
                $result['album'] ?? '',
            ]);

            // Return true to KEEP the result (no explicit content found)
            return !preg_match($pattern, $textToCheck);
        });
    }
}
