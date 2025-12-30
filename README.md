# Song Search

A Statamic addon for searching and selecting songs from Spotify and Apple Music. Automatically populates song metadata and downloads album artwork.

## Features

- Search songs across Spotify and Apple Music simultaneously
- Auto-populate blueprint fields (title, artist, URLs) from search results
- Automatic album artwork download to your assets container
- ISRC-based matching to combine results from both platforms
- Dark mode support
- Configurable field mapping

## Requirements

- PHP 8.2+
- Statamic 5.0+
- Spotify API credentials (free)
- Apple Music API credentials (optional, requires Apple Developer account)

## Installation

### Via Composer

```bash
composer require newsong/song-search
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=song-search-config
```

### Publish Fieldsets (Optional)

```bash
php artisan vendor:publish --tag=song-search-fieldsets
```

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
# Spotify API (Required)
SPOTIFY_CLIENT_ID=your_spotify_client_id
SPOTIFY_CLIENT_SECRET=your_spotify_client_secret

# Apple Music API (Optional)
APPLE_MUSIC_TEAM_ID=your_apple_team_id
APPLE_MUSIC_KEY_ID=your_apple_key_id
APPLE_MUSIC_PRIVATE_KEY_PATH=storage/app/AuthKey_XXXXXXXXXX.p8
```

### Getting Spotify Credentials

1. Go to [Spotify Developer Dashboard](https://developer.spotify.com/dashboard)
2. Log in with your Spotify account
3. Click "Create App"
4. Fill in the app details (name, description)
5. Copy the **Client ID** and **Client Secret**

### Getting Apple Music Credentials

Apple Music requires more setup but provides access to Apple Music's catalog:

1. Go to [Apple Developer Portal](https://developer.apple.com/account)
2. Navigate to **Certificates, Identifiers & Profiles**
3. Go to **Keys** and create a new key
4. Enable **MusicKit** for the key
5. Download the `.p8` private key file
6. Note your **Key ID** and **Team ID**
7. Place the `.p8` file in your Laravel storage (e.g., `storage/app/AuthKey_XXXXXXXXXX.p8`)

## Usage

### Adding to a Blueprint

Add the `song_search` fieldtype to your blueprint:

```yaml
fields:
  -
    handle: song_search
    field:
      type: song_search
      display: 'Song Search'
      instructions: 'Search for a song to auto-populate fields'
      title_field: title
      artist_field: subtitle
      apple_music_url_field: apple_music_url
      spotify_url_field: spotify_url
      artwork_field: featured_media
      auto_download_artwork: true
```

### Using the Fieldset

Import the pre-built fieldset into your blueprint:

```yaml
fields:
  -
    import: song-search::song_search
```

### Fieldtype Configuration Options

| Option | Default | Description |
|--------|---------|-------------|
| `title_field` | `title` | Handle of the field to populate with song title |
| `artist_field` | `subtitle` | Handle of the field to populate with artist name |
| `apple_music_url_field` | `apple_music_url` | Handle of the field for Apple Music URL |
| `spotify_url_field` | `spotify_url` | Handle of the field for Spotify URL |
| `artwork_field` | `featured_media` | Handle of the assets field for album artwork |
| `auto_download_artwork` | `true` | Automatically download artwork when selecting a song |

### Expected Blueprint Fields

For the fieldtype to work correctly, your blueprint should include these fields:

```yaml
fields:
  -
    handle: title
    field:
      type: text
  -
    handle: subtitle
    field:
      type: text
  -
    handle: apple_music_url
    field:
      type: link
  -
    handle: spotify_url
    field:
      type: link
  -
    handle: featured_media
    field:
      type: assets
      container: assets
      folder: albumartwork
      max_files: 1
```

## Configuration File

The configuration file (`config/song-search.php`) allows you to customize:

```php
return [
    'spotify' => [
        'client_id' => env('SPOTIFY_CLIENT_ID'),
        'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
    ],

    'apple_music' => [
        'team_id' => env('APPLE_MUSIC_TEAM_ID'),
        'key_id' => env('APPLE_MUSIC_KEY_ID'),
        'private_key_path' => env('APPLE_MUSIC_PRIVATE_KEY_PATH'),
        'token_cache_seconds' => 60 * 60 * 24 * 150, // 150 days
        'storefront' => env('APPLE_MUSIC_STOREFRONT', 'us'),
    ],

    'artwork' => [
        'container' => 'assets',
        'folder' => 'albumartwork',
        'preferred_size' => 640,
    ],

    'search' => [
        'max_results' => 20,
        'timeout' => 10,
    ],
];
```

## How It Works

1. Click the "Search for Song" button in the control panel
2. Enter a song title, artist name, or combination
3. Results from Spotify and Apple Music are displayed
4. Songs with matching ISRC codes are merged (showing both platform badges)
5. Select a song to populate all configured fields
6. Album artwork is automatically downloaded to your assets folder

## Template Usage

The fieldtype stores minimal data (just a `searched` flag). The actual song data is stored in the sibling fields:

```antlers
<h1>{{ title }}</h1>
<p>by {{ subtitle }}</p>

{{ if spotify_url }}
  <a href="{{ spotify_url }}">Listen on Spotify</a>
{{ /if }}

{{ if apple_music_url }}
  <a href="{{ apple_music_url }}">Listen on Apple Music</a>
{{ /if }}

{{ featured_media }}
  <img src="{{ url }}" alt="{{ title }} album artwork">
{{ /featured_media }}
```

## Troubleshooting

### "Spotify API is not configured"

Ensure your `.env` file contains valid `SPOTIFY_CLIENT_ID` and `SPOTIFY_CLIENT_SECRET` values.

### "Apple Music API is not configured"

This is expected if you haven't set up Apple Music credentials. The addon will still work with Spotify only.

To configure Apple Music:
1. Ensure all three environment variables are set
2. Verify the `.p8` file exists at the specified path
3. Check the file has correct permissions (readable by PHP)

### Artwork not downloading

1. Ensure your asset container exists
2. Check the `albumartwork` folder exists or `create_folder` is enabled
3. Verify write permissions on the assets directory

### Search returns no results

1. Check Laravel logs for API errors: `storage/logs/laravel.log`
2. Verify your API credentials are correct
3. Try a more specific search query

## Development

### Building Assets

```bash
cd addons/newsong/song-search
pnpm install
pnpm run build
```

### Watching for Changes

```bash
pnpm run dev
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

Developed by [NewSong Church](https://newsongchurch.org)
