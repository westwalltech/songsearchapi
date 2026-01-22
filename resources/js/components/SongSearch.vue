<template>
    <div class="song-search-fieldtype">
        <!-- Search Button -->
        <Button @click="openModal" variant="primary" icon="magnifying-glass">
            Search for Song
        </Button>

        <!-- Current Selection Display -->
        <Card v-if="currentTitle" class="mt-3">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-dark-175 mb-1">
                Currently selected
            </div>
            <div class="font-semibold text-gray-900 dark:text-dark-100">{{ currentTitle }}</div>
            <div class="text-sm text-gray-600 dark:text-dark-150">{{ currentArtist }}</div>
        </Card>

        <!-- Search Modal (Centered) -->
        <Modal
            v-model:open="showModal"
            title="Search for Song"
            icon="audio-file"
        >
            <!-- Search Input -->
            <div class="flex gap-2 mb-4">
                <Input
                    ref="searchInput"
                    v-model="searchQuery"
                    @keypress.enter="search"
                    placeholder="Enter song title or artist..."
                    class="flex-1"
                />
                <Button
                    @click="search"
                    variant="primary"
                    :disabled="searching || !searchQuery.trim()"
                    :loading="searching"
                    :text="searching ? 'Searching...' : 'Search'"
                />
            </div>

            <!-- Error Display -->
            <Alert v-if="error" variant="danger" class="mb-4">
                {{ error }}
            </Alert>

            <!-- API Warnings -->
            <Alert v-if="Object.keys(apiErrors).length > 0" variant="warning" class="mb-4">
                <div v-for="(msg, key) in apiErrors" :key="key">{{ msg }}</div>
            </Alert>

            <!-- Results -->
            <div v-if="results.length > 0">
                <div class="text-sm text-gray-600 dark:text-dark-150 mb-2">
                    {{ results.length }} result(s) found
                </div>
                <div class="space-y-2 max-h-[300px] overflow-y-auto p-1 -m-1">
                    <div
                        v-for="(result, index) in results"
                        :key="index"
                        @click="selectSong(result)"
                        class="relative cursor-pointer transition-all rounded-lg border p-3"
                        :class="isSelected(result)
                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 ring-2 ring-blue-500'
                            : 'border-gray-200 dark:border-dark-400 hover:border-gray-300 dark:hover:border-dark-300 hover:bg-gray-50 dark:hover:bg-dark-575'"
                    >
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                <img
                                    v-if="result.artwork_url"
                                    :src="result.artwork_url"
                                    :alt="result.title"
                                    class="size-12 rounded object-cover bg-gray-100 dark:bg-dark-600"
                                    loading="lazy"
                                />
                                <div v-else class="size-12 rounded bg-gray-100 dark:bg-dark-600 flex items-center justify-center">
                                    <Icon name="audio-file" class="size-6 text-gray-400 dark:text-dark-300" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 dark:text-dark-100 truncate">
                                    {{ result.title }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-dark-150 truncate">
                                    {{ result.artist }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-dark-175 truncate">
                                    {{ result.album }}
                                </div>
                            </div>
                            <div class="flex flex-col gap-1.5 shrink-0">
                                <span
                                    v-if="result.spotify_url"
                                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs font-semibold text-[#1db954] bg-[#1db954]/15"
                                >
                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                                    </svg>
                                    Spotify
                                </span>
                                <span
                                    v-if="result.apple_music_url"
                                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs font-semibold text-[#fc3c44] bg-[#fc3c44]/15"
                                >
                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M23.994 6.124a9.23 9.23 0 00-.24-2.19c-.317-1.31-1.062-2.31-2.18-3.043a5.022 5.022 0 00-1.877-.726 10.496 10.496 0 00-1.564-.15c-.04-.003-.083-.01-.124-.013H5.99c-.152.01-.303.017-.455.026-.747.043-1.49.123-2.193.401-1.336.53-2.3 1.452-2.865 2.78-.192.448-.292.925-.363 1.408-.056.392-.088.785-.1 1.18 0 .032-.007.062-.01.093v12.223c.01.14.017.283.027.424.05.815.154 1.624.497 2.373.65 1.42 1.738 2.353 3.234 2.801.42.127.856.187 1.293.228.555.053 1.11.06 1.667.06h11.03c.525 0 1.048-.034 1.57-.1.823-.106 1.597-.35 2.296-.81.84-.553 1.472-1.287 1.88-2.208.186-.42.293-.87.37-1.324.113-.675.138-1.358.137-2.04-.002-3.8 0-7.595-.003-11.393zm-6.423 3.99v5.712c0 .417-.058.827-.244 1.206-.29.59-.76.962-1.388 1.14-.35.1-.706.157-1.07.173-.95.042-1.785-.455-2.107-1.322-.26-.7-.182-1.367.27-1.975.382-.516.9-.793 1.532-.897.332-.055.67-.09 1.004-.16.246-.05.42-.18.504-.427.02-.063.036-.128.037-.194l.002-3.68c0-.087-.03-.155-.118-.175-.064-.014-.13-.022-.195-.026L11.617 9.9l-.107-.014-.02 7.063c0 .11-.005.22-.018.33-.054.48-.175.94-.459 1.346-.378.542-.894.862-1.538 1.002-.374.08-.756.114-1.14.08-.862-.078-1.57-.46-1.968-1.25-.255-.51-.268-1.04-.147-1.575.274-1.21 1.257-1.867 2.313-1.96.38-.034.764-.022 1.14-.103.296-.064.45-.2.506-.49.01-.054.012-.108.012-.163l.001-8.07c0-.078.01-.155.026-.23.024-.118.095-.198.212-.226a1 1 0 01.193-.03l6.413-.678c.072-.008.146-.01.22-.009.18.003.29.1.315.28.01.07.013.14.013.21z"/>
                                    </svg>
                                    Apple
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Results -->
            <div v-else-if="hasSearched && !searching" class="text-center py-8 text-gray-500 dark:text-dark-175">
                <Icon name="audio-file" class="size-12 mx-auto mb-2 text-gray-300 dark:text-dark-400" />
                <p>No results found. Try a different search term.</p>
            </div>

            <!-- Footer -->
            <template #footer>
                <div class="flex justify-end gap-2 p-2">
                    <Button @click="closeModal" variant="ghost">Cancel</Button>
                    <Button
                        @click="confirmSelection"
                        variant="primary"
                        :disabled="!selectedResult || saving"
                        :loading="saving"
                        :text="saving ? 'Saving...' : 'Select Song'"
                    />
                </div>
            </template>
        </Modal>
    </div>
</template>

<script>
import { Fieldtype } from '@statamic/cms';
import {
    Alert,
    Button,
    Card,
    Icon,
    Input,
    Modal,
    publishContextKey,
} from '@statamic/cms/ui';

export default {
    mixins: [Fieldtype],

    components: {
        Alert,
        Button,
        Card,
        Icon,
        Input,
        Modal,
    },

    inject: {
        publishContext: {
            from: publishContextKey,
            default: null,
        },
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
        currentTitle() {
            const field = this.config.title_field || 'title';
            const values = this.publishContext?.values;
            // values might be a ref, so check for .value
            const actualValues = values?.value || values;
            return actualValues?.[field] || '';
        },

        currentArtist() {
            const field = this.config.artist_field || 'subtitle';
            const values = this.publishContext?.values;
            const actualValues = values?.value || values;
            return actualValues?.[field] || '';
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

            this.$nextTick(() => {
                this.$refs.searchInput?.$el?.focus();
            });
        },

        closeModal() {
            this.showModal = false;
        },

        isSelected(result) {
            return this.selectedResult === result;
        },

        async search() {
            if (!this.searchQuery.trim()) return;

            this.searching = true;
            this.error = null;
            this.apiErrors = {};
            this.results = [];
            this.selectedResult = null;

            try {
                const response = await this.$axios.get('/cp/song-search/search', {
                    params: { query: this.searchQuery },
                });

                if (response.data.success) {
                    this.results = response.data.results || [];

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
                if (this.config.auto_download_artwork && song.artwork_url) {
                    try {
                        const artworkResponse = await this.$axios.post('/cp/song-search/download-artwork', {
                            url: song.artwork_url,
                            title: song.title,
                            artist: song.artist,
                        });

                        if (artworkResponse.data.success) {
                            artworkAssetId = artworkResponse.data.asset_id;
                        }
                    } catch (artworkErr) {
                        console.warn('Failed to download artwork:', artworkErr);
                    }
                }

                // Update sibling fields using the publish container
                const titleField = this.config.title_field || 'title';
                const artistField = this.config.artist_field || 'subtitle';
                const appleMusicField = this.config.apple_music_url_field;
                const spotifyField = this.config.spotify_url_field;
                const artworkField = this.config.artwork_field;

                this.setPublishFieldValue(titleField, song.title);
                this.setPublishFieldValue(artistField, song.artist);

                if (song.apple_music_url && appleMusicField) {
                    this.setPublishFieldValue(appleMusicField, song.apple_music_url);
                }

                if (song.spotify_url && spotifyField) {
                    this.setPublishFieldValue(spotifyField, song.spotify_url);
                }

                if (artworkAssetId && artworkField) {
                    this.setPublishFieldValue(artworkField, [artworkAssetId]);
                }

                // Update our own field value
                this.$emit('update:value', {
                    searched: true,
                    last_search_query: this.searchQuery,
                });

                this.closeModal();
            } catch (err) {
                this.error = 'Failed to save selection: ' + (err.message || 'Unknown error');
                console.error('Save selection error:', err);
            } finally {
                this.saving = false;
            }
        },

        setPublishFieldValue(handle, value) {
            if (!handle) return;

            // Use the injected publish context's setFieldValue method
            if (this.publishContext?.setFieldValue) {
                this.publishContext.setFieldValue(handle, value);
            } else {
                console.warn('publishContext.setFieldValue not available for handle:', handle);
            }
        },
    },
};
</script>

<style scoped>
/* No max-width constraint - allow full width */
</style>
