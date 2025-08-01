<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";
import { useForm, usePage } from "@inertiajs/vue3"; // REMOVED 'router' import
import { computed } from "vue";

// Lucide Icons
import { Check, ExternalLink, Music } from "lucide-vue-next";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";

const user = usePage().props.auth.user;

const form = useForm({
    spotify_app_client_id: user.spotify_app_client_id || "",
    spotify_app_client_secret: user.spotify_app_client_secret || "",
});

/**
 * Handles form submission to update Spotify app credentials.
 */
const updateSpotifyAppCredentials = () => {
    form.patch(route("profile.update-spotify-app-credentials"), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by parent (Profile/Edit.vue) via flash message
        },
        onError: () => {
            // Errors will be displayed by InputError components
        },
    });
};

/**
 * Checks if Spotify App credentials are set.
 */
const hasAppCredentials = computed(
    () => !!user.spotify_app_client_id && !!user.spotify_app_client_secret
);

/**
 * Checks if Spotify account is connected (via access token).
 */
const isSpotifyAccountConnected = computed(() => !!user.spotify_access_token);

/**
 * Formats Spotify token expiration date for display.
 */
const spotifyTokenExpiresAtDisplay = computed(() =>
    user.spotify_token_expires_at
        ? new Date(user.spotify_token_expires_at).toLocaleString()
        : "N/A"
);

// --- MODIFIED: Method to initiate Spotify OAuth redirect using native browser navigation ---
const redirectToSpotifyAuth = () => {
    // THIS IS THE DEFINITIVE FIX FOR CORS ON OAUTH REDIRECTS
    window.location.href = route("spotify.redirect"); // Forces full browser page redirect
};
// --- END MODIFIED ---
</script>

<template>
    <section>
        <header>
            <h3 class="text-xl font-semibold text-white mb-2">
                Spotify Connection
            </h3>
            <p class="mt-1 text-sm text-gray-400">
                Connect your Spotify account to generate playlists.
            </p>
        </header>

        <Card class="bg-[#191919] border-gray-600">
            <CardContent class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                            <Music class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <p class="text-white font-medium">Spotify</p>
                            <div class="flex items-center gap-2">
                                <template v-if="isSpotifyAccountConnected">
                                    <Check class="w-4 h-4 text-green-400" />
                                    <span class="text-green-400 text-sm">
                                        Connected
                                    </span>
                                </template>
                                <template v-else>
                                    <span class="text-gray-400 text-sm">
                                        Not connected
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <Button
                        v-if="isSpotifyAccountConnected"
                        @click="redirectToSpotifyAuth"
                        variant="outline"
                        class="border-gray-600 text-gray-300 hover:bg-gray-700 hover:text-white transition-all duration-200">
                        Re-Connect
                    </Button>
                    <Button
                        v-else
                        @click="redirectToSpotifyAuth"
                        class="bg-green-500 hover:bg-green-600 transition-all duration-200">
                        <ExternalLink class="w-4 h-4 mr-2" />
                        Connect Account
                    </Button>
                </div>

                <div class="pt-4 border-t border-gray-700">
                    <p class="text-sm text-gray-400 mb-4">
                        Last updated: {{ spotifyTokenExpiresAtDisplay }}
                    </p>

                    <p class="mt-1 text-sm text-neutral-400 mb-4">
                        To use this app, please create an app on the
                        <a
                            href="https://developer.spotify.com/dashboard/applications"
                            target="_blank"
                            class="font-semibold underline text-emerald-400 hover:text-emerald-300">
                            Spotify Developer Dashboard
                        </a>
                        . Ensure the callback URL is set to
                        <span
                            class="font-mono text-xs rounded bg-neutral-700 px-1 py-0.5 text-neutral-300">
                            https://setlister.co.uk/auth/spotify/callback
                        </span>
                        and Web API is checked. After creation, please input
                        your Client ID and Client Secret.
                    </p>

                    <div class="space-y-4">
                        <div>
                            <Label for="spotify-client-id">
                                Spotify App Client ID
                            </Label>
                            <Input
                                id="spotify-client-id"
                                type="text"
                                class="mt-1 block w-full bg-[#212121] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                v-model="form.spotify_app_client_id"
                                autocomplete="off" />
                            <InputError
                                class="mt-2"
                                :message="form.errors.spotify_app_client_id" />
                        </div>

                        <div>
                            <Label for="spotify-client-secret">
                                Spotify App Client Secret
                            </Label>
                            <Input
                                id="spotify-client-secret"
                                type="password"
                                class="mt-1 block w-full bg-[#212121] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                v-model="form.spotify_app_client_secret"
                                autocomplete="off" />
                            <InputError
                                class="mt-2"
                                :message="
                                    form.errors.spotify_app_client_secret
                                " />
                        </div>

                        <Button
                            @click.prevent="updateSpotifyAppCredentials"
                            class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 transition-all duration-200">
                            <Check
                                v-if="form.recentlySuccessful"
                                class="mr-2 h-4 w-4" />
                            {{
                                hasAppCredentials
                                    ? "Update Credentials"
                                    : "Save Credentials"
                            }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </section>
</template>
