<template>
    <div class="song-search-fieldtype" :class="{ 'dark-mode': isDarkMode }">
        <!-- Search Button -->
        <button
            @click="openModal"
            type="button"
            class="search-trigger-btn"
        >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
            </svg>
            Search for Song
        </button>

        <!-- Current Selection Display -->
        <div v-if="currentTitle" class="current-selection">
            <div class="current-selection-label">Currently selected:</div>
            <div class="current-selection-title">{{ currentTitle }}</div>
            <div class="current-selection-artist">{{ currentArtist }}</div>
        </div>

        <!-- Modal -->
        <transition name="modal-fade">
            <div v-if="showModal" class="song-search-modal-overlay" :class="{ 'dark-mode': isDarkMode }" @click.self="closeModal">
                <transition name="modal-slide">
                    <div v-if="showModal" class="song-search-modal" :class="{ 'dark-mode': isDarkMode }" @keydown.esc="closeModal">
                            <div class="modal-header">
                                <h2 class="modal-title">Search for Song</h2>
                                <button @click="closeModal" type="button" class="modal-close-btn" aria-label="Close modal">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="modal-body">
                                <!-- Search Input -->
                                <div class="search-input-container">
                                    <div class="search-input-wrapper">
                                        <input
                                            type="search"
                                            inputmode="search"
                                            v-model="searchQuery"
                                            @keypress.enter="search"
                                            placeholder="Enter song title or artist..."
                                            class="search-input"
                                            ref="searchInput"
                                        />
                                        <button
                                            @click="search"
                                            type="button"
                                            class="search-btn"
                                            :class="{ 'is-loading': searching }"
                                            :disabled="searching || !searchQuery.trim()"
                                        >
                                            <span v-if="searching" class="btn-content">
                                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span class="btn-text-mobile">...</span>
                                                <span class="btn-text-desktop">Searching...</span>
                                            </span>
                                            <span v-else>Search</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Error Display -->
                                <div v-if="error" class="alert alert-error">
                                    {{ error }}
                                </div>

                                <!-- API Warnings -->
                                <div v-if="Object.keys(apiErrors).length > 0" class="alert alert-warning">
                                    <div v-for="(msg, key) in apiErrors" :key="key">{{ msg }}</div>
                                </div>

                                <!-- Results -->
                                <div v-if="results.length > 0" class="results-container">
                                    <div class="results-count">
                                        {{ results.length }} result(s) found
                                    </div>
                                    <div class="results-list">
                                        <div
                                            v-for="(result, index) in results"
                                            :key="index"
                                            @click="selectSong(result)"
                                            class="result-item"
                                            :class="{ 'selected': selectedResult === result }"
                                            role="button"
                                            tabindex="0"
                                            @keypress.enter="selectSong(result)"
                                        >
                                            <div class="result-artwork">
                                                <img
                                                    v-if="result.artwork_url"
                                                    :src="result.artwork_url"
                                                    :alt="result.title"
                                                    class="artwork-image"
                                                    loading="lazy"
                                                />
                                                <div v-else class="artwork-placeholder">
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
                                <div v-else-if="hasSearched && !searching" class="empty-state">
                                    <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                                    </svg>
                                    <p class="empty-state-text">No results found. Try a different search term.</p>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button @click="closeModal" type="button" class="btn-secondary">
                                    Cancel
                                </button>
                                <button
                                    @click="confirmSelection"
                                    type="button"
                                    class="btn-primary"
                                    :disabled="!selectedResult || saving"
                                >
                                    <span v-if="saving" class="btn-content">
                                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Saving...</span>
                                    </span>
                                    <span v-else>Select Song</span>
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </transition>
    </div>
</template>

<script>
import axios from 'axios';
import { Fieldtype } from '@statamic/cms';

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
            isDarkMode: false,
        };
    },

    mounted() {
        // Check for dark mode on mount
        this.checkDarkMode();

        // Watch for dark mode changes using MutationObserver
        this.darkModeObserver = new MutationObserver(() => {
            this.checkDarkMode();
        });

        this.darkModeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    },

    beforeDestroy() {
        if (this.darkModeObserver) {
            this.darkModeObserver.disconnect();
        }
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
        checkDarkMode() {
            this.isDarkMode = document.documentElement.classList.contains('dark');
        },

        openModal() {
            this.showModal = true;
            this.searchQuery = '';
            this.results = [];
            this.selectedResult = null;
            this.hasSearched = false;
            this.error = null;
            this.apiErrors = {};

            // Prevent body scroll when modal is open
            document.body.style.overflow = 'hidden';

            // Focus search input after modal opens
            this.$nextTick(() => {
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                }
            });
        },

        closeModal() {
            this.showModal = false;
            // Restore body scroll
            document.body.style.overflow = '';
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
/* ============================
   SEARCH TRIGGER BUTTON
   ============================ */
.search-trigger-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    min-height: 44px;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
    background-color: #3b82f6;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.search-trigger-btn:hover {
    background-color: #2563eb;
}

.search-trigger-btn:active {
    transform: scale(0.98);
}

:global(.dark) .search-trigger-btn {
    background-color: #2563eb;
}

:global(.dark) .search-trigger-btn:hover {
    background-color: #3b82f6;
}

/* Note: Current selection inherits from parent dark mode */

/* ============================
   CURRENT SELECTION DISPLAY
   ============================ */
.current-selection {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background-color: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
}

.dark-mode .current-selection {
    background-color: #374151;
    border-color: #4b5563;
}

.current-selection-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.dark-mode .current-selection-label {
    color: #9ca3af;
}

.current-selection-title {
    font-weight: 600;
    color: #111827;
}

.dark-mode .current-selection-title {
    color: #f3f4f6;
}

.current-selection-artist {
    font-size: 0.875rem;
    color: #6b7280;
}

.dark-mode .current-selection-artist {
    color: #9ca3af;
}

/* ============================
   MODAL OVERLAY & TRANSITIONS
   ============================ */
.song-search-modal-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
}

.song-search-modal-overlay.dark-mode {
    background-color: rgba(0, 0, 0, 0.7);
}

/* Modal fade transition (Vue 2.7 compatible) */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter,
.modal-fade-leave-to {
    opacity: 0;
}

/* Modal slide transition (Vue 2.7 compatible) */
.modal-slide-enter-active,
.modal-slide-leave-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-slide-enter,
.modal-slide-leave-to {
    opacity: 0;
    transform: translateY(20px);
}

/* Mobile: slide up from bottom */
@media (max-width: 640px) {
    .song-search-modal-overlay {
        padding: 0;
        align-items: flex-end;
    }

    .modal-slide-enter,
    .modal-slide-leave-to {
        transform: translateY(100%);
    }
}

/* ============================
   MODAL CONTAINER
   ============================ */
.song-search-modal {
    background: white;
    border-radius: 0.5rem;
    width: 100%;
    max-width: 640px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
}

.song-search-modal.dark-mode {
    background: #1f2937;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

/* Mobile: Full width, bottom sheet style */
@media (max-width: 640px) {
    .song-search-modal {
        max-width: 100%;
        max-height: 90vh;
        border-radius: 1rem 1rem 0 0;
    }
}

/* ============================
   MODAL HEADER
   ============================ */
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.dark-mode .modal-header {
    border-color: #374151;
}

@media (max-width: 640px) {
    .modal-header {
        padding: 1rem;
    }
}

.modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.dark-mode .modal-title {
    color: #f3f4f6;
}

.modal-close-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    margin: -0.5rem -0.5rem -0.5rem 0;
    padding: 0;
    color: #9ca3af;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.modal-close-btn:hover {
    color: #4b5563;
    background-color: #f3f4f6;
}

.dark-mode .modal-close-btn:hover {
    color: #e5e7eb;
    background-color: #374151;
}

/* ============================
   MODAL BODY
   ============================ */
.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.25rem;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 640px) {
    .modal-body {
        padding: 1rem;
    }
}

/* ============================
   SEARCH INPUT
   ============================ */
.search-input-container {
    margin-bottom: 1rem;
}

.search-input-wrapper {
    display: flex;
    gap: 0.5rem;
}

@media (max-width: 640px) {
    .search-input-wrapper {
        flex-direction: column;
    }
}

.search-input {
    flex: 1;
    padding: 0.75rem 1rem;
    min-height: 44px;
    font-size: 1rem;
    color: #111827;
    background-color: white;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    outline: none;
    transition: all 0.15s ease;
    -webkit-appearance: none;
}

.search-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-input::placeholder {
    color: #9ca3af;
}

.dark-mode .search-input {
    color: #f3f4f6;
    background-color: #374151;
    border-color: #4b5563;
}

.dark-mode .search-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.dark-mode .search-input::placeholder {
    color: #6b7280;
}

.search-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    min-height: 44px;
    width: 120px;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
    background-color: #3b82f6;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background-color 0.15s ease, transform 0.15s ease;
    white-space: nowrap;
    flex-shrink: 0;
}

.search-btn:hover:not(:disabled) {
    background-color: #2563eb;
}

.search-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Loading state - keep visible, just show not clickable */
.search-btn.is-loading {
    opacity: 1;
    cursor: wait;
}

.dark-mode .search-btn {
    background-color: #2563eb;
}

.dark-mode .search-btn:hover:not(:disabled) {
    background-color: #3b82f6;
}

@media (max-width: 640px) {
    .search-btn {
        width: 100%;
    }
}

.btn-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-text-mobile {
    display: none;
}

.btn-text-desktop {
    display: inline;
}

@media (max-width: 640px) {
    .btn-text-mobile {
        display: inline;
    }
    .btn-text-desktop {
        display: none;
    }
}

/* ============================
   ALERTS (ERROR/WARNING)
   ============================ */
.alert {
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    border-radius: 0.375rem;
}

.alert-error {
    color: #991b1b;
    background-color: #fef2f2;
    border: 1px solid #fecaca;
}

.dark-mode .alert-error {
    color: #fca5a5;
    background-color: rgba(127, 29, 29, 0.2);
    border-color: rgba(248, 113, 113, 0.3);
}

.alert-warning {
    color: #92400e;
    background-color: #fffbeb;
    border: 1px solid #fde68a;
}

.dark-mode .alert-warning {
    color: #fcd34d;
    background-color: rgba(120, 53, 15, 0.2);
    border-color: rgba(251, 191, 36, 0.3);
}

/* ============================
   RESULTS CONTAINER
   ============================ */
.results-container {
    overflow: hidden;
}

.results-count {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.75rem;
}

.dark-mode .results-count {
    color: #9ca3af;
}

.results-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 320px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 640px) {
    .results-list {
        max-height: none;
        gap: 0.75rem;
    }
}

/* ============================
   RESULT ITEMS
   ============================ */
.result-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    min-height: 72px;
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
    -webkit-tap-highlight-color: transparent;
}

.result-item:hover {
    background-color: #f9fafb;
    border-color: #d1d5db;
}

.result-item:active {
    transform: scale(0.99);
}

.result-item:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.dark-mode .result-item {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode .result-item:hover {
    background-color: #374151;
    border-color: #4b5563;
}

.dark-mode .result-item:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.result-item.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.dark-mode .result-item.selected {
    background-color: rgba(59, 130, 246, 0.15);
    border-color: #3b82f6;
}

@media (max-width: 640px) {
    .result-item {
        padding: 1rem;
        gap: 1rem;
    }
}

/* ============================
   ARTWORK
   ============================ */
.result-artwork {
    flex-shrink: 0;
}

.artwork-image {
    width: 48px;
    height: 48px;
    border-radius: 0.375rem;
    object-fit: cover;
    background-color: #f3f4f6;
}

.dark-mode .artwork-image {
    background-color: #374151;
}

.artwork-placeholder {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f3f4f6;
    border-radius: 0.375rem;
    color: #9ca3af;
}

.dark-mode .artwork-placeholder {
    background-color: #374151;
    color: #6b7280;
}

@media (max-width: 640px) {
    .artwork-image,
    .artwork-placeholder {
        width: 56px;
        height: 56px;
    }
}

/* ============================
   RESULT INFO
   ============================ */
.result-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.result-title {
    font-weight: 600;
    font-size: 0.9375rem;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dark-mode .result-title {
    color: #f3f4f6;
}

.result-artist {
    font-size: 0.875rem;
    color: #4b5563;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 0.125rem;
}

.dark-mode .result-artist {
    color: #9ca3af;
}

.result-album {
    font-size: 0.75rem;
    color: #9ca3af;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 0.125rem;
}

.dark-mode .result-album {
    color: #6b7280;
}

/* ============================
   SOURCE BADGES
   ============================ */
.result-sources {
    display: flex;
    gap: 0.375rem;
    flex-shrink: 0;
}

.source-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 9999px;
    transition: transform 0.15s ease;
}

.source-badge:hover {
    transform: scale(1.1);
}

.source-badge.spotify {
    background-color: #1db954;
    color: white;
}

.source-badge.apple {
    background-color: #fa243c;
    color: white;
}

@media (max-width: 640px) {
    .source-badge {
        width: 32px;
        height: 32px;
    }
}

/* ============================
   EMPTY STATE
   ============================ */
.empty-state {
    text-align: center;
    padding: 2rem 1rem;
}

.empty-state-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 1rem;
    color: #d1d5db;
}

.dark-mode .empty-state-icon {
    color: #4b5563;
}

.empty-state-text {
    color: #6b7280;
    font-size: 0.9375rem;
    margin: 0;
}

.dark-mode .empty-state-text {
    color: #9ca3af;
}

/* ============================
   MODAL FOOTER
   ============================ */
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.dark-mode .modal-footer {
    border-color: #374151;
}

@media (max-width: 640px) {
    .modal-footer {
        flex-direction: column-reverse;
        padding: 1rem;
        gap: 0.5rem;
    }
}

/* ============================
   BUTTONS
   ============================ */
.btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    min-height: 44px;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
    background-color: #3b82f6;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-primary:hover:not(:disabled) {
    background-color: #2563eb;
}

.btn-primary:active:not(:disabled) {
    transform: scale(0.98);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.dark-mode .btn-primary {
    background-color: #2563eb;
}

.dark-mode .btn-primary:hover:not(:disabled) {
    background-color: #3b82f6;
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1.25rem;
    min-height: 44px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-secondary:hover {
    background-color: #e5e7eb;
    border-color: #9ca3af;
}

.btn-secondary:active {
    transform: scale(0.98);
}

.dark-mode .btn-secondary {
    color: #e5e7eb;
    background-color: #374151;
    border-color: #4b5563;
}

.dark-mode .btn-secondary:hover {
    background-color: #4b5563;
    border-color: #6b7280;
}

@media (max-width: 640px) {
    .btn-primary,
    .btn-secondary {
        width: 100%;
        padding: 0.875rem 1.25rem;
    }
}

/* ============================
   ANIMATIONS
   ============================ */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* ============================
   SAFE AREA SUPPORT (iOS)
   ============================ */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
    .modal-footer {
        padding-bottom: calc(1rem + env(safe-area-inset-bottom));
    }

    @media (max-width: 640px) {
        .song-search-modal {
            padding-bottom: env(safe-area-inset-bottom);
        }
    }
}
</style>
