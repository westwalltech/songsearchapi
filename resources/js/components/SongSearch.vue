<template>
    <div class="song-search-fieldtype">
        <!-- Search Button -->
        <Button @click="openModal" variant="primary" icon="magnifying-glass">
            Search for Song
        </Button>

        <!-- Current Selection Display -->
        <Card v-if="currentTitle" class="mt-3">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">
                Currently selected
            </div>
            <div class="font-semibold text-gray-900 dark:text-white">{{ currentTitle }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-300">{{ currentArtist }}</div>
        </Card>

        <!-- Search Modal (Centered) -->
        <Modal
            v-model:open="showModal"
            title="Search for Song"
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
                <div class="text-sm text-gray-600 dark:text-dark-150 mb-3">
                    {{ results.length }} result(s) found
                </div>
                <div class="max-h-[350px] overflow-y-auto">
                    <table class="data-table">
                        <tbody>
                            <tr
                                v-for="(result, index) in results"
                                :key="index"
                                @click="selectSong(result)"
                                class="cursor-pointer"
                                :class="{ 'bg-blue-100 dark:bg-blue-900/40': isSelected(result) }"
                            >
                                <td class="w-16">
                                    <div class="flex items-center justify-center">
                                        <img
                                            v-if="result.artwork_url"
                                            :src="result.artwork_url"
                                            :alt="result.title"
                                            class="size-10 rounded object-cover"
                                            loading="lazy"
                                        />
                                        <div v-else class="size-10 rounded bg-gray-200 dark:bg-dark-600 flex items-center justify-center">
                                            <span class="text-gray-400 text-lg">♪</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-medium">{{ result.title }}</div>
                                    <div class="text-gray text-xs">{{ result.artist }}</div>
                                    <div class="text-gray text-2xs">{{ result.album }}</div>
                                </td>
                                <td class="w-24 text-right">
                                    <div class="flex flex-col items-end gap-1">
                                        <Badge v-if="result.spotify_url" color="green">Spotify</Badge>
                                        <Badge v-if="result.apple_music_url" color="red">Apple</Badge>
                                    </div>
                                </td>
                                <td class="w-10 text-center">
                                    <span
                                        v-if="isSelected(result)"
                                        class="text-lg text-blue-500 font-bold"
                                    >✓</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
    Badge,
    Button,
    Card,
    Input,
    Modal,
    publishContextKey,
} from '@statamic/cms/ui';

export default {
    mixins: [Fieldtype],

    components: {
        Alert,
        Badge,
        Button,
        Card,
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
