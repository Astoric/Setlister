<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SetlistGeneratorModal from "@/Components/SetlistGeneratorModal.vue";
import GigDetailModal from "@/Components/GigDetailModal.vue";

const props = defineProps({
    gigs: Array,
});

const showGigDetailModal = ref(false);
const selectedGigForDetail = ref(null);

const showSetlistModal = ref(false);
const selectedGigForSetlist = ref(null);

const processedGigs = ref([]);
watch(
    () => props.gigs,
    (newGigs) => {
        if (newGigs) {
            processedGigs.value = newGigs.map((gig) => {
                return {
                    ...gig,
                    support_acts: Array.isArray(gig.support_acts)
                        ? gig.support_acts
                        : [],
                    people_attending: Array.isArray(gig.people_attending)
                        ? gig.people_attending
                        : [],
                };
            });
        } else {
            processedGigs.value = [];
        }
    },
    { immediate: true }
);

const hasGigs = computed(
    () => processedGigs.value && processedGigs.value.length > 0
);

const flashSuccess = computed(() => usePage().props.flash?.success || null);

const openGigDetailModal = (gig) => {
    selectedGigForDetail.value = processedGigs.value.find(
        (g) => g.id === gig.id
    );
    showGigDetailModal.value = true;
};

const closeGigDetailModal = () => {
    showGigDetailModal.value = false;
    selectedGigForDetail.value = null;
};

const openSetlistModal = (gig) => {
    selectedGigForSetlist.value = processedGigs.value.find(
        (g) => g.id === gig.id
    );
    showSetlistModal.value = true;
};

const closeSetlistModal = () => {
    showSetlistModal.value = false;
    selectedGigForSetlist.value = null;
};

const formatDateTime = (dateTimeString) => {
    const options = {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    };
    return new Date(dateTimeString).toLocaleString("en-US", options);
};
</script>

<template>
    <Head title="Past Gigs" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    v-if="flashSuccess"
                    class="bg-accent-500 text-neutral-900 px-4 py-3 rounded-lg shadow-md mb-4">
                    {{ flashSuccess }}
                </div>

                <!-- Header for Past Gigs page -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="font-semibold text-xl text-white leading-tight">
                        Past Gigs
                    </h2>
                </div>

                <!-- Conditional Rendering: If no gigs present vs. Gigs present -->
                <div
                    v-if="!hasGigs"
                    class="bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <h3 class="text-white text-xl font-semibold mb-4">
                        No Past Gigs Yet!
                    </h3>
                    <p class="text-neutral-400">
                        Add some upcoming gigs, and they will appear here after
                        their date has passed.
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <!-- List of Gigs -->
                    <div
                        v-for="gig in processedGigs"
                        :key="gig.id"
                        @click="openGigDetailModal(gig)"
                        class="bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg p-4 flex items-center cursor-pointer hover:bg-neutral-700 transition-colors duration-200 relative">
                        <!-- Artist Image (Left) -->
                        <div class="flex-shrink-0 mr-4">
                            <img
                                v-if="gig.artist_image_url"
                                :src="gig.artist_image_url"
                                alt="Artist"
                                class="w-16 h-16 rounded-full object-cover border border-neutral-700" />
                            <div
                                v-else
                                class="w-16 h-16 rounded-full bg-neutral-700 flex items-center justify-center text-neutral-400 text-xs overflow-hidden">
                                No Image
                            </div>
                        </div>

                        <!-- Main Gig Details -->
                        <div class="flex-grow">
                            <h3 class="text-white text-lg font-semibold">
                                {{ gig.artist_band_name }}
                            </h3>
                            <p class="text-neutral-400 text-sm mt-1">
                                {{ gig.venue }} -
                                {{ formatDateTime(gig.gig_date_time) }}
                            </p>
                            <div class="mt-2 text-neutral-400 text-sm">
                                Support:
                                {{
                                    (Array.isArray(gig.support_acts)
                                        ? gig.support_acts
                                        : []
                                    ).join(", ") || "None"
                                }}
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <!-- People Attending Tags -->
                                <span
                                    v-if="
                                        Array.isArray(gig.people_attending) &&
                                        gig.people_attending.length
                                    "
                                    v-for="person in gig.people_attending"
                                    :key="person"
                                    class="bg-green-500 text-neutral-900 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                    {{ person }}
                                </span>
                                <span v-else class="text-neutral-500 text-xs">
                                    No one going
                                </span>
                            </div>
                        </div>

                        <!-- Buttons (Right) -->
                        <div
                            class="flex-shrink-0 flex items-center space-x-2 ml-4">
                            <!-- Generate Setlist Button -->
                            <PrimaryButton @click.stop="openSetlistModal(gig)">
                                Generate Setlist
                            </PrimaryButton>

                            <!--Right arrow icon -->
                            <svg
                                class="h-5 w-5 text-neutral-400 ml-2"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gig Detail Modal -->
        <GigDetailModal
            :show="showGigDetailModal"
            :gig="selectedGigForDetail"
            @close="closeGigDetailModal" />

        <!-- Setlist Generator Modal -->
        <SetlistGeneratorModal
            :show="showSetlistModal"
            :gig="selectedGigForSetlist"
            @close="closeSetlistModal" />
    </AuthenticatedLayout>
</template>
