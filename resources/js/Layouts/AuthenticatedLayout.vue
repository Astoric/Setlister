<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import axios from "axios";

import {
    Calendar,
    Music,
    Users,
    Search,
    LogOut,
    Settings,
    Menu,
    X,
    BarChart3,
    Plus,
    Edit,
    MapPin,
    List,
    CalendarDays,
    Eye,
    Star, // Basic Lucide icons
    // Other Lucide icons that might have been copied but are not used in AuthenticatedLayout itself:
    // MapPin, CalendarDays, Clock, Volume2, TrendingUp, Award, Play, User, Shield, ExternalLink, AlertTriangle, Check
} from "lucide-vue-next";

// New UI Components
import Avatar from "@/Components/ui/Avatar.vue";
import AvatarImage from "@/Components/ui/AvatarImage.vue";
import AvatarFallback from "@/Components/ui/AvatarFallback.vue";
import Button from "@/Components/ui/Button.vue";
import DropdownMenu from "@/Components/ui/DropdownMenu.vue";
import DropdownMenuContent from "@/Components/ui/DropdownMenuContent.vue";
import DropdownMenuItem from "@/Components/ui/DropdownMenuItem.vue";
import DropdownMenuSeparator from "@/Components/ui/DropdownMenuSeparator.vue";
import DropdownMenuTrigger from "@/Components/ui/DropdownMenuTrigger.vue";
import Input from "@/Components/ui/Input.vue";
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

const user = usePage().props.auth.user;

const props = defineProps({
    query: String,
    upcoming: { type: Array, default: () => [] },
    past: { type: Array, default: () => [] },
});

const searchQuery = ref("");
const searchResults = ref({ upcoming: [], past: [] });
const searching = ref(false);
let debounceTimeout = null;

const sidebarCollapsed = ref(false);
const sidebarOpen = ref(false); // For mobile
const currentPage = computed(() => usePage().component);

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

const handleProfileSettings = () => {
    router.visit(route("profile.edit"));
};
const handleLogout = () => {
    router.post(route("logout"));
};

const pageVariants = {
    initial: { opacity: 0, y: 20 },
    in: { opacity: 1, y: 0 },
    out: { opacity: 0, y: -20 },
};

const pageTransition = {
    type: "tween",
    ease: "anticipate",
    duration: 0.3,
};

watch(searchQuery, (val) => {
    clearTimeout(debounceTimeout);

    if (!val || val.length < 2) {
        searchResults.value = { upcoming: [], past: [] };
        return;
    }

    debounceTimeout = setTimeout(async () => {
        searching.value = true;
        try {
            const res = await axios.get(route("gigs.search.api"), {
                params: { q: val },
            });
            searchResults.value = res.data;
        } catch (err) {
            console.error("Live search failed:", err);
        } finally {
            searching.value = false;
        }
    }, 300); // 300ms debounce
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
</script>

<template>
    <div class="min-h-screen bg-[#212121] text-white flex flex-col">
        <div class="flex flex-col md:flex-row flex-1">
            <!-- Mobile Hamburger Button -->
            <button
                class="md:hidden fixed top-4 left-4 z-40 bg-[#191919] rounded-full p-2 shadow-lg border border-gray-700 text-gray-300 hover:text-white hover:bg-gray-800 transition-all"
                @click="sidebarOpen = true"
                aria-label="Open navigation menu">
                <Menu class="h-6 w-6" />
            </button>

            <!-- Sidebar Overlay for Mobile -->
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 bg-black bg-opacity-40 z-30 md:hidden"
                @click.self="sidebarOpen = false"></div>

            <!-- Sidebar -->
            <div
                :class="[
                    sidebarCollapsed ? 'w-16' : 'w-64',
                    'bg-[#121212] border-r border-gray-800 min-h-screen transition-all duration-300 fixed md:static z-40 top-0 left-0 h-full md:h-auto md:relative flex-shrink-0',
                    sidebarOpen ? 'block' : 'hidden',
                    'md:block',
                ]"
                :style="{ width: sidebarCollapsed ? '64px' : '256px' }">
                <div class="p-4">
                    <!-- Mobile Close Button -->
                    <div class="flex md:hidden justify-end mb-4">
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="sidebarOpen = false"
                            class="p-2 text-gray-400 transition-all duration-200 hover:bg-gray-700 hover:text-white">
                            <X class="h-5 w-5" />
                        </Button>
                    </div>
                    <!-- Header with toggle -->
                    <div class="mb-8 flex items-center justify-between">
                        <!-- Removed AnimatePresence and motion.div around logo/title -->
                        <div
                            v-if="!sidebarCollapsed"
                            class="flex items-center gap-3">
                            <Link
                                :href="route('dashboard')"
                                class="flex items-center gap-2">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full">
                                    <img
                                        src="/images/logo.svg"
                                        alt="Setlister Logo"
                                        class="h-15 w-auto" />
                                </div>
                                <span
                                    class="bg-green-500 bg-clip-text text-xl font-semibold text-transparent">
                                    Setlister
                                </span>
                            </Link>
                        </div>

                        <Button
                            variant="ghost"
                            size="sm"
                            @click="sidebarCollapsed = !sidebarCollapsed"
                            class="p-2 text-gray-400 transition-all duration-200 hover:bg-gray-700 hover:text-white hidden md:inline-flex">
                            <Menu v-if="sidebarCollapsed" class="h-4 w-4" />
                            <X v-else class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Collapsed logo when sidebar is collapsed -->
                    <!-- Removed AnimatePresence and motion.div -->
                    <div
                        v-if="sidebarCollapsed"
                        class="mb-8 flex justify-center">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-emerald-400 to-teal-500">
                            <Music class="h-4 w-4 text-white" />
                        </div>
                    </div>

                    <!-- Navigation Links -->
                    <nav class="space-y-2">
                        <Button
                            :variant="
                                currentPage.startsWith('Upcoming')
                                    ? 'secondary'
                                    : 'ghost'
                            "
                            :class="`${
                                sidebarCollapsed
                                    ? 'w-full justify-center px-2'
                                    : 'w-full justify-start gap-3'
                            } ${
                                currentPage.startsWith('Upcoming')
                                    ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border-emerald-500/30 text-emerald-400 hover:from-emerald-500/30 hover:to-teal-500/30'
                                    : 'text-gray-400 hover:text-white hover:bg-gray-700'
                            } transition-all duration-200`"
                            @click="router.visit(route('dashboard'))"
                            :title="sidebarCollapsed ? 'Upcoming Gigs' : ''">
                            <Calendar class="h-4 w-4" />
                            <span v-if="!sidebarCollapsed">Upcoming Gigs</span>
                        </Button>
                        <Button
                            :variant="
                                currentPage.startsWith('Past')
                                    ? 'secondary'
                                    : 'ghost'
                            "
                            :class="`${
                                sidebarCollapsed
                                    ? 'w-full justify-center px-2'
                                    : 'w-full justify-start gap-3'
                            } ${
                                currentPage.startsWith('Past')
                                    ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border-emerald-500/30 text-emerald-400 hover:from-emerald-500/30 hover:to-teal-500/30'
                                    : 'text-gray-400 hover:text-white hover:bg-gray-700'
                            } transition-all duration-200`"
                            @click="router.visit(route('past-gigs'))"
                            :title="sidebarCollapsed ? 'Past Gigs' : ''">
                            <Music class="h-4 w-4" />
                            <span v-if="!sidebarCollapsed">Past Gigs</span>
                        </Button>
                        <Button
                            :variant="
                                currentPage.startsWith('Stats')
                                    ? 'secondary'
                                    : 'ghost'
                            "
                            :class="`${
                                sidebarCollapsed
                                    ? 'w-full justify-center px-2'
                                    : 'w-full justify-start gap-3'
                            } ${
                                currentPage.startsWith('Stats')
                                    ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border-emerald-500/30 text-emerald-400 hover:from-emerald-500/30 hover:to-teal-500/30'
                                    : 'text-gray-400 hover:text-white hover:bg-gray-700'
                            } transition-all duration-200`"
                            @click="router.visit(route('stats.index'))"
                            :title="sidebarCollapsed ? 'Stats' : ''">
                            <BarChart3 class="h-4 w-4" />
                            <span v-if="!sidebarCollapsed">Stats</span>
                        </Button>
                        <Button
                            :variant="
                                currentPage.startsWith('Profile')
                                    ? 'secondary'
                                    : 'ghost'
                            "
                            :class="`${
                                sidebarCollapsed
                                    ? 'w-full justify-center px-2'
                                    : 'w-full justify-start gap-3'
                            } ${
                                currentPage.startsWith('Profile')
                                    ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border-emerald-500/30 text-emerald-400 hover:from-emerald-500/30 hover:to-teal-500/30'
                                    : 'text-gray-400 hover:text-white hover:bg-gray-700'
                            } transition-all duration-200`"
                            @click="router.visit(route('profile.edit'))"
                            :title="sidebarCollapsed ? 'Profile' : ''">
                            <Settings class="h-4 w-4" />
                            <span v-if="!sidebarCollapsed">Profile</span>
                        </Button>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 md:ml-0 flex flex-col min-h-screen">
                <!-- Header -->
                <div
                    class="bg-[#212121] border-b border-gray-700 p-4 sm:p-6 sticky top-0 z-20">
                    <div
                        class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sm:gap-0">
                        <!-- Removed motion.div -->
                        <div
                            :class="[
                                'transition-all duration-200',
                                'w-full',
                                'sm:w-auto',
                                !sidebarOpen ? 'ml-14 md:ml-0' : 'ml-0',
                            ]">
                            <h1
                                class="text-lg sm:text-2xl font-semibold text-white">
                                Hi {{ user.name }}
                            </h1>
                            <p class="mt-1 text-xs sm:text-base text-gray-400">
                                Ready to create some amazing setlists?
                            </p>
                        </div>

                        <div
                            class="flex flex-row items-center gap-2 sm:gap-4 w-full sm:w-auto">
                            <div
                                class="relative flex-1 sm:flex-none w-full sm:w-auto">
                                <Search
                                    class="absolute left-3 top-1/2 -translate-y-1/2 transform text-gray-400 h-4 w-4" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search gigs..."
                                    @keydown.enter="
                                        router.visit(
                                            route('gigs.search', {
                                                q: searchQuery,
                                            })
                                        )
                                    "
                                    class="w-full sm:w-80 pl-10 bg-[#191919] border-gray-600 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200 text-xs sm:text-base" />
                            </div>

                            <!-- Profile Dropdown -->
                            <DropdownMenu>
                                <DropdownMenuTrigger
                                    class="relative h-8 w-8 cursor-pointer rounded-full ring-2 ring-emerald-500/30 transition-all hover:ring-emerald-500/50 ml-2">
                                    <Avatar class="h-8 w-8">
                                        <AvatarImage
                                            v-if="
                                                user.spotify_profile_picture_url
                                            "
                                            :src="
                                                user.spotify_profile_picture_url
                                            "
                                            alt="Profile Picture" />
                                        <AvatarFallback
                                            v-else
                                            class="bg-gradient-to-r from-gray-700 to-gray-600 text-white text-lg">
                                            {{ getAvatarFallback(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    class="w-56 bg-[#191919] border-gray-600"
                                    align="end">
                                    <DropdownMenuItem
                                        @click="handleProfileSettings"
                                        class="cursor-pointer text-gray-300 focus:bg-gray-700 focus:text-white">
                                        <Settings class="mr-2 h-4 w-4" />
                                        <span>Profile settings</span>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator
                                        class="bg-gray-700" />
                                    <DropdownMenuItem
                                        @click="handleLogout"
                                        class="cursor-pointer text-gray-300 focus:bg-gray-700 focus:text-white">
                                        <LogOut class="mr-2 h-4 w-4" />
                                        <span>Logout</span>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>

                <!-- Page Content Slot -->
                <main class="p-4 sm:p-6 flex-1 flex flex-col">
                    <div v-if="searchQuery.length >= 2">
                        <h2 class="text-2xl font-bold mb-6 text-white">
                            Search Results for "{{ searchQuery }}"
                        </h2>

                        <!-- UPCOMING -->
                        <div v-if="searchResults.upcoming.length" class="mb-10">
                            <h3
                                class="text-xl font-semibold text-emerald-400 mb-3">
                                Upcoming Gigs
                            </h3>
                            <div class="grid gap-4 sm:gap-6 grid-cols-1">
                                <Card
                                    v-for="gig in searchResults.upcoming"
                                    :key="gig.id"
                                    class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-emerald-500/10 group cursor-pointer"
                                    @click="openGigDetailModal(gig)">
                                    <CardContent class="p-4 sm:p-6">
                                        <div
                                            class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sm:gap-0">
                                            <!-- LEFT -->
                                            <div
                                                class="flex items-center gap-3 sm:gap-4 w-full sm:w-auto">
                                                <Avatar
                                                    class="h-12 w-12 sm:h-16 sm:w-16 ring-2 ring-gray-600 group-hover:ring-emerald-500/50">
                                                    <AvatarImage
                                                        v-if="
                                                            gig.artist_image_url
                                                        "
                                                        :src="
                                                            gig.artist_image_url
                                                        " />
                                                    <AvatarFallback v-else>
                                                        {{
                                                            getAvatarFallback(
                                                                gig.artist_band_name
                                                            )
                                                        }}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div
                                                    class="space-y-1 sm:space-y-2">
                                                    <h3
                                                        class="text-lg sm:text-xl font-semibold text-white group-hover:text-emerald-400 transition-colors">
                                                        {{
                                                            gig.artist_band_name
                                                        }}
                                                    </h3>
                                                    <div
                                                        class="flex flex-col sm:flex-row gap-2 sm:gap-4 text-xs sm:text-sm text-gray-400">
                                                        <div
                                                            class="flex items-center gap-1">
                                                            <MapPin
                                                                class="h-4 w-4" />
                                                            <span>
                                                                {{ gig.venue }}
                                                            </span>
                                                        </div>
                                                        <div
                                                            class="flex items-center gap-1">
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
                                                        class="flex items-center flex-wrap gap-2">
                                                        <span
                                                            class="text-sm text-gray-500">
                                                            Support:
                                                        </span>
                                                        <span
                                                            v-if="
                                                                gig.support_acts
                                                                    .length
                                                            "
                                                            class="text-sm text-gray-300">
                                                            {{
                                                                gig.support_acts.join(
                                                                    ", "
                                                                )
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="text-sm text-gray-400">
                                                            None
                                                        </span>
                                                    </div>
                                                    <div
                                                        class="flex items-center flex-wrap gap-2">
                                                        <span
                                                            class="text-sm text-gray-500">
                                                            Attending with:
                                                        </span>
                                                        <div
                                                            class="flex gap-2 flex-wrap">
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
                                                                v-if="
                                                                    !gig
                                                                        .people_attending
                                                                        .length
                                                                "
                                                                class="text-sm text-gray-500">
                                                                No one
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- RIGHT -->
                                            <div
                                                class="flex items-center gap-2 sm:gap-3 mt-4 sm:mt-0">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click.stop="
                                                        openEditGigModal(gig)
                                                    ">
                                                    <Edit
                                                        class="h-4 w-4 text-gray-400" />
                                                </Button>
                                                <Button
                                                    @click.stop="
                                                        gig.sets
                                                            ? router.visit(
                                                                  route(
                                                                      'gigs.show',
                                                                      gig.id
                                                                  )
                                                              )
                                                            : openSetlistModal(
                                                                  gig
                                                              )
                                                    "
                                                    class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-4 sm:px-6">
                                                    <Eye class="h-4 w-4 mr-2" />
                                                    <span>
                                                        {{
                                                            gig.sets?.length
                                                                ? "View Setlist"
                                                                : "Generate Setlist"
                                                        }}
                                                    </span>
                                                </Button>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        <!-- PAST -->
                        <div v-if="searchResults.past.length">
                            <h3
                                class="text-xl font-semibold text-emerald-400 mb-3">
                                Past Gigs
                            </h3>
                            <div class="grid gap-4 sm:gap-6 grid-cols-1">
                                <Card
                                    v-for="gig in searchResults.past"
                                    :key="gig.id"
                                    class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-emerald-500/10 group cursor-pointer"
                                    @click="openGigDetailModal(gig)">
                                    <CardContent class="p-4 sm:p-6">
                                        <div
                                            class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sm:gap-0">
                                            <div
                                                class="flex items-center gap-3 sm:gap-4 w-full sm:w-auto">
                                                <Avatar
                                                    class="h-12 w-12 sm:h-16 sm:w-16 ring-2 ring-gray-600 group-hover:ring-emerald-500/50 transition-all duration-300">
                                                    <AvatarImage
                                                        v-if="
                                                            gig.artist_image_url
                                                        "
                                                        :src="
                                                            gig.artist_image_url
                                                        "
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

                                                <div
                                                    class="space-y-1 sm:space-y-2">
                                                    <h3
                                                        class="text-lg sm:text-xl font-semibold text-white group-hover:text-emerald-400 transition-colors">
                                                        {{
                                                            gig.artist_band_name
                                                        }}
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
                                                            : openSetlistModal(
                                                                  gig
                                                              )
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

                        <div
                            v-if="
                                !searchResults.upcoming.length &&
                                !searchResults.past.length &&
                                !searching
                            "
                            class="text-gray-400">
                            No gigs found.
                        </div>
                    </div>

                    <!-- If no search active, render the page normally -->
                    <slot v-else />
                </main>
            </div>
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
</template>

<style>
/* Define a generic fade transition (as a fallback or for simple transitions) */
.page-slide-enter-active,
.page-slide-leave-active {
    transition: all 0.5s ease-in-out; /* Match pageTransition duration */
}
.page-slide-enter-from {
    opacity: 0;
    transform: translateY(20px); /* Matches initial.y */
}
.page-slide-leave-to {
    opacity: 0;
    transform: translateY(-20px); /* Matches exit.y */
}
</style>
