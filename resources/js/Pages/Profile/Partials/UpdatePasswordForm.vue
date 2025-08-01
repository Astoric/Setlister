<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";
// New UI Components
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

/**
 * Handles password update.
 */
const updatePassword = () => {
    form.put(route("password.update"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: () => {
            if (form.errors.password) {
                form.reset("password", "password_confirmation");
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset("current_password");
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h3 class="text-xl font-semibold text-white mb-2">
                Update Password
            </h3>
            <p class="text-gray-400 text-sm">
                Ensure your account is using a long, random password to stay
                secure.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div>
                <Label for="current-password">Current Password</Label>
                <Input
                    id="current-password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                    autocomplete="current-password" />
                <InputError
                    :message="form.errors.current_password"
                    class="mt-2" />
            </div>

            <div>
                <Label for="new-password">New Password</Label>
                <Input
                    id="new-password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                    autocomplete="new-password" />
                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div>
                <Label for="confirm-password">Confirm Password</Label>
                <Input
                    id="confirm-password"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                    autocomplete="new-password" />
                <InputError
                    :message="form.errors.password_confirmation"
                    class="mt-2" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="form.processing"
                    class="text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 transition-all duration-200">
                    Update Password
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
