<script setup>
import { computed, ref, watch, h } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

import Button from "@/Components/ui/Button.vue";
import Avatar from "@/Components/ui/Avatar.vue";
import AvatarImage from "@/Components/ui/AvatarImage.vue";
import AvatarFallback from "@/Components/ui/AvatarFallback.vue";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import Dialog from "@/Components/ui/Dialog.vue";
import DialogContent from "@/Components/ui/DialogContent.vue";
import DialogHeader from "@/Components/ui/DialogHeader.vue";
import DialogTitle from "@/Components/ui/DialogTitle.vue";
import DialogDescription from "@/Components/ui/DialogDescription.vue";

import {
    ArrowLeft,
    Calendar,
    Clock,
    Download,
    Heart,
    MapPin,
    Music,
    Play,
    Share2,
    Trash2,
    Check,
} from "lucide-vue-next";

const props = defineProps({
    setlist: {
        type: Object,
    },
});

const flashSuccess = computed(() => usePage().props.flash?.success || null);
const flashError = computed(() => usePage().props.flash?.error || null);

const isGenerating = ref(false);
const playlistGenerated = ref(false);

const confirmingSetlistDeletion = ref(false);

watch(
    flashSuccess,
    (newValue) => {
        if (newValue) {
            playlistGenerated.value = true;
        }
    },
    { immediate: true }
);

const formatDate = (dateString) => {
    const options = { year: "numeric", month: "long", day: "numeric" };
    return new Date(dateString).toLocaleString("en-US", options);
};

// NEW: Helper to format duration in milliseconds to MM:SS or HH:MM:SS.
const formatDuration = (ms) => {
    if (ms === null || isNaN(ms)) return "N/A";
    const totalSeconds = Math.floor(ms / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${minutes}:${seconds < 10 ? "0" : ""}${seconds}`;
};

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

const getAvatarFallback = (name) => {
    return name
        ? name
              .split(" ")
              .map((n) => n[0])
              .join("")
              .substring(0, 2)
              .toUpperCase()
        : "??";
};

const confirmSetlistDeletion = () => {
    confirmingSetlistDeletion.value = true;
};

const deleteSetlist = () => {
    router.delete(route("setlists.destroy", props.setlist.id), {
        onSuccess: () => {
            // Inertia::location handles redirect to saved-setlists
        },
        onError: () => {
            // Error handling for deletion
        },
        onFinish: () => {
            confirmingSetlistDeletion.value = false;
        },
    });
};

// NEW: Computed property to get all songs in order with a global index
const allSongsWithGlobalIndex = computed(() => {
    let globalIndex = 0;
    const songs = [];
    if (props.setlist && Array.isArray(props.setlist.sets)) {
        props.setlist.sets.forEach((setObj) => {
            if (setObj.songs && Array.isArray(setObj.songs)) {
                setObj.songs.forEach((song) => {
                    songs.push({
                        ...song,
                        globalIndex: globalIndex + 1, // Start from 1
                    });
                    globalIndex++;
                });
            }
        });
    }
    return songs;
});

// NEW: Helper to find a song by its name to use with the global index logic below
const findSongByReference = (setIndex, songIndexInSet) => {
    let currentGlobalIndex = 0;
    for (let sIdx = 0; sIdx < props.setlist.sets.length; sIdx++) {
        const currentSet = props.setlist.sets[sIdx];
        if (currentSet.songs && Array.isArray(currentSet.songs)) {
            for (
                let songIdx = 0;
                songIdx < currentSet.songs.length;
                songIdx++
            ) {
                if (sIdx === setIndex && songIdx === songIndexInSet) {
                    return currentGlobalIndex + 1; // Found the specific song's global index
                }
                currentGlobalIndex++;
            }
        }
    }
    return -1; // Not found
};
</script>

<template>
    <Head :title="`${setlist.artist_name} Setlist`" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                <div
                    v-if="flashSuccess"
                    class="mb-4 rounded-lg bg-accent-500 px-4 py-3 shadow-md text-neutral-900"
                    v-html="flashSuccess"></div>
                <div
                    v-if="flashError"
                    class="mb-4 rounded-lg bg-red-500 px-4 py-3 shadow-md text-white">
                    {{ flashError }}
                </div>

                <!-- Setlist Header -->
                <div class="border-b border-gray-700 p-6">
                    <div class="mb-6 flex items-center justify-between">
                        <Button
                            variant="ghost"
                            as-child
                            class="gap-2 text-gray-400 transition-all duration-200 hover:bg-gray-700 hover:text-white">
                            <Link
                                :href="route('saved-setlists')"
                                class="flex items-center gap-2">
                                <ArrowLeft class="h-4 w-4" />
                                Back to Saved Setlists
                            </Link>
                        </Button>

                        <div class="flex items-center gap-3">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200">
                                <Heart class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200">
                                <Share2 class="h-4 w-4" />
                            </Button>
                            <!-- Delete Setlist Button -->
                            <Button
                                @click="confirmSetlistDeletion"
                                class="bg-red-600 hover:bg-red-700 p-2 text-white transition-all duration-200 rounded-lg"
                                title="Delete Setlist">
                                <Trash2 class="h-5 w-5" />
                            </Button>
                            <Button
                                @click="generatePlaylist()"
                                class="text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 px-6 shadow-lg shadow-emerald-500/25 transition-all duration-200"
                                :disabled="isGenerating || playlistGenerated"
                                :class="{
                                    'opacity-75 cursor-not-allowed':
                                        isGenerating || playlistGenerated,
                                }">
                                <Download
                                    v-if="isGenerating || !playlistGenerated"
                                    class="mr-2 h-4 w-4" />
                                <Check v-else class="mr-2 h-4 w-4" />
                                <span v-if="isGenerating">
                                    Generating playlist...
                                </span>
                                <span v-else-if="playlistGenerated">Done!</span>
                                <span v-else>Generate Spotify Playlist</span>
                            </Button>
                        </div>
                    </div>

                    <div class="flex items-center gap-6">
                        <Avatar class="h-20 w-20 ring-2 ring-emerald-500/30">
                            <AvatarImage
                                v-if="setlist.gig?.artist_image_url"
                                :src="setlist.gig.artist_image_url"
                                :alt="setlist.gig.artist_name" />
                            <AvatarFallback
                                v-else
                                class="bg-gradient-to-r from-gray-700 to-gray-600 text-2xl text-white">
                                {{ getAvatarFallback(setlist.artist_name) }}
                            </AvatarFallback>
                        </Avatar>

                        <div>
                            <h1
                                class="mb-2 bg-gradient-to-r from-white to-gray-300 bg-clip-text text-4xl font-bold text-transparent">
                                {{ setlist.artist_name }}
                            </h1>
                            <div
                                class="mb-3 flex items-center gap-4 text-gray-400">
                                <div class="flex items-center gap-2">
                                    <MapPin class="h-4 w-4" />
                                    <span>{{ setlist.venue_name }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4" />
                                    <span>
                                        {{ formatDate(setlist.gig_date) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Clock class="h-4 w-4" />
                                    <span>
                                        {{ setlist.total_duration_display }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Setlist Content -->
                <div class="p-6">
                    <div class="max-w-4xl">
                        <!-- Main Set -->
                        <div class="mb-8">
                            <h2
                                class="mb-6 text-2xl font-semibold text-emerald-400">
                                Main Set
                            </h2>
                            <Card class="bg-[#191919] border-gray-600">
                                <CardContent class="p-0">
                                    <div
                                        v-for="(
                                            setObj, setIndex
                                        ) in setlist.sets"
                                        :key="setIndex">
                                        <div
                                            v-if="
                                                setObj.name &&
                                                setObj.name !== 'Main Set'
                                            "
                                            class="px-4 py-2 text-sm font-semibold text-gray-400 border-b border-gray-700 bg-[#212121]">
                                            {{ setObj.name }}
                                        </div>
                                        <div
                                            v-for="(
                                                song, songIndex
                                            ) in setObj.songs"
                                            :key="`${setIndex}-${songIndex}`"
                                            class="group flex items-center gap-4 p-4 transition-colors hover:bg-gray-800/50"
                                            :class="{
                                                'border-b border-gray-700':
                                                    setIndex <
                                                        setlist.sets.length -
                                                            1 ||
                                                    songIndex <
                                                        setObj.songs.length - 1,
                                            }">
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/20 text-sm font-semibold text-emerald-400">
                                                <!-- MODIFIED: Use calculated global index -->
                                                {{
                                                    findSongByReference(
                                                        setIndex,
                                                        songIndex
                                                    )
                                                }}
                                            </div>
                                            <div class="flex-1">
                                                <p
                                                    class="text-white transition-colors group-hover:text-emerald-400">
                                                    {{ song.name }}
                                                </p>
                                            </div>
                                            <!-- Song Duration (from spotify_track_details on generation) -->
                                            <div
                                                class="text-sm text-gray-500 transition-colors group-hover:text-gray-400">
                                                {{
                                                    formatDuration(
                                                        song.duration_ms
                                                    )
                                                }}
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="opacity-0 text-gray-400 transition-opacity group-hover:opacity-100 hover:text-white">
                                                <Play class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Original Setlist.fm Link -->
                        <div
                            v-if="setlist.setlist_url"
                            class="mt-8 text-right text-sm text-gray-400">
                            <a
                                :href="setlist.setlist_url"
                                target="_blank"
                                class="hover:underline">
                                View original on Setlist.fm
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Delete Confirmation Modal -->
    <Dialog
        :open="confirmingSetlistDeletion"
        @update:open="confirmingSetlistDeletion = $event"
        :max-width="'sm'"
        v-if="confirmingSetlistDeletion">
        <DialogContent class="bg-[#191919] border-gray-700 text-white max-w-sm">
            <DialogHeader>
                <DialogTitle class="text-white">
                    Are you sure you want to delete this setlist?
                </DialogTitle>
                <DialogDescription class="text-gray-400">
                    This action cannot be undone. This setlist will be
                    permanently removed.
                </DialogDescription>
            </DialogHeader>
            <div class="flex justify-end gap-3">
                <Button
                    variant="ghost"
                    @click="confirmingSetlistDeletion = false"
                    class="text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200">
                    Cancel
                </Button>
                <Button
                    @click="deleteSetlist"
                    :class="{ 'opacity-75 cursor-not-allowed': isGenerating }"
                    :disabled="isGenerating"
                    variant="destructive"
                    class="bg-red-600 hover:bg-red-700 text-white transition-all duration-200">
                    Delete
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
