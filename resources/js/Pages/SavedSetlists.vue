<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

// New UI Components
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Avatar from "@/Components/ui/Avatar.vue";
import AvatarImage from "@/Components/ui/AvatarImage.vue";
import AvatarFallback from "@/Components/ui/AvatarFallback.vue";
import Badge from "@/Components/ui/Badge.vue";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import Dialog from "@/Components/ui/Dialog.vue"; // For modals if any
import DialogContent from "@/Components/ui/DialogContent.vue";
import DialogHeader from "@/Components/ui/DialogHeader.vue";
import DialogTitle from "@/Components/ui/DialogTitle.vue";
import DialogDescription from "@/Components/ui/DialogDescription.vue";

// Lucide Icons (as used in App.jsx)
import {
    Calendar,
    Clock,
    Edit,
    Eye,
    MapPin,
    Music,
    Play,
} from "lucide-vue-next";

const props = defineProps({
    setlists: {
        type: Array, // Expect an array of setlist objects
    },
});

// Processed setlists (ensures arrays are proper JS arrays)
const processedSetlists = ref([]);
watch(
    () => props.setlists,
    (newSetlists) => {
        if (newSetlists) {
            processedSetlists.value = newSetlists.map((setlist) => {
                // Ensure 'sets' and 'songs' are arrays.
                // Calculate total songs and duration similar to App.jsx example.
                let totalSongs = 0;
                let totalDurationMinutes = 0; // Assuming song objects might have duration later

                setlist.sets.forEach((set) => {
                    if (Array.isArray(set.songs)) {
                        totalSongs += set.songs.length;
                        // If songs had duration property, could sum here
                    }
                });

                // Placeholder for duration and lastModified as they are not in DB currently
                const duration = "N/A"; // Or retrieve from DB if you add it
                const lastModified = "N/A"; // Or retrieve from DB (updated_at)

                return {
                    ...setlist,
                    totalSongs: totalSongs,
                    duration: duration,
                    lastModified: lastModified,
                    // Avatar fallback letter (e.g., 'ST' for Sleep Token)
                    avatarFallback: setlist.artist_name
                        ? setlist.artist_name
                              .split(" ")
                              .map((n) => n[0])
                              .join("")
                              .substring(0, 2)
                              .toUpperCase()
                        : "??",
                };
            });
        } else {
            processedSetlists.value = [];
        }
    },
    { immediate: true }
);

// General UI state
const hasSetlists = computed(
    () => processedSetlists.value && processedSetlists.value.length > 0
);
const flashSuccess = computed(() => usePage().props.flash?.success || null);

// --- Methods ---
const formatGigDate = (dateString) => {
    // This is for gig_date in setlists table which is 'date' type, not datetime-local
    const options = { year: "numeric", month: "long", day: "numeric" };
    return new Date(dateString).toLocaleString("en-US", options);
};

const truncateSongs = (sets) => {
    if (!sets || sets.length === 0) return "No songs listed.";
    let allSongs = [];
    sets.forEach((set) => {
        if (Array.isArray(set.songs)) {
            allSongs = allSongs.concat(set.songs.map((song) => song.name));
        }
    });
    if (allSongs.length === 0) return "No songs listed.";

    const maxDisplay = 3; // App.jsx shows 3 featured tracks
    const truncated = allSongs.slice(0, maxDisplay).join(", ");
    return allSongs.length > maxDisplay ? `${truncated}...` : truncated;
};

const handleViewSetlist = (setlist) => {
    // This action navigates to SetlistDetail.vue
    // We use Link component's href for this.
};
</script>

<template>
    <Head title="Saved Setlists" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Success message display -->
                <div
                    v-if="flashSuccess"
                    class="mb-4 rounded-lg bg-accent-500 px-4 py-3 shadow-md text-neutral-900"
                    v-html="flashSuccess"></div>

                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2
                            class="text-3xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
                            Saved Setlists
                        </h2>
                        <p class="mt-2 text-gray-400">
                            Your curated collection of musical experiences
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-gray-400">
                            <span class="font-semibold text-white">
                                {{ hasSetlists ? processedSetlists.length : 0 }}
                            </span>
                            setlists saved
                        </div>
                    </div>
                </div>

                <!-- Conditional Rendering: If no setlists present vs. Setlists present -->
                <div
                    v-if="!hasSetlists"
                    class="rounded-lg bg-neutral-800 p-6 text-center shadow-sm overflow-hidden">
                    <h3 class="mb-4 text-xl font-semibold text-white">
                        No Setlists Saved Yet!
                    </h3>
                    <p class="text-gray-400">
                        Generate a setlist from an upcoming or past gig to see
                        it here.
                    </p>
                </div>

                <div v-else class="grid gap-6">
                    <Card
                        v-for="(setlist, index) in processedSetlists"
                        :key="setlist.id"
                        :class="`bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-emerald-500/10 group cursor-pointer`">
                        <Link
                            :href="route('setlists.show', setlist.id)"
                            class="block">
                            <CardContent class="p-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <Avatar
                                            class="h-16 w-16 ring-2 ring-gray-600 group-hover:ring-emerald-500/50 transition-all duration-300">
                                            <AvatarImage
                                                v-if="
                                                    setlist.gig.artist_image_url
                                                "
                                                :src="
                                                    setlist.gig.artist_image_url
                                                "
                                                :alt="
                                                    setlist.gig.artist_name
                                                " />
                                            <AvatarFallback
                                                v-else
                                                class="bg-gradient-to-r from-gray-700 to-gray-600 text-lg text-white">
                                                {{ setlist.avatarFallback }}
                                            </AvatarFallback>
                                        </Avatar>

                                        <div class="flex-1 space-y-2">
                                            <h3
                                                class="text-xl font-semibold text-white group-hover:text-emerald-400 transition-colors">
                                                {{ setlist.artist_name }}
                                            </h3>

                                            <div
                                                class="flex items-center gap-4 text-sm text-gray-400">
                                                <div
                                                    class="flex items-center gap-2">
                                                    <MapPin class="h-4 w-4" />
                                                    <span>
                                                        {{ setlist.venue_name }}
                                                    </span>
                                                </div>
                                                <div
                                                    class="flex items-center gap-2">
                                                    <Calendar class="h-4 w-4" />
                                                    <span>
                                                        {{
                                                            formatGigDate(
                                                                setlist.gig_date
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                                <div
                                                    class="flex items-center gap-2">
                                                    <Clock class="h-4 w-4" />
                                                    <span>
                                                        {{
                                                            setlist.total_duration_display
                                                        }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="space-y-1">
                                                <div
                                                    class="text-sm text-gray-500">
                                                    Featured tracks:
                                                </div>
                                                <div
                                                    class="text-sm text-gray-300">
                                                    {{
                                                        truncateSongs(
                                                            setlist.sets
                                                        )
                                                    }}
                                                </div>
                                            </div>

                                            <div
                                                class="flex items-center gap-4">
                                                <div
                                                    class="text-sm text-gray-500">
                                                    <span
                                                        class="font-semibold text-white">
                                                        {{ setlist.totalSongs }}
                                                    </span>
                                                    songs
                                                </div>
                                                <div
                                                    class="text-sm text-gray-500">
                                                    Last modified
                                                    {{
                                                        setlist.last_modified_display
                                                    }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-gray-400 hover:bg-emerald-500/10 hover:text-emerald-400 transition-all duration-200">
                                            <Play class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            @click="handleViewSetlist(setlist)"
                                            class="text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 px-6 shadow-lg shadow-emerald-500/25 transition-all duration-200">
                                            <Eye class="mr-2 h-4 w-4" />
                                            View Setlist
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Link>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
