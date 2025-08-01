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
        <div class="flex">
            <!-- Sidebar -->
            <div
                :class="`${
                    sidebarCollapsed ? 'w-16' : 'w-64'
                } bg-[#121212] border-r border-gray-800 min-h-screen transition-all duration-300`"
                :style="{ width: sidebarCollapsed ? '64px' : '256px' }">
                <div class="p-4">
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
                            class="p-2 text-gray-400 transition-all duration-200 hover:bg-gray-700 hover:text-white">
                            <!-- Removed motion.div around icons -->
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
                        <!-- MODIFIED: Add items-center and gap-3 to ensure inline alignment -->
                        <Button
                            :variant="
                                currentPage.startsWith('Upcoming')
                                    ? 'secondary'
                                    : 'ghost'
                            "
                            as-child
                            :class="`${
                                sidebarCollapsed
                                    ? 'w-full justify-center px-2'
                                    : 'w-full justify-start gap-3'
                            } ${
                                currentPage.startsWith('Upcoming')
                                    ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border-emerald-500/30 text-emerald-400 hover:from-emerald-500/30 hover:to-teal-500/30'
                                    : 'text-gray-400 hover:text-white hover:bg-gray-700'
                            } transition-all duration-200`">
                            <Link
                                :href="route('dashboard')"
                                :title="sidebarCollapsed ? 'Upcoming Gigs' : ''"
                                class="flex items-center gap-3">
                                <!-- ADDED: flex items-center gap-3 -->
                                <Calendar class="h-4 w-4" />
                                <span v-if="!sidebarCollapsed">
                                    Upcoming Gigs
                                </span>
                            </Link>
                        </Button>
                        <Button
                            :variant="
                                currentPage.startsWith('Past')
                                    ? 'secondary'
                                    : 'ghost'
                            "
                            as-child
                            :class="`${
                                sidebarCollapsed
                                    ? 'w-full justify-center px-2'
                                    : 'w-full justify-start gap-3'
                            } ${
                                currentPage.startsWith('Past')
                                    ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border-emerald-500/30 text-emerald-400 hover:from-emerald-500/30 hover:to-teal-500/30'
                                    : 'text-gray-400 hover:text-white hover:bg-gray-700'
                            } transition-all duration-200`">
                            <Link
                                :href="route('past-gigs')"
                                :title="sidebarCollapsed ? 'Past Gigs' : ''"
                                class="flex items-center gap-3">
                                <!-- ADDED: flex items-center gap-3 -->
                                <Music class="h-4 w-4" />
                                <span v-if="!sidebarCollapsed">Past Gigs</span>
                            </Link>
                        </Button>
                        <Button
                            :variant="
                                currentPage.startsWith('SavedSetlists') ||
                                currentPage.startsWith('SetlistDetail')
                                    ? 'secondary'
                                    : 'ghost'
                            "
                            as-child
                            :class="`${
                                sidebarCollapsed
                                    ? 'w-full justify-center px-2'
                                    : 'w-full justify-start gap-3'
                            } ${
                                currentPage.startsWith('SavedSetlists') ||
                                currentPage.startsWith('SetlistDetail')
                                    ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border-emerald-500/30 text-emerald-400 hover:from-emerald-500/30 hover:to-teal-500/30'
                                    : 'text-gray-400 hover:text-white hover:bg-gray-700'
                            } transition-all duration-200`">
                            <Link
                                :href="route('saved-setlists')"
                                :title="
                                    sidebarCollapsed ? 'Saved Setlists' : ''
                                "
                                class="flex items-center gap-3">
                                <!-- ADDED: flex items-center gap-3 -->
                                <Users class="h-4 w-4" />
                                <span v-if="!sidebarCollapsed">
                                    Saved Setlists
                                </span>
                            </Link>
                        </Button>
                        <Button
                            :variant="
                                currentPage.startsWith('Stats')
                                    ? 'secondary'
                                    : 'ghost'
                            "
                            as-child
                            :class="`${
                                sidebarCollapsed
                                    ? 'w-full justify-center px-2'
                                    : 'w-full justify-start gap-3'
                            } ${
                                currentPage.startsWith('Stats')
                                    ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border-emerald-500/30 text-emerald-400 hover:from-emerald-500/30 hover:to-teal-500/30'
                                    : 'text-gray-400 hover:text-white hover:bg-gray-700'
                            } transition-all duration-200`">
                            <Link
                                :href="route('stats.index')"
                                :title="sidebarCollapsed ? 'Stats' : ''"
                                class="flex items-center gap-3">
                                <!-- ADDED: flex items-center gap-3 -->
                                <BarChart3 class="h-4 w-4" />
                                <span v-if="!sidebarCollapsed">Stats</span>
                            </Link>
                        </Button>
                        <Button
                            :variant="
                                currentPage.startsWith('Profile')
                                    ? 'secondary'
                                    : 'ghost'
                            "
                            as-child
                            :class="`${
                                sidebarCollapsed
                                    ? 'w-full justify-center px-2'
                                    : 'w-full justify-start gap-3'
                            } ${
                                currentPage.startsWith('Profile')
                                    ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border-emerald-500/30 text-emerald-400 hover:from-emerald-500/30 hover:to-teal-500/30'
                                    : 'text-gray-400 hover:text-white hover:bg-gray-700'
                            } transition-all duration-200`">
                            <Link
                                :href="route('profile.edit')"
                                :title="sidebarCollapsed ? 'Profile' : ''"
                                class="flex items-center gap-3">
                                <!-- ADDED: flex items-center gap-3 -->
                                <Settings class="h-4 w-4" />
                                <span v-if="!sidebarCollapsed">Profile</span>
                            </Link>
                        </Button>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1">
                <!-- Header -->
                <div class="bg-[#212121] border-b border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <!-- Removed motion.div -->
                        <div>
                            <h1 class="text-2xl font-semibold text-white">
                                Hi {{ user.name }}
                            </h1>
                            <p class="mt-1 text-gray-400">
                                Ready to create some amazing setlists?
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <Search
                                    class="absolute left-3 top-1/2 -translate-y-1/2 transform text-gray-400 h-4 w-4" />
                                <Input
                                    placeholder="Search by Album title, UPC, Artist"
                                    class="w-80 pl-10 bg-[#191919] border-gray-600 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200" />
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
                <main class="p-6">
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
