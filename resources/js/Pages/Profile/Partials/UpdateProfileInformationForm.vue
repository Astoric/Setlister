<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue"; // Import computed
// New UI Components
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    // The main profile page will pass these explicitly
    name: { type: String, required: true },
    email: { type: String, required: true },
});

const user = usePage().props.auth.user; // User for email verification status

const form = useForm({
    name: props.name,
    email: props.email,
});

/**
 * Handles form submission for profile information update.
 */
const updateProfileInformation = () => {
    form.patch(route("profile.update"), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by parent (Profile/Edit.vue) via flash message
        },
        onError: () => {
            // Errors displayed by InputError components
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h3 class="text-xl font-semibold text-white mb-2">
                Profile Information
            </h3>
            <p class="text-gray-400 text-sm">
                Update your account's profile information and email address.
            </p>
        </header>

        <form @submit.prevent="updateProfileInformation" class="mt-6 space-y-6">
            <div>
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    class="mt-1 block w-full bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                    v-model="form.email"
                    required
                    autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-400">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-emerald-400 underline hover:text-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-emerald-400">
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="form.processing"
                    class="text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 transition-all duration-200">
                    Save Changes
                </Button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0">
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-500">
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
