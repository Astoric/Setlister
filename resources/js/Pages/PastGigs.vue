<script setup>
import { computed, ref, watch, h } from "vue"; // Import 'h' for renderStars
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

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
import Dialog from "@/Components/ui/Dialog.vue";
import DialogContent from "@/Components/ui/DialogContent.vue";
import DialogHeader from "@/Components/ui/DialogHeader.vue";
import DialogTitle from "@/Components/ui/DialogTitle.vue";
import DialogDescription from "@/Components/ui/DialogDescription.vue";

import GigFormModal from "@/Components/GigFormModal.vue";
import SetlistGeneratorModal from "@/Components/SetlistGeneratorModal.vue";
import GigDetailModal from "@/Components/GigDetailModal.vue";

import {
    Plus,
    Edit,
    Calendar,
    MapPin,
    List,
    CalendarDays,
    Eye,
    Music,
    Star,
} from "lucide-vue-next";

const props = defineProps({
    gigs: {
        type: Array,
    },
});

// Helper method for total duration (moved from SetlistController/Model)
const calculateTotalDuration = (sets) => {
    let totalDurationMs = 0;
    if (Array.isArray(sets)) {
        sets.forEach((set) => {
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
};

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
                    total_duration_display: calculateTotalDuration(gig.sets), // Calculate duration
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

const showAddGigModal = ref(false);
const showEditGigModal = ref(false);
const selectedGigForEdit = ref(null);
const showSetlistModal = ref(false);
const selectedGigForSetlist = ref(null);
const showGigDetailModal = ref(false);
const selectedGigForDetail = ref(null);

const viewMode = ref("list");
const selectedDate = ref(new Date());

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
    const options = {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    };
    return new Date(dateTimeString).toLocaleString("en-US", options);
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
const renderStars = (rating) => {
    return Array.from({ length: 5 }, (_, i) =>
        h(Star, {
            key: i,
            class: `w-3 h-3 transition-colors duration-200 ${
                i < rating ? "text-yellow-400 fill-current" : "text-gray-500"
            }`,
        })
    );
};
</script>

<template>
    <Head title="Past Gigs" />

    <AuthenticatedLayout>
        <div class="py-4 sm:py-6">
            <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
                <div
                    v-if="flashSuccess"
                    class="mb-4 rounded-lg bg-accent-500 px-4 py-3 shadow-md text-neutral-900"
                    v-html="flashSuccess"></div>

                <div
                    class="mb-6 sm:mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sm:gap-0">
                    <div>
                        <h2
                            class="text-3xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
                            Past Gigs
                        </h2>
                        <p class="mt-2 text-gray-400">
                            Your musical journey and completed performances
                        </p>
                    </div>
                    <div
                        class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-4 w-full sm:w-auto">
                        <div
                            class="flex items-center gap-2 rounded-lg bg-[#191919] p-1 w-full sm:w-auto justify-center">
                            <Button
                                :variant="
                                    viewMode === 'list' ? 'secondary' : 'ghost'
                                "
                                size="sm"
                                @click="viewMode = 'list'"
                                :class="`${
                                    viewMode === 'list'
                                        ? 'bg-emerald-500/20 text-emerald-400'
                                        : 'text-gray-400 hover:text-white'
                                } transition-all duration-200`">
                                <List class="h-4 w-4" />
                            </Button>
                            <Button
                                :variant="
                                    viewMode === 'calendar'
                                        ? 'secondary'
                                        : 'ghost'
                                "
                                size="sm"
                                @click="viewMode = 'calendar'"
                                :class="`${
                                    viewMode === 'calendar'
                                        ? 'bg-emerald-500/20 text-emerald-400'
                                        : 'text-gray-400 hover:text-white'
                                } transition-all duration-200`">
                                <CalendarDays class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <Transition name="fade" mode="out-in">
                    <div v-if="viewMode === 'list'">
                        <div
                            v-if="!hasGigs"
                            class="rounded-lg bg-neutral-800 p-4 sm:p-6 text-center shadow-sm overflow-hidden">
                            <h3
                                class="mb-4 text-lg sm:text-xl font-semibold text-white">
                                No Past Gigs Yet!
                            </h3>
                            <p class="text-gray-400 text-xs sm:text-base">
                                Add some upcoming gigs, and they will appear
                                here after their date has passed.
                            </p>
                        </div>
                        <div v-else class="grid gap-4 sm:gap-6 grid-cols-1">
                            <Card
                                v-for="(gig, index) in processedGigs"
                                :key="gig.id"
                                :class="`bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-emerald-500/10 group cursor-pointer`"
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
                                                    :alt="
                                                        gig.artist_band_name
                                                    " />
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
                                                        <MapPin
                                                            class="h-4 w-4" />
                                                        <span>
                                                            {{ gig.venue }}
                                                        </span>
                                                    </div>
                                                    <div
                                                        class="flex items-center gap-1 sm:gap-2">
                                                        <Calendar
                                                            class="h-4 w-4" />
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
                                                    <span
                                                        class="text-sm text-gray-500">
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
                                                                gig.sets
                                                                    .length >
                                                                    0 &&
                                                                gig.sets[0]
                                                                    .songs
                                                                    ? gig
                                                                          .sets[0]
                                                                          .songs
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
                                                    <span
                                                        class="text-sm text-gray-500">
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
                                                @click.stop="
                                                    openEditGigModal(gig)
                                                "
                                                class="text-gray-400 hover:text-white hover:bg-gray-700 transition-all duration-200">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                @click.stop="
                                                    gig.sets
                                                        ? router.visit(
                                                              route(
                                                                  'gigs.show', // New route for Gig detail
                                                                  gig.id
                                                              )
                                                          )
                                                        : openSetlistModal(gig)
                                                "
                                                class="text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 px-4 sm:px-6 py-2 sm:py-0 shadow-lg shadow-emerald-500/25 transition-all duration-200 text-xs sm:text-base">
                                                <Eye
                                                    v-if="
                                                        gig.sets &&
                                                        gig.sets.length > 0
                                                    "
                                                    class="h-4 w-4 mr-2" />
                                                <span
                                                    v-if="
                                                        gig.sets &&
                                                        gig.sets.length > 0
                                                    ">
                                                    View Setlist
                                                </span>
                                                <span v-else>
                                                    Generate Setlist
                                                </span>
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                    <div v-else-if="viewMode === 'calendar'">
                        <Card class="bg-[#191919] border-gray-600">
                            <CardHeader>
                                <CardTitle class="text-white">
                                    Calendar View
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="text-center py-8 text-gray-400">
                                    <Calendar
                                        class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-3 opacity-50" />
                                    <p class="text-xs sm:text-base">
                                        Calendar view for past gigs is coming
                                        soon!
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </Transition>
            </div>
        </div>
        <GigDetailModal
            :show="showGigDetailModal"
            :gig="selectedGigForDetail"
            @close="closeGigDetailModal" />
        <SetlistGeneratorModal
            :show="showSetlistModal"
            :gig="selectedGigForSetlist"
            @close="closeSetlistModal" />
        <GigFormModal
            :show="showEditGigModal"
            :gig="selectedGigForEdit"
            @close="closeEditGigModal" />
    </AuthenticatedLayout>
</template>
