<?php

namespace NewSong\SongSearch\Services;

use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;

class ArtworkDownloader
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
        ]);
    }

    /**
     * Download artwork and create a Statamic asset
     *
     * @param string $url The artwork URL
     * @param string $title Song title for filename
     * @param string $artist Artist name for filename
     * @return string|null The asset ID or null on failure
     */
    public function downloadAndCreateAsset(string $url, string $title, string $artist): ?string
    {
        try {
            // Generate a sanitized filename
            $filename = $this->generateFilename($title, $artist);

            $containerHandle = config('song-search.artwork.container', 'assets');
            $folder = config('song-search.artwork.folder', 'albumartwork');

            // Check if asset container exists
            $container = AssetContainer::find($containerHandle);
            if (!$container) {
                Log::error("Song Search: Asset container '{$containerHandle}' not found");
                return null;
            }

            // Build the asset path
            $path = $folder . '/' . $filename;

            // Check if asset already exists
            $existingAsset = Asset::find("{$containerHandle}::{$path}");
            if ($existingAsset) {
                // Asset already exists, return its ID
                Log::info("Song Search: Asset already exists at {$path}");
                return $existingAsset->id();
            }

            // Download the image to a temp file
            $tempPath = sys_get_temp_dir() . '/' . uniqid('artwork_') . '_' . $filename;
            $response = $this->client->get($url, [
                'sink' => $tempPath,
            ]);

            // Detect content type and adjust extension if needed
            $contentType = $response->getHeaderLine('Content-Type');
            $extension = $this->getExtensionFromContentType($contentType);

            // Rename file if extension doesn't match
            if ($extension && !Str::endsWith(strtolower($filename), '.' . $extension)) {
                $newFilename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;
                $newTempPath = sys_get_temp_dir() . '/' . uniqid('artwork_') . '_' . $newFilename;
                rename($tempPath, $newTempPath);
                $tempPath = $newTempPath;
                $filename = $newFilename;
                $path = $folder . '/' . $filename;
            }

            // Create an UploadedFile instance
            $uploadedFile = new UploadedFile(
                $tempPath,
                $filename,
                $contentType ?: 'image/jpeg',
                null,
                true // Mark as already moved (test mode)
            );

            // Create the asset
            $asset = Asset::make()
                ->container($containerHandle)
                ->path($path);

            // Upload the file
            $asset->upload($uploadedFile);

            // Set some metadata
            $asset->set('alt', "{$title} by {$artist}");
            $asset->save();

            // Clean up temp file if it still exists
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }

            Log::info("Song Search: Created asset at {$path}");
            return $asset->id();
        } catch (\Exception $e) {
            Log::error('Song Search Artwork Download Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a sanitized filename from song title and artist
     */
    protected function generateFilename(string $title, string $artist): string
    {
        $slug = Str::slug($artist . '-' . $title);
        // Limit length
        $slug = Str::limit($slug, 100, '');
        // Remove any trailing hyphens
        $slug = rtrim($slug, '-');
        return $slug . '.jpg';
    }

    /**
     * Get file extension from content type
     */
    protected function getExtensionFromContentType(string $contentType): ?string
    {
        // Extract just the MIME type if there are parameters
        $contentType = explode(';', $contentType)[0];
        $contentType = trim($contentType);

        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        return $map[$contentType] ?? null;
    }
}
