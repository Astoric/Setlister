<script setup>
import { ref, watch, computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { XMarkIcon, MusicalNoteIcon } from "@heroicons/vue/24/outline"; // MusicalNoteIcon for songs

const props = defineProps({
    show: Boolean,
    gig: Object, // The gig object for which we are generating a setlist
});

const emit = defineEmits(["close"]);

const isLoading = ref(false);
const searchError = ref(null);
const setlistResults = ref([]); // Stores array of results from Setlist.fm
const selectedSetlist = ref(null); // Stores the setlist selected by the user for saving

const form = useForm({
    gig_id: null,
    setlist_id: null,
    artist_name: "",
    venue_name: "",
    gig_date: "",
    setlist_url: "",
    sets: [], // This will eventually contain structured song data from Spotify
});

// Watch for modal visibility and gig prop changes
watch(
    () => props.show,
    (newValue) => {
        if (newValue && props.gig) {
            // Reset form and clear data when modal opens for a new gig
            form.reset();
            form.clearErrors();
            selectedSetlist.value = null;
            setlistResults.value = [];
            searchError.value = null;
            form.gig_id = props.gig.id; // Set gig_id for the form submission

            // Automatically search for setlists based on gig artist and date
            searchSetlists(props.gig.artist_band_name, props.gig.gig_date_time);
        } else if (!newValue) {
            // Reset everything when modal closes
            form.reset();
            selectedSetlist.value = null;
            setlistResults.value = [];
            searchError.value = null;
        }
    }
);

const searchSetlists = async (artistName, gigDateTime) => {
    isLoading.value = true;
    searchError.value = null;
    try {
        const gigDate = new Date(gigDateTime).toISOString().split("T")[0]; // Format to YYYY-MM-DD
        const response = await axios.get(route("setlists.search"), {
            params: {
                artistName: artistName,
                gigDate: gigDate, // Send the date for exact match attempt
                gigId: props.gig.id, // Pass gigId for validation on backend
            },
        });
        setlistResults.value = response.data.setlists;
    } catch (error) {
        console.error("Error searching Setlist.fm:", error);
        searchError.value =
            error.response?.data?.error ||
            "Failed to search Setlist.fm. Check console for details.";
    } finally {
        isLoading.value = false;
    }
};

const selectSetlist = async (setlist) => {
    selectedSetlist.value = setlist;

    // Populate form data from selected setlist
    form.setlist_id = setlist.setlist_id;
    form.artist_name = setlist.artist_name;
    form.venue_name = setlist.venue_name;
    form.gig_date = setlist.event_date.split(".").reverse().join("-");
    form.setlist_url = setlist.url;

    isLoading.value = true;
    try {
        // --- MODIFIED CODE BLOCK START ---
        // Call your backend route to fetch detailed setlist data from Setlist.fm (proxied)
        const setlistDetailResponse = await axios.get(
            route("setlists.details", { setlistId: setlist.setlist_id })
        );
        const fullSetlistData = setlistDetailResponse.data;
        // --- MODIFIED CODE BLOCK END ---

        let parsedSets = [];
        if (fullSetlistData.sets && fullSetlistData.sets.set) {
            fullSetlistData.sets.set.forEach((set) => {
                let songsInSet = [];
                if (set.song) {
                    songsInSet = set.song
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
                        .map((song) => ({
                            name: song.name,
                            // spotify_id: null,
                            // duration_ms: null,
                        }));
                }
                // --- MODIFIED CONDITION HERE ---
                // Only push the set if it has songs AFTER filtering
                if (songsInSet.length > 0) {
                    parsedSets.push({
                        name: set.name || "Main Set", // Use set.name if available, otherwise 'Main Set'
                        songs: songsInSet,
                    });
                }
            });
        }
        form.sets = parsedSets;

        // --- HERE YOU WOULD INTEGRATE SPOTIFY API (UNCHANGED COMMENTS) ---
        // Iterate through form.sets.songs and call Spotify Search API for each song
        // You'll need an access token from Spotify (OAuth 2.0 flow is complex for this)
        // For now, we're just parsing Setlist.fm data
        // Example: await fetchSpotifyTrackData(form.sets.songs);
    } catch (error) {
        console.error(
            "Error fetching detailed setlist or Spotify data:",
            error
        );
        searchError.value =
            error.response?.data?.error ||
            "Failed to fetch detailed setlist data. Check browser console and Laravel logs.";
    } finally {
        isLoading.value = false;
    }
};

const saveSetlist = () => {
    form.post(route("setlists.store"), {
        onSuccess: () => {
            emit("close"); // Close modal on successful submission
            form.reset();
            selectedSetlist.value = null;
            setlistResults.value = [];
        },
        onError: (errors) => {
            console.error("Error saving setlist:", errors);
            // Errors are automatically displayed by InputError components if needed
        },
    });
};

const goBackToSearch = () => {
    selectedSetlist.value = null;
    searchError.value = null; // Clear error
    // Re-run search if desired, or let it happen via watch on 'show'
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" :maxWidth="'3xl'">
        <div class="p-6 bg-neutral-900 text-white rounded-lg relative">
            <h2 class="text-2xl font-semibold mb-6">
                Generate Setlist for "{{ props.gig?.artist_band_name }}"
            </h2>

            <!-- Close button -->
            <button
                @click="emit('close')"
                class="absolute top-4 right-4 text-neutral-400 hover:text-white transition-colors">
                <XMarkIcon class="h-6 w-6" />
            </button>

            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
                <div
                    class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent-500 mx-auto mb-4"></div>
                <p class="text-neutral-400">Searching Setlist.fm...</p>
            </div>

            <!-- Error State -->
            <div
                v-else-if="searchError"
                class="bg-red-800 text-red-100 p-4 rounded-lg mb-4">
                {{ searchError }}
            </div>

            <!-- Setlist Selection Step -->
            <div v-else-if="!selectedSetlist">
                <p
                    v-if="setlistResults.length === 0"
                    class="text-center text-neutral-400 py-8">
                    No setlists found for "{{ props.gig?.artist_band_name }}" on
                    Setlist.fm.
                </p>
                <div v-else>
                    <h3 class="text-xl font-medium mb-4">
                        Select a Setlist (Top 10):
                    </h3>
                    <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                        <div
                            v-for="setlist in setlistResults"
                            :key="setlist.setlist_id"
                            @click="selectSetlist(setlist)"
                            class="bg-neutral-800 p-4 rounded-lg cursor-pointer hover:bg-neutral-700 transition-colors flex justify-between items-center">
                            <div>
                                <p class="font-semibold">
                                    {{ setlist.artist_name }} at
                                    {{ setlist.venue_name }}
                                </p>
                                <p class="text-neutral-400 text-sm">
                                    {{ setlist.event_date }} in
                                    {{ setlist.city_name }}
                                </p>
                                <a
                                    :href="setlist.url"
                                    target="_blank"
                                    class="text-accent-500 hover:underline text-sm mt-1 block">
                                    View on Setlist.fm
                                </a>
                            </div>
                            <PrimaryButton>Select</PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Setlist Details & Save Step -->
            <div v-else>
                <h3 class="text-xl font-semibold mb-4">
                    Confirm & Save Setlist
                </h3>
                <div class="bg-neutral-800 p-6 rounded-lg mb-6">
                    <p class="text-lg font-bold text-accent-500 mb-2">
                        {{ selectedSetlist.artist_name }}
                    </p>
                    <p class="text-neutral-300 mb-1">
                        Venue: {{ selectedSetlist.venue_name }}
                    </p>
                    <p class="text-neutral-300 mb-4">
                        Date: {{ selectedSetlist.event_date }}
                    </p>

                    <h4 class="text-lg font-semibold mb-3">Songs:</h4>
                    <div v-if="form.sets.length > 0">
                        <div
                            v-for="setObj in form.sets"
                            :key="setObj.name"
                            class="mb-4">
                            <p class="font-medium text-neutral-200">
                                {{ setObj.name || "Main Set" }}
                            </p>
                            <ul
                                class="list-disc list-inside text-neutral-400 ml-4">
                                <li
                                    v-for="song in setObj.songs"
                                    :key="song.name"
                                    class="flex items-center text-sm my-1">
                                    <MusicalNoteIcon
                                        class="w-4 h-4 mr-2 text-accent-500" />
                                    {{ song.name }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p v-else class="text-neutral-400">
                        No songs found for this setlist. (Might be an
                        instrumental set or API parsing issue)
                    </p>
                </div>

                <div class="flex justify-between items-center mt-6">
                    <PrimaryButton
                        @click="goBackToSearch"
                        class="bg-neutral-700 hover:bg-neutral-600 text-white">
                        Back to Search
                    </PrimaryButton>
                    <PrimaryButton
                        @click="saveSetlist"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing">
                        Save Setlist
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
