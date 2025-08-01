<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import UpdateSpotifyAppCredentialsForm from "./Partials/UpdateSpotifyAppCredentialsForm.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const user = usePage().props.auth.user;

// Access flash messages
const flashSuccess = computed(() => usePage().props.flash?.success || null);
const flashError = computed(() => usePage().props.flash?.error || null);

// Check Spotify connection status
const isSpotifyConnected = computed(() => !!user.spotify_access_token);
const spotifyExpiresAt = computed(() =>
    user.spotify_token_expires_at
        ? new Date(user.spotify_token_expires_at).toLocaleString()
        : "N/A"
);
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Flash Messages for Spotify Connection Status and general profile updates -->
                <div
                    v-if="flashSuccess"
                    class="mb-4 rounded-lg bg-accent-500 px-4 py-3 shadow-md text-neutral-900"
                    v-html="flashSuccess"></div>
                <div
                    v-if="flashError"
                    class="mb-4 rounded-lg bg-red-500 px-4 py-3 shadow-md text-white">
                    {{ flashError }}
                </div>

                <!-- Spotify Connection Section -->
                <div class="p-4 sm:p-8 rounded-xl bg-neutral-800 shadow">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-white">
                                Spotify Connection
                            </h2>
                            <p class="mt-1 text-sm text-neutral-400">
                                Connect your Spotify account to generate
                                playlists.
                            </p>
                        </header>

                        <div class="mt-6">
                            <div
                                v-if="isSpotifyConnected"
                                class="flex items-center font-semibold text-accent-500">
                                <svg
                                    class="w-5 h-5 mr-2"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                Spotify Connected!
                                <span class="ml-4 text-sm text-neutral-500">
                                    Token expires: {{ spotifyExpiresAt }}
                                </span>
                            </div>
                            <div
                                v-else
                                class="flex items-center font-semibold text-red-500">
                                <svg
                                    class="w-5 h-5 mr-2"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                Spotify Not Connected
                            </div>

                            <a
                                :href="route('spotify.redirect')"
                                class="inline-block mt-4">
                                <PrimaryButton>
                                    {{
                                        isSpotifyConnected
                                            ? "Re-Connect Spotify"
                                            : "Connect Spotify"
                                    }}
                                </PrimaryButton>
                            </a>
                            <p class="mt-2 text-sm text-neutral-500">
                                You will be redirected to Spotify to authorize
                                this application.
                            </p>
                        </div>
                    </section>
                </div>

                <div class="p-4 sm:p-8 rounded-xl bg-neutral-800 shadow">
                    <UpdateSpotifyAppCredentialsForm class="max-w-xl" />
                </div>

                <!-- Update Profile Information Form -->
                <div class="p-4 sm:p-8 rounded-xl bg-neutral-800 shadow">
                    <UpdateProfileInformationForm
                        :name="user.name"
                        :email="user.email"
                        class="max-w-xl" />
                </div>

                <!-- Update Password Form -->
                <div class="p-4 sm:p-8 rounded-xl bg-neutral-800 shadow">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <!-- Delete User Form -->
                <div class="p-4 sm:p-8 rounded-xl bg-neutral-800 shadow">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
