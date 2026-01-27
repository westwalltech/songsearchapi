# Changelog

All notable changes to Song Search will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-01-27

### Changed
- **Statamic 6 Support** - Full compatibility with Statamic 6 and Laravel 12
- Updated Vue components to use Statamic v6 UI component imports
- Added missing `Icon` component import
- Updated `orchestra/testbench` to ^10.0 for Laravel 12 compatibility

### Fixed
- Fixed "Failed to resolve component: Icon" Vue warning

## [1.0.0] - 2024-11-15

### Added
- Initial release of Song Search for Statamic
- Search integration with Spotify and Apple Music APIs
- Auto-populate entry fields from search results
- Artwork auto-download to asset container
- Modal-based search interface
- Current selection display
- Configurable field mappings

### Features
- **Search Modal** - Clean search interface with results table
- **Multi-Platform** - Search Spotify and Apple Music simultaneously
- **Auto-Fill** - Populate title, artist, and streaming URLs
- **Artwork Download** - Automatically download and save album artwork
- **Field Mapping** - Configure which fields receive search data

### Technical
- Built with Vue 3 and Vite
- PHP 8.2+ and Statamic 5+ required
- Guzzle HTTP client for API requests
- Spotify OAuth token management
- Apple Music JWT authentication

[1.1.0]: https://github.com/newsong/song-search/releases/tag/v1.1.0
[1.0.0]: https://github.com/newsong/song-search/releases/tag/v1.0.0
