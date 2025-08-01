<script setup>
import { computed, ref, watch, h } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";

import Avatar from "@/Components/ui/Avatar.vue";
import AvatarFallback from "@/Components/ui/AvatarFallback.vue";
import AvatarImage from "@/Components/ui/AvatarImage.vue";
import Button from "@/Components/ui/Button.vue";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Dialog from "@/Components/ui/Dialog.vue";
import DialogContent from "@/Components/ui/DialogContent.vue";
import DialogDescription from "@/Components/ui/DialogDescription.vue";
import DialogHeader from "@/Components/ui/DialogHeader.vue";
import DialogTitle from "@/Components/ui/DialogTitle.vue";
import Input from "@/Components/ui/Input.vue"; // If used in search filters
import {
    Calendar,
    Download,
    ExternalLink,
    MapPin,
    Music,
    X,
    Check,
} from "lucide-vue-next"; // Lucide icons

const props = defineProps({
    show: { type: Boolean },
    gig: { type: Object }, // The gig object for which we are generating a setlist
});

const emit = defineEmits(["close"]);

const isLoading = ref(false);
const ObtainingData = ref(false); // Used for "Pulling data from Spotify" spinner
const searchError = ref(null);
const setlistResults = ref([]);
const selectedSetlist = ref(null);

const form = useForm({
    gig_id: null,
    setlist_id: null,
    artist_name: "",
    venue_name: "",
    gig_date: "",
    setlist_url: "",
    sets: [],
});

/**
 * Watches for modal visibility and gig prop changes to initialize/reset the form and trigger search.
 */
watch(
    () => props.show,
    (newValue) => {
        if (newValue && props.gig) {
            form.reset();
            form.clearErrors();
            selectedSetlist.value = null;
            setlistResults.value = [];
            searchError.value = null;
            form.gig_id = props.gig.id;

            searchSetlists(props.gig.artist_band_name, props.gig.gig_date_time);
        } else if (!newValue) {
            form.reset();
            selectedSetlist.value = null;
            setlistResults.value = [];
            searchError.value = null;
        }
    },
    { immediate: true }
);

/**
 * Searches Setlist.fm for setlists based on artist and gig date.
 */
const searchSetlists = async (artistName, gigDateTime) => {
    isLoading.value = true;
    searchError.value = null;
    try {
        const gigDate = new Date(gigDateTime).toISOString().split("T")[0];
        const response = await axios.get(route("setlists.search"), {
            params: {
                artistName: artistName,
                gigDate: gigDate,
                gigId: props.gig.id,
            },
        });
        setlistResults.value = response.data.setlists;

        // --- NEW/MODIFIED AUTO-SELECTION LOGIC ---
        // Find if any result is an exact date match (flagged by backend)
        const exactMatchResult = setlistResults.value.find(
            (setlist) => setlist.is_exact_date_match
        );

        // If there's exactly one result AND it's an exact match, auto-select it.
        // Or if there's only one result period (and no error).
        if (setlistResults.value.length === 1 && exactMatchResult) {
            await selectSetlist(exactMatchResult); // Await selection to update UI state
        }
        // --- END NEW/MODIFIED ---
    } catch (error) {
        console.error("Error searching Setlist.fm:", error);
        searchError.value =
            error.response?.data?.error ||
            "Failed to search Setlist.fm. Please try again later.";
    } finally {
        isLoading.value = false;
    }
};

/**
 * Selects a setlist from search results and fetches its detailed data (including Spotify duration).
 */
const selectSetlist = async (setlist) => {
    selectedSetlist.value = setlist;

    form.setlist_id = setlist.setlist_id;
    form.artist_name = setlist.artist_name;
    form.venue_name = setlist.venue_name;
    form.gig_date = setlist.event_date.split(".").reverse().join("-");
    form.setlist_url = setlist.url;

    ObtainingData.value = true; // Activate "Pulling data from Spotify" spinner
    try {
        const setlistDetailResponse = await axios.get(
            route("setlists.details", { setlistId: setlist.setlist_id })
        );
        const fullSetlistData = setlistDetailResponse.data;

        let songDetailPromises = [];

        if (fullSetlistData.sets && fullSetlistData.sets.set) {
            fullSetlistData.sets.set.forEach((set) => {
                if (set.song) {
                    set.song
                        .filter(
                            (song) =>
                                !song.tape &&
                                song.name &&
                                song.name.trim().length > 0 &&
                                ![
                                    "Intro",
                                    "Outro",
                                    "Interlude",
                                    "Speech",
                                    "Taped",
                                    "Snippet",
                                ].some((excluded) =>
                                    song.name
                                        .toLowerCase()
                                        .includes(excluded.toLowerCase())
                                )
                        )
                        .forEach((song) => {
                            songDetailPromises.push(
                                axios
                                    .get(
                                        route("setlists.fetch-track-details"),
                                        {
                                            params: {
                                                trackName: song.name,
                                                artistName: setlist.artist_name,
                                            },
                                        }
                                    )
                                    .then((response) => {
                                        const details = response.data;
                                        return {
                                            name: song.name,
                                            spotify_id: details?.id || null,
                                            duration_ms:
                                                details?.duration_ms || null,
                                        };
                                    })
                                    .catch((error) => {
                                        console.warn(
                                            `Failed to get Spotify details for ${song.name}:`,
                                            error
                                        );
                                        return {
                                            name: song.name,
                                            spotify_id: null,
                                            duration_ms: null,
                                        };
                                    })
                            );
                        });
                }
            });
        }

        const resolvedSongDetails = await Promise.all(songDetailPromises);

        let currentSongDetailIndex = 0;
        let finalParsedSets = [];

        if (fullSetlistData.sets && fullSetlistData.sets.set) {
            fullSetlistData.sets.set.forEach((set) => {
                let songsWithDetails = [];
                if (set.song) {
                    set.song
                        .filter(
                            (song) =>
                                !song.tape &&
                                song.name &&
                                song.name.trim().length > 0 &&
                                ![
                                    "Intro",
                                    "Outro",
                                    "Interlude",
                                    "Speech",
                                    "Taped",
                                    "Snippet",
                                ].some((excluded) =>
                                    song.name
                                        .toLowerCase()
                                        .includes(excluded.toLowerCase())
                                )
                        )
                        .forEach((song) => {
                            const detail =
                                resolvedSongDetails[currentSongDetailIndex];
                            songsWithDetails.push({
                                name: song.name,
                                spotify_id: detail?.id || null,
                                duration_ms: detail?.duration_ms || null,
                            });
                            currentSongDetailIndex++;
                        });
                }
                if (songsWithDetails.length > 0) {
                    finalParsedSets.push({
                        name: set.name || "Main Set",
                        songs: songsWithDetails,
                    });
                }
            });
        }
        form.sets = finalParsedSets;
    } catch (error) {
        console.error(
            "Error fetching detailed setlist or Spotify details:",
            error
        );
        searchError.value =
            error.response?.data?.error ||
            "Failed to fetch detailed setlist data. Check console for details.";
    } finally {
        ObtainingData.value = false; // Deactivate spinner
    }
};

/**
 * Saves the selected setlist to the database.
 */
const saveSetlist = () => {
    form.post(route("setlists.store"), {
        onSuccess: () => {
            emit("close");
            form.reset();
            selectedSetlist.value = null;
            setlistResults.value = [];
        },
        onError: (errors) => {
            console.error("Error saving setlist:", errors);
        },
    });
};

/**
 * Navigates back to the setlist search results.
 */
const goBackToSearch = () => {
    selectedSetlist.value = null;
    searchError.value = null;
};

/**
 * Helper to format duration in milliseconds to MM:SS or HH:MM:SS.
 */
const formatDuration = (ms) => {
    if (ms === null || isNaN(ms)) return "N/A";
    const totalSeconds = Math.floor(ms / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${minutes}:${seconds < 10 ? "0" : ""}${seconds}`;
};
</script>

<template>
    <Dialog
        :open="show"
        @update:open="emit('close')"
        :max-width="'3xl'"
        v-if="show">
        <DialogContent class="bg-[#191919] border-gray-600 text-white">
            <DialogHeader>
                <DialogTitle
                    class="text-2xl font-semibold text-white text-center">
                    Generate Setlist for "{{ props.gig?.artist_band_name }}"
                </DialogTitle>
                <DialogDescription class="text-gray-400 text-center">
                    Search Setlist.fm for relevant setlists and save them.
                </DialogDescription>
            </DialogHeader>

            <!-- Content based on state -->
            <div class="p-4">
                <!-- Loading State (Searching Setlist.fm) -->
                <div v-if="isLoading" class="text-center py-8">
                    <div
                        class="animate-spin h-12 w-12 rounded-full border-b-2 border-emerald-500 mx-auto mb-4"></div>
                    <p class="text-gray-400">Searching Setlist.fm...</p>
                </div>

                <!-- NEW Loading State (Pulling data from Spotify) -->
                <div v-else-if="ObtainingData" class="text-center py-8">
                    <div
                        class="animate-spin h-12 w-12 rounded-full border-b-2 border-emerald-500 mx-auto mb-4"></div>
                    <p class="text-gray-400">
                        Pulling data from Spotify...
                        <br />
                        (This may take a moment)
                    </p>
                </div>

                <!-- Error State -->
                <div
                    v-else-if="searchError"
                    class="bg-red-500/10 text-red-400 border border-red-500/20 p-4 rounded-lg mb-4">
                    {{ searchError }}
                </div>

                <!-- Setlist Selection Step -->
                <div v-else-if="!selectedSetlist">
                    <p
                        v-if="setlistResults.length === 0"
                        class="text-center text-gray-400 py-8">
                        No setlists found for "{{
                            props.gig?.artist_band_name
                        }}".
                    </p>
                    <div v-else class="space-y-3 max-h-96 overflow-y-auto pr-2">
                        <h3 class="text-xl font-medium text-white mb-4">
                            Select a Setlist (Top 10):
                        </h3>
                        <Card
                            v-for="setlist in setlistResults"
                            :key="setlist.setlist_id"
                            @click="selectSetlist(setlist)"
                            class="bg-[#212121] border-gray-700 hover:bg-gray-700 transition-colors cursor-pointer">
                            <CardContent class="p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 flex items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                                            <Music class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="text-white font-semibold">
                                                {{ setlist.artist_name }}
                                            </p>
                                            <div
                                                class="text-gray-400 text-sm flex items-center gap-2">
                                                <MapPin class="h-3 w-3" />
                                                <span>
                                                    {{ setlist.venue_name }}
                                                </span>
                                                <Calendar
                                                    class="h-3 w-3 ml-2" />
                                                <span>
                                                    {{ setlist.event_date }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="border-gray-600 text-gray-300 hover:bg-gray-700 hover:text-white">
                                        Select
                                    </Button>
                                </div>
                                <a
                                    :href="setlist.url"
                                    target="_blank"
                                    class="mt-2 text-xs text-emerald-400 hover:underline flex items-center gap-1">
                                    View on Setlist.fm
                                    <ExternalLink class="h-3 w-3" />
                                </a>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Selected Setlist Details & Save Step -->
                <div v-else>
                    <Card class="bg-[#191919] border-gray-600">
                        <CardContent class="p-6">
                            <h3 class="text-xl font-semibold text-white mb-4">
                                Confirm & Save Setlist
                            </h3>
                            <div class="space-y-2">
                                <p class="text-lg font-bold text-emerald-400">
                                    {{ selectedSetlist.artist_name }}
                                </p>
                                <p class="text-gray-300">
                                    Venue: {{ selectedSetlist.venue_name }}
                                </p>
                                <p class="text-gray-300">
                                    Date: {{ selectedSetlist.event_date }}
                                </p>
                            </div>

                            <div class="mt-6">
                                <h4
                                    class="text-lg font-semibold text-white mb-3">
                                    Songs:
                                </h4>
                                <div v-if="form.sets.length > 0">
                                    <div
                                        v-for="(setObj, setIndex) in form.sets"
                                        :key="setObj.name"
                                        class="mb-4">
                                        <p
                                            class="text-gray-300 font-medium mb-1">
                                            {{ setObj.name || "Main Set" }}
                                        </p>
                                        <ul class="list-none space-y-1 pl-4">
                                            <li
                                                v-for="(
                                                    song, songIndex
                                                ) in setObj.songs"
                                                :key="song.name"
                                                class="flex items-center text-sm text-gray-400">
                                                <Music
                                                    class="h-4 w-4 mr-2 text-emerald-400" />
                                                {{ song.name }}
                                                <!-- NEW: Display Song Duration -->
                                                <span
                                                    v-if="song.duration_ms"
                                                    class="ml-auto text-gray-500">
                                                    {{
                                                        formatDuration(
                                                            song.duration_ms
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="ml-auto text-gray-500">
                                                    N/A
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <p v-else class="text-gray-500">
                                    No songs found for this setlist.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="mt-6 flex justify-between items-center">
                        <Button
                            variant="ghost"
                            @click="goBackToSearch"
                            class="text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200">
                            Back to Search
                        </Button>
                        <Button
                            @click="saveSetlist"
                            :class="{
                                'opacity-75 cursor-not-allowed':
                                    form.processing,
                            }"
                            :disabled="form.processing"
                            class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 px-6 shadow-lg shadow-emerald-500/25 transition-all duration-200">
                            Save Setlist
                        </Button>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
