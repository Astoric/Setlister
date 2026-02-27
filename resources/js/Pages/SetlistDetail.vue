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
    gig: {
        // NOW RECEIVES GIG OBJECT INSTEAD OF SETLIST
        type: Object,
        required: true,
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

    router.post(
        route("setlists.generate-spotify-playlist", props.gig.id),
        {},
        {
            // PASS GIG ID
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
        }
    );
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
    // This will send a request to clear the setlist data on the gig
    router.patch(
        route("gigs.update", props.gig.id),
        {
            setlist_id_setlistfm: null,
            setlist_url: null,
            sets: [],
            // Re-include other gig fields if they are required by the Gig update validation
            // Otherwise, Laravel's partial update will just update these specific fields.
            artist_band_name: props.gig.artist_band_name,
            venue: props.gig.venue,
            gig_date_time: props.gig.gig_date_time,
            support_acts: props.gig.support_acts,
            people_attending: props.gig.people_attending,
        },
        {
            onSuccess: () => {
                // If successful, redirect to appropriate gig list
                if (new Date(props.gig.gig_date_time) < new Date()) {
                    router.visit(route("past-gigs"));
                } else {
                    router.visit(route("dashboard"));
                }
            },
            onError: (errors) => {
                console.error("Error deleting setlist data:", errors);
            },
            onFinish: () => {
                confirmingSetlistDeletion.value = false;
            },
        }
    );
};

const allSongsWithGlobalIndex = computed(() => {
    let globalIndex = 0;
    const songs = [];
    if (props.gig && Array.isArray(props.gig.sets)) {
        // Use props.gig.sets
        props.gig.sets.forEach((setObj) => {
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

const findSongByReference = (setIndex, songIndexInSet) => {
    let currentGlobalIndex = 0;
    if (!props.gig || !Array.isArray(props.gig.sets)) return -1;

    for (let sIdx = 0; sIdx < props.gig.sets.length; sIdx++) {
        const currentSet = props.gig.sets[sIdx];
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

// Calculate total duration for display
const totalDurationDisplay = computed(() => {
    let totalDurationMs = 0;
    if (props.gig && Array.isArray(props.gig.sets)) {
        props.gig.sets.forEach((set) => {
            if (set.songs && Array.isArray(set.songs)) {
                set.songs.forEach((song) => {
                    if (
                        song.duration_ms &&
                        typeof song.duration_ms === "number"
                    ) {
                        totalDurationMs += song.duration_ms;
                    }
                });
            }
        });
    }

    const totalSeconds = Math.floor(totalDurationMs / 1000);
    const totalMinutes = Math.floor(totalSeconds / 60);
    const totalHours = Math.floor(totalMinutes / 60);
    const remainingMinutes = totalMinutes % 60;

    return `${totalHours}h ${remainingMinutes}m`;
});

// Last modified display (assuming updated_at is on gig now)
const lastModifiedDisplay = computed(() => {
    return props.gig.updated_at
        ? new Date(props.gig.updated_at).toLocaleDateString()
        : "N/A"; // Or use diffForHumans if Carbon is passed
});
</script>

<template>
    <Head :title="`${gig.artist_band_name} Setlist`" />

    <AuthenticatedLayout>
        <div class="py-4 sm:py-6">
            <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
                <div
                    v-if="flashSuccess"
                    class="mb-4 rounded-lg bg-accent-500 px-4 py-3 shadow-md text-neutral-900"
                    v-html="flashSuccess"></div>
                <div
                    v-if="flashError"
                    class="mb-4 rounded-lg bg-red-500 px-4 py-3 shadow-md text-white">
                    {{ flashError }}
                </div>

                <div class="border-b border-gray-700 p-4 sm:p-6">
                    <div
                        class="mb-4 sm:mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sm:gap-0">
                        <Button
                            variant="ghost"
                            as-child
                            class="gap-2 text-gray-400 transition-all duration-200 hover:bg-gray-700 hover:text-white w-full sm:w-auto">
                            <Link
                                :href="
                                    gig.gig_date_time &&
                                    new Date(gig.gig_date_time) < new Date()
                                        ? route('past-gigs')
                                        : route('dashboard')
                                "
                                class="flex items-center gap-2">
                                <ArrowLeft class="h-4 w-4" />
                                Back to Gigs
                            </Link>
                        </Button>

                        <div
                            class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto justify-center sm:justify-end mt-4 sm:mt-0">
                            <Button
                                @click="confirmSetlistDeletion"
                                class="bg-red-600 hover:bg-red-700 p-2 text-white transition-all duration-200 rounded-lg"
                                title="Clear Setlist">
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

                    <div
                        class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 mt-4 sm:mt-0">
                        <Avatar
                            class="h-16 w-16 sm:h-20 sm:w-20 ring-2 ring-emerald-500/30">
                            <AvatarImage
                                v-if="gig.artist_image_url"
                                :src="gig.artist_image_url"
                                :alt="gig.artist_band_name" />
                            <AvatarFallback
                                v-else
                                class="bg-gradient-to-r from-gray-700 to-gray-600 text-2xl text-white">
                                {{ getAvatarFallback(gig.artist_band_name) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="w-full sm:w-auto">
                            <h1
                                class="mb-1 sm:mb-2 bg-gradient-to-r from-white to-gray-300 bg-clip-text text-2xl sm:text-4xl font-bold text-transparent">
                                {{ gig.artist_band_name }}
                            </h1>
                            <div
                                class="mb-2 sm:mb-3 flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 text-xs sm:text-base text-gray-400">
                                <div class="flex items-center gap-1 sm:gap-2">
                                    <MapPin class="h-4 w-4" />
                                    <span>{{ gig.venue }}</span>
                                </div>
                                <div class="flex items-center gap-1 sm:gap-2">
                                    <Calendar class="h-4 w-4" />
                                    <span>
                                        {{ formatDate(gig.gig_date_time) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 sm:gap-2">
                                    <Clock class="h-4 w-4" />
                                    <span>
                                        {{ totalDurationDisplay }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    <div class="max-w-4xl mx-auto">
                        <div class="mb-6 sm:mb-8">
                            <h2
                                class="mb-4 sm:mb-6 text-xl sm:text-2xl font-semibold text-emerald-400">
                                Main Set
                            </h2>
                            <Card class="bg-[#191919] border-gray-600">
                                <CardContent class="p-0">
                                    <div
                                        v-for="(setObj, setIndex) in gig.sets"
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
                                            class="group flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 p-3 sm:p-4 transition-colors hover:bg-gray-800/50"
                                            :class="{
                                                'border-b border-gray-700':
                                                    setIndex <
                                                        gig.sets.length - 1 ||
                                                    songIndex <
                                                        setObj.songs.length - 1,
                                            }">
                                            <div
                                                class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-emerald-500/20 text-xs sm:text-sm font-semibold text-emerald-400">
                                                {{
                                                    findSongByReference(
                                                        setIndex,
                                                        songIndex
                                                    )
                                                }}
                                            </div>
                                            <div
                                                class="flex-1 w-full sm:w-auto">
                                                <p
                                                    class="text-white transition-colors group-hover:text-emerald-400 text-xs sm:text-base">
                                                    {{ song.name }}
                                                </p>
                                            </div>
                                            <div
                                                class="text-xs sm:text-sm text-gray-500 transition-colors group-hover:text-gray-400">
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

                        <div
                            v-if="gig.setlist_url"
                            class="mt-6 sm:mt-8 text-right text-xs sm:text-sm text-gray-400">
                            <a
                                :href="gig.setlist_url"
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

    <Dialog
        :open="confirmingSetlistDeletion"
        @update:open="confirmingSetlistDeletion = $event"
        :max-width="'sm'"
        v-if="confirmingSetlistDeletion">
        <DialogContent class="bg-[#191919] border-gray-700 text-white max-w-sm">
            <DialogHeader>
                <DialogTitle class="text-white">
                    Are you sure you want to remove the setlist from this gig?
                </DialogTitle>
                <DialogDescription class="text-gray-400">
                    This will clear the setlist data for this gig. You can
                    generate it again later.
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
                    Remove Setlist
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
