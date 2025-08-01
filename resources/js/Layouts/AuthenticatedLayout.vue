<script setup>
import { ref } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";

const user = usePage().props.auth.user;

/**
 * Determines if a navigation link is active.
 */
const isActive = (path) => usePage().url.startsWith(path);
</script>

<template>
    <div class="flex min-h-screen bg-neutral-900 text-white">
        <!-- Persistent Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 flex w-64 flex-col justify-between p-6 shadow-lg bg-neutral-950 text-white">
            <div>
                <!-- Logo & Title -->
                <div class="mb-8 flex shrink-0 items-center">
                    <Link :href="route('dashboard')">
                        <ApplicationLogo
                            class="block h-9 w-auto fill-current text-white" />
                    </Link>
                    <span class="ml-3 text-2xl font-semibold text-accent-500">
                        Setlister
                    </span>
                </div>

                <!-- Navigation Links -->
                <nav class="space-y-4">
                    <!-- Upcoming Gigs (Default) -->
                    <Link
                        :href="route('dashboard')"
                        class="flex items-center rounded-lg p-3 transition-colors duration-200"
                        :class="{
                            'bg-accent-500 text-neutral-950':
                                route().current('dashboard'),
                            'hover:bg-neutral-800':
                                !route().current('dashboard'),
                        }">
                        <svg
                            class="mr-3 h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        Upcoming Gigs
                    </Link>
                    <!-- Past Gigs -->
                    <Link
                        :href="route('past-gigs')"
                        class="flex items-center rounded-lg p-3 transition-colors duration-200"
                        :class="{
                            'bg-accent-500 text-neutral-950':
                                route().current('past-gigs'),
                            'hover:bg-neutral-800':
                                !route().current('past-gigs'),
                        }">
                        <svg
                            class="mr-3 h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                fill-rule="evenodd"
                                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Past Gigs
                    </Link>
                    <!-- Saved Setlists -->
                    <Link
                        :href="route('saved-setlists')"
                        class="flex items-center rounded-lg p-3 transition-colors duration-200"
                        :class="{
                            'bg-accent-500 text-neutral-950':
                                route().current('saved-setlists'),
                            'hover:bg-neutral-800':
                                !route().current('saved-setlists'),
                        }">
                        <svg
                            class="mr-3 h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Saved Setlists
                    </Link>
                </nav>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="ml-64 flex flex-1 flex-col">
            <!-- Header -->
            <header
                class="flex h-20 items-center justify-between p-6 shadow-md bg-neutral-900">
                <h2 class="text-xl font-semibold leading-tight text-white">
                    Hi {{ user.name }}
                </h2>

                <div class="flex w-1/3 items-center space-x-4">
                    <!-- Search Bar -->
                    <div class="relative flex-grow">
                        <input
                            type="text"
                            placeholder="Search by Album title, UPC, Artist"
                            class="w-full rounded-lg border border-neutral-700 bg-neutral-700 py-2 pl-10 pr-4 text-white focus:outline-none focus:ring-2 focus:ring-accent-500" />
                        <MagnifyingGlassIcon
                            class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 transform text-neutral-400" />
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- User Profile Dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-md border border-transparent bg-neutral-700 px-3 py-2 text-sm font-medium leading-4 text-white transition-colors duration-150 focus:outline-none hover:text-neutral-300">
                                <!-- User Profile Picture -->
                                <img
                                    :src="
                                        user.spotify_profile_picture_url ||
                                        `https://ui-avatars.com/api/?name=${user.name}&color=7F9CF5&background=EBF4FF`
                                    "
                                    alt="User Avatar"
                                    class="mr-2 h-8 w-8 rounded-full object-cover" />
                                {{ user.name }}

                                <svg
                                    class="ml-2 h-4 w-4"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </template>

                        <!-- Dropdown Content -->
                        <template #content>
                            <div
                                class="rounded-lg bg-neutral-800 py-1 shadow-lg">
                                <DropdownLink
                                    :href="route('profile.edit')"
                                    class="block px-4 py-2 text-sm text-neutral-300 hover:bg-neutral-700 hover:text-white">
                                    Profile
                                </DropdownLink>
                                <DropdownLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="block w-full px-4 py-2 text-left text-sm text-neutral-300 hover:bg-neutral-700 hover:text-white">
                                    Log Out
                                </DropdownLink>
                            </div>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Page Content -->
            <main class="mb-6 mr-6 flex-1 rounded-lg bg-neutral-800 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
