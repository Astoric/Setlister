<script setup>
import { computed, ref, watch, h } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

import Button from "@/Components/ui/Button.vue";
import Avatar from "@/Components/ui/Avatar.vue";
import AvatarImage from "@/Components/ui/AvatarImage.vue";
import AvatarFallback from "@/Components/ui/AvatarFallback.vue";
import Badge from "@/Components/ui/Badge.vue";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";

import GigFormModal from "@/Components/GigFormModal.vue";
import SetlistGeneratorModal from "@/Components/SetlistGeneratorModal.vue";
import GigDetailModal from "@/Components/GigDetailModal.vue";

import { Edit, Calendar, MapPin, Eye, Star } from "lucide-vue-next";

const props = defineProps({
    query: String,
    upcoming: { type: Array, default: () => [] },
    past: { type: Array, default: () => [] },
});

// --- Preprocessing for arrays, like UpcomingGigs.vue ---
const processedUpcoming = ref([]);
const processedPast = ref([]);

watch(
    () => props.upcoming,
    (newGigs) => {
        processedUpcoming.value = newGigs.map((gig) => ({
            ...gig,
            support_acts: Array.isArray(gig.support_acts)
                ? gig.support_acts
                : [],
            people_attending: Array.isArray(gig.people_attending)
                ? gig.people_attending
                : [],
        }));
    },
    { immediate: true }
);

watch(
    () => props.past,
    (newGigs) => {
        processedPast.value = newGigs.map((gig) => ({
            ...gig,
            support_acts: Array.isArray(gig.support_acts)
                ? gig.support_acts
                : [],
            people_attending: Array.isArray(gig.people_attending)
                ? gig.people_attending
                : [],
        }));
    },
    { immediate: true }
);

// modals
const showEditGigModal = ref(false);
const selectedGigForEdit = ref(null);
const showSetlistModal = ref(false);
const selectedGigForSetlist = ref(null);
const showGigDetailModal = ref(false);
const selectedGigForDetail = ref(null);

const openEditGigModal = (gig) => {
    selectedGigForEdit.value = gig;
    showEditGigModal.value = true;
};
const closeEditGigModal = () => {
    showEditGigModal.value = false;
    selectedGigForEdit.value = null;
};
const openSetlistModal = (gig) => {
    selectedGigForSetlist.value = gig;
    showSetlistModal.value = true;
};
const closeSetlistModal = () => {
    showSetlistModal.value = false;
    selectedGigForSetlist.value = null;
};
const openGigDetailModal = (gig) => {
    selectedGigForDetail.value = gig;
    showGigDetailModal.value = true;
};
const closeGigDetailModal = () => {
    showGigDetailModal.value = false;
    selectedGigForDetail.value = null;
};

const formatDateTime = (dateTimeString) => {
    return new Date(dateTimeString).toLocaleString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const getAvatarFallback = (name) =>
    name
        ? name
              .split(" ")
              .map((n) => n[0])
              .join("")
              .substring(0, 2)
              .toUpperCase()
        : "??";
</script>

<template>
    <Head :title="`Search: ${query}`" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 py-6">
            <h2 class="text-3xl font-bold text-white mb-6">
                Search Results for "{{ query }}"
            </h2>

            <!-- Upcoming -->
            <div v-if="processedUpcoming.length">
                <h3 class="text-2xl font-semibold text-emerald-400 mb-4">
                    Upcoming Gigs
                </h3>
                <div class="grid gap-4 sm:gap-6 grid-cols-1">
                    <Card
                        v-for="gig in processedUpcoming"
                        :key="gig.id"
                        class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 cursor-pointer"
                        @click="openGigDetailModal(gig)">
                        <CardContent class="p-4 sm:p-6">
                            <div
                                class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sm:gap-0">
                                <div
                                    class="flex items-center gap-3 sm:gap-4 w-full sm:w-auto">
                                    <Avatar
                                        class="h-12 w-12 sm:h-16 sm:w-16 ring-2 ring-gray-600 group-hover:ring-emerald-500/50 transition-all duration-300">
                                        <AvatarImage
                                            v-if="gig.artist_image_url"
                                            :src="gig.artist_image_url"
                                            :alt="gig.artist_band_name" />
                                        <AvatarFallback
                                            v-else
                                            class="bg-gradient-to-r from-gray-700 to-gray-600 text-white text-lg">
                                            {{
                                                getAvatarFallback(
                                                    gig.artist_band_name
                                                )
                                            }}
                                        </AvatarFallback>
                                    </Avatar>

                                    <div class="space-y-1 sm:space-y-2">
                                        <h3
                                            class="text-lg sm:text-xl font-semibold text-white group-hover:text-emerald-400 transition-colors">
                                            {{ gig.artist_band_name }}
                                        </h3>

                                        <div
                                            class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 text-xs sm:text-sm text-gray-400">
                                            <div
                                                class="flex items-center gap-1 sm:gap-2">
                                                <MapPin class="h-4 w-4" />
                                                <span>
                                                    {{ gig.venue }}
                                                </span>
                                            </div>
                                            <div
                                                class="flex items-center gap-1 sm:gap-2">
                                                <Calendar class="h-4 w-4" />
                                                <span>
                                                    {{
                                                        formatDateTime(
                                                            gig.gig_date_time
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            class="flex items-center gap-1 sm:gap-2 flex-wrap">
                                            <span class="text-sm text-gray-500">
                                                Support:
                                            </span>
                                            <span
                                                v-if="
                                                    (Array.isArray(
                                                        gig.support_acts
                                                    )
                                                        ? gig.support_acts
                                                        : []
                                                    ).length > 0
                                                "
                                                class="text-sm text-gray-300">
                                                {{
                                                    (Array.isArray(
                                                        gig.support_acts
                                                    )
                                                        ? gig.support_acts
                                                        : []
                                                    ).join(", ")
                                                }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-sm text-gray-400">
                                                None
                                            </span>
                                        </div>

                                        <div
                                            class="flex items-center gap-1 sm:gap-2 flex-wrap">
                                            <span class="text-sm text-gray-500">
                                                Attending with:
                                            </span>
                                            <div
                                                class="flex gap-1 sm:gap-2 flex-wrap">
                                                <Badge
                                                    v-if="
                                                        (Array.isArray(
                                                            gig.people_attending
                                                        )
                                                            ? gig.people_attending
                                                            : []
                                                        ).length > 0
                                                    "
                                                    v-for="person in gig.people_attending"
                                                    :key="person"
                                                    variant="secondary"
                                                    class="bg-gray-700 text-gray-300 hover:bg-emerald-500/20 hover:text-emerald-400 transition-colors">
                                                    {{ person }}
                                                </Badge>
                                                <span
                                                    v-else
                                                    class="text-sm text-gray-500">
                                                    No one
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center gap-2 sm:gap-3 mt-4 sm:mt-0 justify-center sm:justify-end w-full sm:w-auto">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click.stop="openEditGigModal(gig)"
                                        class="text-gray-400 hover:text-white hover:bg-gray-700 transition-all duration-200">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        @click.stop="
                                            gig.sets
                                                ? router.visit(
                                                      route(
                                                          'gigs.show', // Will need a new route to show gig detail if not already setup
                                                          gig.id // Pass gig ID for detail view
                                                      )
                                                  )
                                                : openSetlistModal(gig)
                                        "
                                        class="text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 px-4 sm:px-6 py-2 sm:py-0 shadow-lg shadow-emerald-500/25 transition-all duration-200 text-xs sm:text-base">
                                        <Eye
                                            v-if="
                                                gig.sets && gig.sets.length > 0
                                            "
                                            class="h-4 w-4 mr-2" />
                                        <span
                                            v-if="
                                                gig.sets && gig.sets.length > 0
                                            ">
                                            View Setlist
                                        </span>
                                        <span v-else>Generate Setlist</span>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Past -->
            <div v-if="processedPast.length" class="mt-10">
                <h3 class="text-2xl font-semibold text-emerald-400 mb-4">
                    Past Gigs
                </h3>
                <div class="grid gap-4 sm:gap-6 grid-cols-1">
                    <Card
                        v-for="gig in processedPast"
                        :key="gig.id"
                        class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 cursor-pointer"
                        @click="openGigDetailModal(gig)">
                        <CardContent class="p-4 sm:p-6">
                            <div
                                class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sm:gap-0">
                                <div
                                    class="flex items-center gap-3 sm:gap-4 w-full sm:w-auto">
                                    <Avatar
                                        class="h-12 w-12 sm:h-16 sm:w-16 ring-2 ring-gray-600 group-hover:ring-emerald-500/50 transition-all duration-300">
                                        <AvatarImage
                                            v-if="gig.artist_image_url"
                                            :src="gig.artist_image_url"
                                            :alt="gig.artist_band_name" />
                                        <AvatarFallback
                                            v-else
                                            class="bg-gradient-to-r from-gray-700 to-gray-600 text-white text-lg">
                                            {{
                                                getAvatarFallback(
                                                    gig.artist_band_name
                                                )
                                            }}
                                        </AvatarFallback>
                                    </Avatar>

                                    <div class="space-y-1 sm:space-y-2">
                                        <h3
                                            class="text-lg sm:text-xl font-semibold text-white group-hover:text-emerald-400 transition-colors">
                                            {{ gig.artist_band_name }}
                                        </h3>

                                        <div
                                            class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 text-xs sm:text-sm text-gray-400">
                                            <div
                                                class="flex items-center gap-1 sm:gap-2">
                                                <MapPin class="h-4 w-4" />
                                                <span>
                                                    {{ gig.venue }}
                                                </span>
                                            </div>
                                            <div
                                                class="flex items-center gap-1 sm:gap-2">
                                                <Calendar class="h-4 w-4" />
                                                <span>
                                                    {{
                                                        formatDateTime(
                                                            gig.gig_date_time
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            class="flex items-center gap-1 sm:gap-2 flex-wrap">
                                            <span class="text-sm text-gray-500">
                                                Support:
                                            </span>
                                            <span
                                                v-if="
                                                    (Array.isArray(
                                                        gig.support_acts
                                                    )
                                                        ? gig.support_acts
                                                        : []
                                                    ).length > 0
                                                "
                                                class="text-sm text-gray-300">
                                                {{
                                                    (Array.isArray(
                                                        gig.support_acts
                                                    )
                                                        ? gig.support_acts
                                                        : []
                                                    ).join(", ")
                                                }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-sm text-gray-400">
                                                None
                                            </span>
                                        </div>

                                        <div
                                            class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4">
                                            <div
                                                class="flex items-center gap-1 sm:gap-2">
                                                <span
                                                    class="text-sm text-gray-500">
                                                    Songs:
                                                </span>
                                                <span
                                                    class="text-sm text-gray-300">
                                                    {{
                                                        gig.sets &&
                                                        gig.sets.length > 0 &&
                                                        gig.sets[0].songs
                                                            ? gig.sets[0].songs
                                                                  .length
                                                            : "N/A"
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="flex items-center gap-1">
                                                <Star
                                                    class="h-4 w-4 text-gray-500 fill-current" />
                                            </div>
                                        </div>

                                        <div
                                            class="flex items-center gap-1 sm:gap-2 flex-wrap">
                                            <span class="text-sm text-gray-500">
                                                Attended with:
                                            </span>
                                            <div
                                                class="flex gap-1 sm:gap-2 flex-wrap">
                                                <Badge
                                                    v-if="
                                                        (Array.isArray(
                                                            gig.people_attending
                                                        )
                                                            ? gig.people_attending
                                                            : []
                                                        ).length > 0
                                                    "
                                                    v-for="person in gig.people_attending"
                                                    :key="person"
                                                    variant="secondary"
                                                    class="bg-gray-700 text-gray-300 hover:bg-emerald-500/20 hover:text-emerald-400 transition-colors">
                                                    {{ person }}
                                                </Badge>
                                                <span
                                                    v-else
                                                    class="text-sm text-gray-500">
                                                    No one
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center gap-2 sm:gap-3 mt-4 sm:mt-0 justify-center sm:justify-end w-full sm:w-auto">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click.stop="openEditGigModal(gig)"
                                        class="text-gray-400 hover:text-white hover:bg-gray-700 transition-all duration-200">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        @click.stop="
                                            gig.sets
                                                ? router.visit(
                                                      route('gigs.show', gig.id)
                                                  )
                                                : openSetlistModal(gig)
                                        "
                                        class="text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 px-4 sm:px-6 py-2 sm:py-0 shadow-lg shadow-emerald-500/25 transition-all duration-200 text-xs sm:text-base">
                                        <Eye
                                            v-if="
                                                gig.sets && gig.sets.length > 0
                                            "
                                            class="h-4 w-4 mr-2" />
                                        <span
                                            v-if="
                                                gig.sets && gig.sets.length > 0
                                            ">
                                            View Setlist
                                        </span>
                                        <span v-else>Generate Setlist</span>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div
                v-if="!processedUpcoming.length && !processedPast.length"
                class="text-gray-400 mt-10">
                No gigs found for "{{ query }}"
            </div>
        </div>

        <!-- Modals -->
        <GigFormModal
            :show="showEditGigModal"
            :gig="selectedGigForEdit"
            @close="closeEditGigModal" />
        <SetlistGeneratorModal
            :show="showSetlistModal"
            :gig="selectedGigForSetlist"
            @close="closeSetlistModal" />
        <GigDetailModal
            :show="showGigDetailModal"
            :gig="selectedGigForDetail"
            @close="closeGigDetailModal" />
    </AuthenticatedLayout>
</template>
