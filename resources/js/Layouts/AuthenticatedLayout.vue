<script setup>
import { computed, ref } from "vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";

import {
    Calendar,
    Music,
    Users,
    Search,
    LogOut,
    Settings,
    Menu,
    X,
    BarChart3, // Basic Lucide icons
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

const user = usePage().props.auth.user;

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
</script>

<template>
    <div class="min-h-screen bg-[#212121] text-white">
        <div class="flex flex-col md:flex-row">
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
            <div class="flex-1 md:ml-0">
                <!-- Header -->
                <div
                    class="bg-[#212121] border-b border-gray-700 p-4 sm:p-6 sticky top-0 z-20">
                    <div
                        class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sm:gap-0">
                        <!-- Removed motion.div -->
                        <div>
                            <h1
                                class="text-lg sm:text-2xl font-semibold text-white">
                                Hi {{ user.name }}
                            </h1>
                            <p class="mt-1 text-xs sm:text-base text-gray-400">
                                Ready to create some amazing setlists?
                            </p>
                        </div>

                        <div
                            class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-4 w-full sm:w-auto">
                            <div class="relative w-full sm:w-auto">
                                <Search
                                    class="absolute left-3 top-1/2 -translate-y-1/2 transform text-gray-400 h-4 w-4" />
                                <Input
                                    placeholder="Search by Album title, UPC, Artist"
                                    class="w-full sm:w-80 pl-10 bg-[#191919] border-gray-600 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200 text-xs sm:text-base" />
                            </div>

                            <!-- Profile Dropdown -->
                            <DropdownMenu>
                                <DropdownMenuTrigger
                                    class="relative h-8 w-8 cursor-pointer rounded-full ring-2 ring-emerald-500/30 transition-all hover:ring-emerald-500/50">
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
                <main class="p-4 sm:p-6">
                    <Transition
                        name="page-slide"
                        mode="out-in"
                        :duration="pageTransition.duration * 1000">
                        <div
                            :key="currentPage"
                            v-motion-slide-visible-once-bottom
                            :initial="pageVariants.initial"
                            :animate="pageVariants.in"
                            :exit="pageVariants.out"
                            :transition="pageTransition">
                            <slot />
                        </div>
                    </Transition>
                </main>
            </div>
        </div>
    </div>
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
