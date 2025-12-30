<?php

namespace NewSong\SongSearch\Fieldtypes;

use Statamic\Fields\Fieldtype;

class SongSearch extends Fieldtype
{
    protected $icon = 'audio-file';

    protected $categories = ['media'];

    /**
     * The blank/default value
     */
    public function defaultValue()
    {
        return [
            'searched' => false,
            'last_search_query' => null,
        ];
    }

    /**
     * Pre-process the data before it gets sent to the publish page
     */
    public function preProcess($data)
    {
        if (!is_array($data)) {
            return $this->defaultValue();
        }

        return array_merge($this->defaultValue(), $data);
    }

    /**
     * Process the data before it gets saved
     */
    public function process($data)
    {
        if (!is_array($data)) {
            return $this->defaultValue();
        }

        return [
            'searched' => $data['searched'] ?? false,
            'last_search_query' => $data['last_search_query'] ?? null,
        ];
    }

    /**
     * Augment the value for use in templates
     */
    public function augment($value)
    {
        if (!is_array($value)) {
            return ['searched' => false];
        }

        return [
            'searched' => $value['searched'] ?? false,
        ];
    }

    /**
     * Define the fieldtype config blueprint
     */
    protected function configFieldItems(): array
    {
        return [
            'title_field' => [
                'type' => 'text',
                'display' => 'Title Field Handle',
                'instructions' => 'The handle of the sibling field to populate with the song title.',
                'default' => 'title',
                'width' => 50,
            ],
            'artist_field' => [
                'type' => 'text',
                'display' => 'Artist Field Handle',
                'instructions' => 'The handle of the sibling field to populate with the artist name.',
                'default' => 'subtitle',
                'width' => 50,
            ],
            'apple_music_url_field' => [
                'type' => 'text',
                'display' => 'Apple Music URL Field Handle',
                'instructions' => 'The handle of the sibling field for the Apple Music URL.',
                'default' => 'apple_music_url',
                'width' => 50,
            ],
            'spotify_url_field' => [
                'type' => 'text',
                'display' => 'Spotify URL Field Handle',
                'instructions' => 'The handle of the sibling field for the Spotify URL.',
                'default' => 'spotify_url',
                'width' => 50,
            ],
            'artwork_field' => [
                'type' => 'text',
                'display' => 'Featured Media Field Handle',
                'instructions' => 'The handle of the sibling assets field for album artwork.',
                'default' => 'featured_media',
                'width' => 50,
            ],
            'auto_download_artwork' => [
                'type' => 'toggle',
                'display' => 'Auto Download Artwork',
                'instructions' => 'Automatically download album artwork when a song is selected.',
                'default' => true,
                'width' => 50,
            ],
        ];
    }

    /**
     * Provide data to the Vue component via meta
     */
    public function preload()
    {
        return [
            'titleField' => $this->config('title_field', 'title'),
            'artistField' => $this->config('artist_field', 'subtitle'),
            'appleMusicUrlField' => $this->config('apple_music_url_field', 'apple_music_url'),
            'spotifyUrlField' => $this->config('spotify_url_field', 'spotify_url'),
            'artworkField' => $this->config('artwork_field', 'featured_media'),
            'autoDownloadArtwork' => $this->config('auto_download_artwork', true),
        ];
    }
}
