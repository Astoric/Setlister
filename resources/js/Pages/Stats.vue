<script setup>
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

// New UI Components
import Button from "@/Components/ui/Button.vue"; // For generic buttons if any
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import Avatar from "@/Components/ui/Avatar.vue"; // Might not be directly used but imported
import AvatarImage from "@/Components/ui/AvatarImage.vue";
import AvatarFallback from "@/Components/ui/AvatarFallback.vue";
import Badge from "@/Components/ui/Badge.vue"; // Might not be directly used but imported
import Input from "@/Components/ui/Input.vue"; // Not directly used on this page
import Label from "@/Components/ui/Label.vue"; // Not directly used on this page
import Dialog from "@/Components/ui/Dialog.vue"; // Not directly used on this page
import DialogContent from "@/Components/ui/DialogContent.vue";
import DialogHeader from "@/Components/ui/DialogHeader.vue";
import DialogTitle from "@/Components/ui/DialogTitle.vue";
import DialogDescription from "@/Components/ui/DialogDescription.vue";

// Lucide Icons (as used in App.jsx for stats)
import {
    Music,
    Star,
    Volume2,
    Clock,
    MapPin,
    Users,
    Calendar,
    TrendingUp,
    Award,
} from "lucide-vue-next";

const props = defineProps({
    stats: {
        // The stats object passed from StatsController
        type: Object,
        default: () => ({
            totalPastGigs: 0,
            totalUpcomingGigs: 0,
            totalGigs: 0,
            totalSetlists: 0,
            averageRating: "0.0", // String for consistent formatting
            totalSongs: 0,
            totalDuration: "0h 0m",
            topVenue: "N/A",
            topAttendee: "N/A",
            lastUpdated: "N/A",
            gigsAttendedThisYear: 0,
            upcomingGigsThisYear: 0,
            achievements: [],
        }),
    },
});

// Render stars (reusing logic from App.jsx)
const renderStars = (rating) => {
    return Array.from({ length: 5 }, (_, i) =>
        h(Star, {
            // Use h() to render Lucide Star component
            key: i,
            class: `w-3 h-3 transition-colors duration-200 ${
                i < rating ? "text-yellow-400 fill-current" : "text-gray-500"
            }`,
        })
    );
};
</script>

<template>
    <Head title="Your Music Stats" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header for Stats Page -->
                <div class="mb-8">
                    <h2
                        class="text-3xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
                        Your Music Stats
                    </h2>
                    <p class="mt-2 text-gray-400">
                        Track your musical journey and discover patterns in your
                        listening habits
                    </p>
                </div>

                <!-- Main Stats Grid -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4 mb-8">
                    <!-- Total Gigs Card -->
                    <Card
                        class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300">
                        <CardHeader
                            class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle
                                class="text-sm font-medium text-gray-400">
                                Total Gigs
                            </CardTitle>
                            <Music class="h-4 w-4 text-emerald-400" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-white">
                                {{ stats.totalGigs }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ stats.totalPastGigs }} completed,
                                {{ stats.totalUpcomingGigs }} upcoming
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Average Rating Card -->
                    <Card
                        class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300">
                        <CardHeader
                            class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle
                                class="text-sm font-medium text-gray-400">
                                Average Rating
                            </CardTitle>
                            <Star
                                class="h-4 w-4 text-yellow-400 fill-current" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-white">
                                {{ stats.averageRating }}
                            </div>
                            <div class="flex items-center mt-1">
                                <!-- Pass parsed float to renderStars -->
                                <template
                                    v-if="parseFloat(stats.averageRating) > 0">
                                    <component
                                        :is="
                                            renderStars(
                                                parseFloat(stats.averageRating)
                                            )
                                        " />
                                </template>
                                <span v-else class="text-sm text-gray-500">
                                    N/A
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Total Songs Card -->
                    <Card
                        class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300">
                        <CardHeader
                            class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle
                                class="text-sm font-medium text-gray-400">
                                Total Songs
                            </CardTitle>
                            <Volume2 class="h-4 w-4 text-emerald-400" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-white">
                                {{ stats.totalSongs }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Across {{ stats.totalPastGigs }} gigs
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Music Time Card -->
                    <Card
                        class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300">
                        <CardHeader
                            class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle
                                class="text-sm font-medium text-gray-400">
                                Music Time
                            </CardTitle>
                            <Clock class="h-4 w-4 text-emerald-400" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-white">
                                {{ stats.totalDuration }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Of saved setlists
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Top Venue & Top Concert Buddy Grid -->
                <div class="grid gap-6 md:grid-cols-2 mb-8">
                    <!-- Top Venue Card -->
                    <Card
                        class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300">
                        <CardHeader>
                            <CardTitle
                                class="text-lg font-semibold text-white flex items-center gap-2">
                                <MapPin class="h-5 w-5 text-emerald-400" />
                                Top Venue
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-xl font-semibold text-emerald-400">
                                {{ stats.topVenue }}
                            </div>
                            <p class="text-sm text-gray-400 mt-2">
                                Your most visited venue
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Top Concert Buddy Card -->
                    <Card
                        class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300">
                        <CardHeader>
                            <CardTitle
                                class="text-lg font-semibold text-white flex items-center gap-2">
                                <Users class="h-5 w-5 text-emerald-400" />
                                Top Concert Buddy
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-xl font-semibold text-emerald-400">
                                {{ stats.topAttendee }}
                            </div>
                            <p class="text-sm text-gray-400 mt-2">
                                Your most frequent concert companion
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Recent Activity & Achievements Grid -->
                <div class="grid gap-6 md:grid-cols-3">
                    <!-- Recent Activity Card -->
                    <Card
                        class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300">
                        <CardHeader>
                            <CardTitle
                                class="text-lg font-semibold text-white flex items-center gap-2">
                                <Calendar class="h-5 w-5 text-emerald-400" />
                                Recent Activity
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    class="flex items-center justify-between text-sm">
                                    <span class="text-gray-400">
                                        Setlists saved
                                    </span>
                                    <span class="font-semibold text-white">
                                        {{ stats.totalSetlists }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between text-sm">
                                    <span class="text-gray-400">
                                        Last updated
                                    </span>
                                    <span class="text-emerald-400">
                                        {{ stats.lastUpdated }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- This Year Stats Card -->
                    <Card
                        class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300">
                        <CardHeader>
                            <CardTitle
                                class="text-lg font-semibold text-white flex items-center gap-2">
                                <TrendingUp class="h-5 w-5 text-emerald-400" />
                                This Year
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    class="flex items-center justify-between text-sm">
                                    <span class="text-gray-400">
                                        Gigs attended
                                    </span>
                                    <span class="font-semibold text-white">
                                        {{ stats.gigsAttendedThisYear }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between text-sm">
                                    <span class="text-gray-400">
                                        Upcoming gigs
                                    </span>
                                    <span class="text-emerald-400">
                                        {{ stats.upcomingGigsThisYear }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Achievements Card -->
                    <Card
                        class="bg-[#191919] border-gray-600 hover:border-emerald-500/50 transition-all duration-300">
                        <CardHeader>
                            <CardTitle
                                class="text-lg font-semibold text-white flex items-center gap-2">
                                <Award class="h-5 w-5 text-emerald-400" />
                                Achievements
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="achievement in stats.achievements"
                                    :key="achievement.label"
                                    class="flex items-center gap-2">
                                    <div
                                        :class="`w-2 h-2 rounded-full ${
                                            achievement.unlocked
                                                ? 'bg-emerald-400'
                                                : 'bg-gray-600'
                                        }`"></div>
                                    <span
                                        :class="`text-sm ${
                                            achievement.unlocked
                                                ? 'text-gray-300'
                                                : 'text-gray-500'
                                        }`">
                                        {{ achievement.label }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
