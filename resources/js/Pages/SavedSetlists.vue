<script setup>
import { computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { MusicalNoteIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    setlists: {
        type: Array,
    },
});

/**
 * Checks if there are any saved setlists.
 */
const hasSetlists = computed(() => props.setlists && props.setlists.length > 0);

/**
 * Accesses flash success messages from the page props.
 */
const flashSuccess = computed(() => usePage().props.flash?.success || null);

/**
 * Formats the gig date.
 */
const formatDate = (dateString) => {
    const options = { year: "numeric", month: "long", day: "numeric" };

    return new Date(dateString).toLocaleString("en-US", options);
};

/**
 * Truncates the song list for summary display.
 */
const truncateSongs = (sets) => {
    if (!sets || sets.length === 0) {
        return "No songs listed.";
    }
    let allSongs = [];
    sets.forEach((set) => {
        if (Array.isArray(set.songs)) {
            allSongs = allSongs.concat(set.songs.map((song) => song.name));
        }
    });
    if (allSongs.length === 0) {
        return "No songs listed.";
    }

    const maxDisplay = 5;
    const truncated = allSongs.slice(0, maxDisplay).join(", ");

    return allSongs.length > maxDisplay ? `${truncated}...` : truncated;
};
</script>

<template>
    <Head title="Saved Setlists" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Success message display -->
                <div
                    v-if="flashSuccess"
                    class="mb-4 rounded-lg bg-accent-500 px-4 py-3 shadow-md text-neutral-900">
                    {{ flashSuccess }}
                </div>

                <!-- Header -->
                <div class="mb-6 flex justify-between items-center">
                    <h2 class="text-xl font-semibold leading-tight text-white">
                        Saved Setlists
                    </h2>
                </div>

                <!-- Conditional Rendering: If no setlists present vs. Setlists present -->
                <div
                    v-if="!hasSetlists"
                    class="rounded-lg bg-neutral-800 p-6 text-center shadow-sm overflow-hidden">
                    <h3 class="mb-4 text-xl font-semibold text-white">
                        No Setlists Saved Yet!
                    </h3>
                    <p class="text-neutral-400">
                        Generate a setlist from an upcoming or past gig to see
                        it here.
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <!-- List of Setlists -->
                    <Link
                        v-for="setlist in setlists"
                        :key="setlist.id"
                        :href="route('setlists.show', setlist.id)"
                        class="block flex cursor-pointer items-center justify-between overflow-hidden rounded-lg bg-neutral-800 p-6 shadow-sm transition-colors duration-200 hover:bg-neutral-700">
                        <div>
                            <!-- Artist Name -->
                            <h3 class="mb-1 text-xl font-semibold text-white">
                                {{ setlist.artist_name }}
                            </h3>
                            <!-- Venue & Date -->
                            <p class="mb-2 text-sm text-neutral-400">
                                {{ setlist.venue_name }} -
                                {{ formatDate(setlist.gig_date) }}
                            </p>
                            <!-- Truncated Songs Summary -->
                            <div
                                class="flex items-center text-sm text-neutral-300">
                                <MusicalNoteIcon class="mr-1 h-4 w-4" />
                                <span class="max-w-lg truncate">
                                    {{ truncateSongs(setlist.sets) }}
                                </span>
                            </div>
                            <!-- Setlist.fm Link -->
                            <a
                                v-if="setlist.setlist_url"
                                :href="setlist.setlist_url"
                                target="_blank"
                                @click.stop
                                class="mt-2 block text-xs text-accent-500 hover:underline">
                                View on Setlist.fm
                            </a>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
