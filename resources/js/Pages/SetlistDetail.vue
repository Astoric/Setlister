<script setup>
import { computed, ref, watch } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { ArrowLeftIcon, MusicalNoteIcon } from "@heroicons/vue/24/outline";
import { Head, Link, router, usePage } from "@inertiajs/vue3";

const props = defineProps({
    setlist: {
        type: Object,
    },
});

const flashSuccess = computed(() => usePage().props.flash?.success || null);
const flashError = computed(() => usePage().props.flash?.error || null);

const isGenerating = ref(false);
const playlistGenerated = ref(false);

/**
 * Watches flashSuccess to set playlistGenerated to true.
 */
watch(
    flashSuccess,
    (newValue) => {
        if (newValue) {
            playlistGenerated.value = true;
        }
    },
    { immediate: true }
);

/**
 * Formats the gig date.
 */
const formatDate = (dateString) => {
    const options = { year: "numeric", month: "long", day: "numeric" };

    return new Date(dateString).toLocaleString("en-US", options);
};

/**
 * Computes all songs from all sets for display.
 */
const allSongs = computed(() => {
    let songs = [];
    if (
        props.setlist &&
        props.setlist.sets &&
        Array.isArray(props.setlist.sets)
    ) {
        props.setlist.sets.forEach((set) => {
            if (set.songs && Array.isArray(set.songs)) {
                songs = songs.concat(set.songs);
            }
        });
    }

    return songs;
});

/**
 * Handles generating a Spotify playlist.
 */
const generatePlaylist = () => {
    isGenerating.value = true;
    playlistGenerated.value = false;

    const pagePropsFlash = usePage().props.flash;
    if (pagePropsFlash) {
        pagePropsFlash.success = null;
        pagePropsFlash.error = null;
    }

    router.post(route("setlists.generate-spotify-playlist", props.setlist.id), {
        onStart: () => {},
        onFinish: () => {
            isGenerating.value = false;
        },
        onSuccess: () => {
            playlistGenerated.value = true;
        },
        onError: (errors) => {
            playlistGenerated.value = false;
            console.error("Playlist generation error:", errors);
        },
    });
};
</script>

<template>
    <Head :title="`${setlist.artist_name} Setlist`" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Success message display -->
                <div
                    v-if="flashSuccess"
                    class="mb-4 rounded-lg bg-accent-500 px-4 py-3 shadow-md text-neutral-900"
                    v-html="flashSuccess"></div>
                <!-- Error message display -->
                <div
                    v-if="flashError"
                    class="mb-4 rounded-lg bg-red-500 px-4 py-3 shadow-md text-white">
                    {{ flashError }}
                </div>
                <!-- Back Button & Header -->
                <div class="mb-6 flex items-center justify-between">
                    <Link
                        :href="route('saved-setlists')"
                        class="flex items-center text-neutral-400 transition-colors hover:text-white">
                        <ArrowLeftIcon class="mr-2 h-5 w-5" />
                        Back to Saved Setlists
                    </Link>
                    <h2
                        class="ml-4 flex-grow text-center text-xl font-semibold leading-tight text-white">
                        {{ setlist.artist_name }} Setlist
                    </h2>
                    <!-- Generate Spotify Playlist Button -->
                    <PrimaryButton
                        @click="generatePlaylist()"
                        :disabled="isGenerating || playlistGenerated"
                        :class="{
                            'cursor-not-allowed opacity-75':
                                isGenerating || playlistGenerated,
                        }">
                        <span v-if="isGenerating">Generating playlist...</span>
                        <span v-else-if="playlistGenerated">Done!</span>
                        <span v-else>Generate Spotify Playlist</span>
                    </PrimaryButton>
                </div>

                <!-- Setlist Details Card -->
                <div
                    class="overflow-hidden rounded-lg bg-neutral-800 p-6 shadow-sm">
                    <!-- Artist Name -->
                    <h3 class="mb-2 text-3xl font-bold text-white">
                        {{ setlist.artist_name }}
                    </h3>
                    <!-- Venue & Date -->
                    <p class="mb-4 text-lg text-neutral-300">
                        at {{ setlist.venue_name }} on
                        {{ formatDate(setlist.gig_date) }}
                    </p>

                    <!-- Sets & Songs List -->
                    <div
                        v-if="setlist.sets && setlist.sets.length > 0"
                        class="space-y-6">
                        <div
                            v-for="(setObj, setIndex) in setlist.sets"
                            :key="setIndex"
                            class="border-t border-neutral-700 pt-4 first:border-t-0 first:pt-0">
                            <!-- Set Name -->
                            <h4
                                class="mb-3 text-xl font-semibold text-accent-500">
                                {{ setObj.name || "Main Set" }}
                            </h4>
                            <!-- Songs List -->
                            <ol
                                class="list-inside list-decimal space-y-1 text-neutral-300">
                                <li
                                    v-for="(song, songIndex) in setObj.songs"
                                    :key="`${setIndex}-${songIndex}`"
                                    class="flex items-start">
                                    <span class="mr-2">
                                        {{ songIndex + 1 }}.
                                    </span>
                                    <MusicalNoteIcon
                                        class="mr-2 mt-1 h-4 w-4 flex-shrink-0 text-neutral-400" />
                                    <span class="flex-grow">
                                        {{ song.name }}
                                    </span>
                                </li>
                            </ol>
                        </div>
                    </div>
                    <!-- No Set Information -->
                    <p v-else class="py-8 text-center text-neutral-400">
                        No set information available for this gig.
                    </p>

                    <!-- Setlist.fm Link -->
                    <div v-if="setlist.setlist_url" class="mt-6 text-right">
                        <a
                            :href="setlist.setlist_url"
                            target="_blank"
                            class="flex items-center justify-end text-sm text-accent-500 hover:underline">
                            View original on Setlist.fm
                            <svg
                                class="ml-1 h-4 w-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"></path>
                                <path
                                    d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
