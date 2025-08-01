<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { useForm, usePage, Link } from "@inertiajs/vue3"; // NEW: Import Link for external link
import { computed } from "vue";

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
            // Optional: Show success message or clear form recentlySuccessful state
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
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-white">
                Spotify App Credentials
            </h2>
            <p class="mt-1 text-sm text-neutral-400">
                To use this app, please create an app on the
                <a
                    href="https://developer.spotify.com/dashboard/applications"
                    target="_blank"
                    class="font-semibold underline text-accent-500 hover:text-accent-400">
                    Spotify Developer Dashboard
                </a>
                . Ensure the callback URL is set to
                <span
                    class="font-mono text-xs text-neutral-300 bg-neutral-700 px-1 py-0.5 rounded">
                    https://setlister.co.uk/auth/spotify/callback
                </span>
                and Web API is checked. After creation, please input your Client
                ID and Client Secret.
            </p>
        </header>

        <form
            @submit.prevent="updateSpotifyAppCredentials"
            class="mt-6 space-y-6">
            <div>
                <InputLabel
                    for="spotify_app_client_id"
                    value="Spotify App Client ID"
                    class="text-neutral-400" />
                <TextInput
                    id="spotify_app_client_id"
                    type="text"
                    class="mt-1 block w-full bg-neutral-700 text-white border-neutral-600 focus:border-accent-500 focus:ring-accent-500"
                    v-model="form.spotify_app_client_id"
                    autocomplete="off" />
                <InputError
                    class="mt-2"
                    :message="form.errors.spotify_app_client_id" />
            </div>

            <div>
                <InputLabel
                    for="spotify_app_client_secret"
                    value="Spotify App Client Secret"
                    class="text-neutral-400" />
                <TextInput
                    id="spotify_app_client_secret"
                    type="text"
                    class="mt-1 block w-full bg-neutral-700 text-white border-neutral-600 focus:border-accent-500 focus:ring-accent-500"
                    v-model="form.spotify_app_client_secret"
                    autocomplete="off" />
                <InputError
                    class="mt-2"
                    :message="form.errors.spotify_app_client_secret" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    {{
                        hasAppCredentials
                            ? "Update Credentials"
                            : "Save Credentials"
                    }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0">
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-neutral-500">
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
