<script setup>
import { computed, ref, watch, h } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
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
    Users,
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
                    // Add calculated total duration for existing setlist data
                    total_duration_display: calculateTotalDuration(gig.sets),
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

const openAddGigModal = () => {
    showAddGigModal.value = true;
};
const closeAddGigModal = () => {
    showAddGigModal.value = false;
};
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
    <Head title="Upcoming Gigs" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    v-if="flashSuccess"
                    class="mb-4 rounded-lg bg-accent-500 px-4 py-3 shadow-md text-neutral-900"
                    v-html="flashSuccess"></div>

                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2
                            class="text-3xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
                            Upcoming Gigs
                        </h2>
                        <p class="mt-2 text-gray-400">
                            Manage your upcoming performances and setlists
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <Button
                            @click="openAddGigModal"
                            class="text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 px-6 shadow-lg shadow-emerald-500/25 transition-all duration-200">
                            <Plus class="mr-2 h-4 w-4" />
                            Add New Gig
                        </Button>
                        <div
                            class="flex items-center gap-2 rounded-lg bg-[#191919] p-1">
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
                            class="rounded-lg bg-neutral-800 p-6 text-center shadow-sm overflow-hidden">
                            <h3 class="mb-4 text-xl font-semibold text-white">
                                No Upcoming Gigs Yet!
                            </h3>
                            <Button
                                @click="openAddGigModal"
                                class="mx-auto flex items-center bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 px-6 shadow-lg shadow-emerald-500/25 transition-all duration-200">
                                <Plus class="mr-2 h-4 w-4" />
                                Add New Gig
                            </Button>
                        </div>
                        <div v-else class="grid gap-6">
                            <Card
                                v-for="(gig, index) in processedGigs"
                                :key="gig.id"
                                :class="`bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-emerald-500/10 group cursor-pointer`"
                                @click="openGigDetailModal(gig)">
                                <CardContent class="p-6">
                                    <div
                                        class="flex items-center justify-between">
                                        <div class="flex items-center gap-4">
                                            <Avatar
                                                class="h-16 w-16 ring-2 ring-gray-600 group-hover:ring-emerald-500/50 transition-all duration-300">
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

                                            <div class="space-y-2">
                                                <h3
                                                    class="text-xl font-semibold text-white group-hover:text-emerald-400 transition-colors">
                                                    {{ gig.artist_band_name }}
                                                </h3>

                                                <div
                                                    class="flex items-center gap-4 text-sm text-gray-400">
                                                    <div
                                                        class="flex items-center gap-2">
                                                        <MapPin
                                                            class="h-4 w-4" />
                                                        <span>
                                                            {{ gig.venue }}
                                                        </span>
                                                    </div>
                                                    <div
                                                        class="flex items-center gap-2">
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
                                                    class="flex items-center gap-2">
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
                                                    class="flex items-center gap-2">
                                                    <span
                                                        class="text-sm text-gray-500">
                                                        Attending with:
                                                    </span>
                                                    <div class="flex gap-2">
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

                                        <div class="flex items-center gap-3">
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
                                                                  'gigs.show', // Will need a new route to show gig detail if not already setup
                                                                  gig.id // Pass gig ID for detail view
                                                              )
                                                          )
                                                        : openSetlistModal(gig)
                                                "
                                                class="text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 px-6 shadow-lg shadow-emerald-500/25 transition-all duration-200">
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
                                        class="w-12 h-12 mx-auto mb-3 opacity-50" />
                                    <p>
                                        Calendar view for upcoming gigs is
                                        coming soon!
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </Transition>
            </div>
        </div>

        <GigFormModal :show="showAddGigModal" @close="closeAddGigModal" />
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
