<template>
    <div class="song-search-fieldtype">
        <!-- Search Button -->
        <button
            @click="openModal"
            type="button"
            class="btn-primary flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
            </svg>
            Search for Song
        </button>

        <!-- Current Selection Display -->
        <div v-if="currentTitle" class="mt-3 p-3 bg-gray-100 dark:bg-dark-700 rounded border border-gray-200 dark:border-dark-900">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-dark-150 mb-1">Currently selected:</div>
            <div class="font-medium text-gray-900 dark:text-dark-100">{{ currentTitle }}</div>
            <div class="text-sm text-gray-600 dark:text-dark-150">{{ currentArtist }}</div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="song-search-modal-overlay" @click.self="closeModal">
            <div class="song-search-modal">
                <div class="modal-header">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-dark-100">Search for Song</h2>
                    <button @click="closeModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-dark-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Search Input -->
                    <div class="mb-4">
                        <div class="flex gap-2">
                            <input
                                type="text"
                                v-model="searchQuery"
                                @keypress.enter="search"
                                placeholder="Enter song title or artist..."
                                class="input-text flex-1"
                                ref="searchInput"
                            />
                            <button
                                @click="search"
                                type="button"
                                class="btn-primary"
                                :disabled="searching || !searchQuery.trim()"
                            >
                                <span v-if="searching" class="flex items-center">
                                    <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Searching...
                                </span>
                                <span v-else>Search</span>
                            </button>
                        </div>
                    </div>

                    <!-- Error Display -->
                    <div v-if="error" class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-sm text-red-700 dark:text-red-400">
                        {{ error }}
                    </div>

                    <!-- API Warnings -->
                    <div v-if="Object.keys(apiErrors).length > 0" class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded text-sm text-yellow-700 dark:text-yellow-400">
                        <div v-for="(msg, key) in apiErrors" :key="key">{{ msg }}</div>
                    </div>

                    <!-- Results -->
                    <div v-if="results.length > 0" class="results-container">
                        <div class="text-sm text-gray-600 dark:text-dark-150 mb-2">
                            {{ results.length }} result(s) found
                        </div>
                        <div class="results-list">
                            <div
                                v-for="(result, index) in results"
                                :key="index"
                                @click="selectSong(result)"
                                class="result-item"
                                :class="{ 'selected': selectedResult === result }"
                            >
                                <div class="result-artwork">
                                    <img
                                        v-if="result.artwork_url"
                                        :src="result.artwork_url"
                                        :alt="result.title"
                                        class="w-12 h-12 rounded object-cover"
                                    />
                                    <div v-else class="w-12 h-12 bg-gray-200 dark:bg-dark-600 rounded flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="result-info">
                                    <div class="result-title">{{ result.title }}</div>
                                    <div class="result-artist">{{ result.artist }}</div>
                                    <div class="result-album">{{ result.album }}</div>
                                </div>
                                <div class="result-sources">
                                    <span v-if="result.spotify_url" class="source-badge spotify" title="Available on Spotify">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                                        </svg>
                                    </span>
                                    <span v-if="result.apple_music_url" class="source-badge apple" title="Available on Apple Music">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Results -->
                    <div v-else-if="hasSearched && !searching" class="text-center py-8 text-gray-500 dark:text-dark-150">
                        No results found. Try a different search term.
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="closeModal" type="button" class="btn">
                        Cancel
                    </button>
                    <button
                        @click="confirmSelection"
                        type="button"
                        class="btn-primary"
                        :disabled="!selectedResult || saving"
                    >
                        <span v-if="saving" class="flex items-center">
                            <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                        <span v-else>Select Song</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    mixins: [Fieldtype],

    inject: {
        storeName: {
            default: 'base'
        },
        setFieldValue: {
            default: null
        }
    },

    data() {
        return {
            showModal: false,
            searchQuery: '',
            searching: false,
            hasSearched: false,
            results: [],
            selectedResult: null,
            saving: false,
            error: null,
            apiErrors: {},
        };
    },

    computed: {
        /**
         * Get current form values from the Vuex store
         */
        formValues() {
            if (this.$store && this.$store.state.publish && this.$store.state.publish[this.storeName]) {
                return this.$store.state.publish[this.storeName].values || {};
            }
            return {};
        },

        currentTitle() {
            const field = this.meta.titleField || 'title';
            return this.formValues[field] || '';
        },

        currentArtist() {
            const field = this.meta.artistField || 'subtitle';
            return this.formValues[field] || '';
        },
    },

    methods: {
        openModal() {
            this.showModal = true;
            this.searchQuery = '';
            this.results = [];
            this.selectedResult = null;
            this.hasSearched = false;
            this.error = null;
            this.apiErrors = {};

            // Focus search input after modal opens
            this.$nextTick(() => {
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                }
            });
        },

        closeModal() {
            this.showModal = false;
        },

        async search() {
            if (!this.searchQuery.trim()) return;

            this.searching = true;
            this.error = null;
            this.apiErrors = {};
            this.results = [];
            this.selectedResult = null;

            try {
                const response = await axios.get('/cp/song-search/search', {
                    params: { query: this.searchQuery },
                });

                if (response.data.success) {
                    this.results = response.data.results || [];

                    // Capture any API errors/warnings
                    if (response.data.errors && Object.keys(response.data.errors).length > 0) {
                        this.apiErrors = response.data.errors;
                    }
                } else {
                    this.error = response.data.message || 'Search failed';
                }
            } catch (err) {
                this.error = 'Failed to search. Please try again.';
                console.error('Song search error:', err);
            } finally {
                this.searching = false;
                this.hasSearched = true;
            }
        },

        selectSong(result) {
            this.selectedResult = result;
        },

        async confirmSelection() {
            if (!this.selectedResult) return;

            this.saving = true;
            this.error = null;

            try {
                const song = this.selectedResult;
                let artworkAssetId = null;

                // Download artwork if enabled and URL available
                if (this.meta.autoDownloadArtwork && song.artwork_url) {
                    try {
                        const artworkResponse = await axios.post('/cp/song-search/download-artwork', {
                            url: song.artwork_url,
                            title: song.title,
                            artist: song.artist,
                        });

                        if (artworkResponse.data.success) {
                            artworkAssetId = artworkResponse.data.asset_id;
                        }
                    } catch (artworkErr) {
                        console.warn('Failed to download artwork:', artworkErr);
                        // Continue without artwork - don't fail the whole operation
                    }
                }

                // Update sibling fields
                this.setSiblingFieldValue(this.meta.titleField || 'title', song.title);
                this.setSiblingFieldValue(this.meta.artistField || 'subtitle', song.artist);

                if (song.apple_music_url) {
                    this.setSiblingFieldValue(this.meta.appleMusicUrlField || 'apple_music_url', song.apple_music_url);
                }

                if (song.spotify_url) {
                    this.setSiblingFieldValue(this.meta.spotifyUrlField || 'spotify_url', song.spotify_url);
                }

                if (artworkAssetId && this.meta.artworkField) {
                    // Assets field expects an array of asset IDs
                    this.setSiblingFieldValue(this.meta.artworkField, [artworkAssetId]);
                }

                // Update our own field value to mark as searched
                this.update({
                    searched: true,
                    last_search_query: this.searchQuery,
                });

                this.closeModal();
            } catch (err) {
                this.error = 'Failed to save selection. Please try again.';
                console.error('Save selection error:', err);
            } finally {
                this.saving = false;
            }
        },

        /**
         * Update a sibling field value
         */
        setSiblingFieldValue(handle, value) {
            if (!handle) return;

            // Method 1: Use injected setFieldValue from Container (preferred)
            if (typeof this.setFieldValue === 'function') {
                this.setFieldValue(handle, value);
                return;
            }

            // Method 2: Try to find the publish container and call its method
            let parent = this.$parent;
            while (parent) {
                if (parent.setFieldValue && typeof parent.setFieldValue === 'function') {
                    parent.setFieldValue(handle, value);
                    return;
                }
                parent = parent.$parent;
            }

            // Method 3: Fallback to Vuex dispatch
            if (this.$store && this.$store.dispatch) {
                this.$store.dispatch(`publish/${this.storeName}/setFieldValue`, {
                    handle: handle,
                    value: value,
                    user: Statamic.user ? Statamic.user.id : null,
                });
            }
        },
    },
};
</script>

<style scoped>
.song-search-modal-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.song-search-modal {
    background: white;
    border-radius: 0.5rem;
    width: 100%;
    max-width: 640px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

:global(.dark) .song-search-modal {
    background: #1f2937;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

:global(.dark) .modal-header {
    border-color: #374151;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
}

:global(.dark) .modal-footer {
    border-color: #374151;
}

.results-container {
    max-height: 400px;
    overflow-y: auto;
}

.results-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.result-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
}

:global(.dark) .result-item {
    border-color: #374151;
}

.result-item:hover {
    background-color: #f9fafb;
}

:global(.dark) .result-item:hover {
    background-color: #374151;
}

.result-item.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

:global(.dark) .result-item.selected {
    background-color: rgba(59, 130, 246, 0.1);
    border-color: #3b82f6;
}

.result-info {
    flex: 1;
    min-width: 0;
}

.result-title {
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

:global(.dark) .result-title {
    color: #f3f4f6;
}

.result-artist {
    font-size: 0.875rem;
    color: #6b7280;
}

:global(.dark) .result-artist {
    color: #9ca3af;
}

.result-album {
    font-size: 0.75rem;
    color: #9ca3af;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

:global(.dark) .result-album {
    color: #6b7280;
}

.result-sources {
    display: flex;
    gap: 0.25rem;
    flex-shrink: 0;
}

.source-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 9999px;
}

.source-badge.spotify {
    background-color: #1db954;
    color: white;
}

.source-badge.apple {
    background-color: #fa243c;
    color: white;
}
</style>
