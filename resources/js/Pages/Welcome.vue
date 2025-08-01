<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue"; // Import ref, computed, watch
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Checkbox from "@/Components/Checkbox.vue";
import { ArrowLeftIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
    formType: {
        type: String,
        default: "welcome",
    },
    status: {
        type: String,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const currentForm = ref(props.formType);

watch(
    () => props.formType,
    (newType) => {
        currentForm.value = newType;
    }
);

const loginForm = useForm({
    email: "",
    password: "",
    remember: false,
});

const registerForm = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const pageStatus = computed(() => usePage().props.status);
const pageErrors = computed(() => usePage().props.errors);

const combinedErrors = computed(() => {
    return {
        ...pageErrors.value,
        ...(currentForm.value === "login"
            ? loginForm.errors
            : registerForm.errors),
    };
});

const submitLogin = () => {
    loginForm.post(route("login"), {
        onFinish: () => {
            loginForm.reset("password");
            loginForm.clearErrors();
        },
    });
};

const submitRegister = () => {
    registerForm.post(route("register"), {
        onFinish: () => {
            registerForm.reset("password", "password_confirmation");
            registerForm.clearErrors();
        },
    });
};
</script>

<template>
    <Head title="Welcome to Setlister" />

    <div
        class="min-h-screen bg-gradient-to-br from-accent-500 to-neutral-900 flex items-center justify-center p-4">
        <!-- Centered Card -->
        <div
            class="bg-neutral-900 rounded-2xl p-8 w-full max-w-md shadow-2xl relative">
            <!-- Back Button for forms -->
            <button
                v-if="currentForm !== 'welcome'"
                @click="currentForm = 'welcome'"
                class="absolute top-4 left-3 text-neutral-400 hover:text-white transition-colors p-2 rounded-full hover:bg-neutral-800"
                aria-label="Back to welcome">
                <ArrowLeftIcon class="h-6 w-6" />
            </button>
            <!-- Spotify Logo -->
            <div class="flex justify-center mb-6">
                <div
                    class="w-16 h-16 rounded-full flex items-center justify-center">
                    <img
                        src="/images/logo.svg"
                        alt="Setlister Logo"
                        class="h-15 w-auto" />
                </div>
            </div>

            <h1 class="text-white text-2xl font-medium mb-2 text-center">
                Welcome to Setlister
            </h1>
            <p class="text-neutral-400 text-sm mb-8 text-center">
                {{
                    currentForm === "login"
                        ? "Please sign in to your account"
                        : currentForm === "register"
                        ? "Create your Setlister account"
                        : "Please sign in to your account"
                }}
            </p>

            <!-- Global Status / Error Messages -->
            <div
                v-if="pageStatus"
                class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                {{ pageStatus }}
            </div>
            <div
                v-if="
                    pageErrors.email ||
                    pageErrors.password ||
                    pageErrors.name ||
                    pageErrors.password_confirmation
                "
                class="mb-4">
                <InputError class="mt-2" :message="combinedErrors.email" />
                <InputError class="mt-2" :message="combinedErrors.password" />
                <InputError class="mt-2" :message="combinedErrors.name" />
                <InputError
                    class="mt-2"
                    :message="combinedErrors.password_confirmation" />
            </div>

            <!-- Welcome Page Buttons (Default State) -->
            <div v-if="currentForm === 'welcome'" class="space-y-4">
                <Link
                    v-if="canLogin"
                    :href="route('login')"
                    @click.prevent="currentForm = 'login'"
                    class="w-full bg-accent-500 hover:bg-accent-600 text-neutral-900 rounded-xl px-6 h-10 text-sm font-medium transition-all duration-200 hover:scale-[1.02] flex items-center justify-center"
                    style="line-height: 1.5"
                    aria-label="Login">
                    Login
                </Link>

                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    @click.prevent="currentForm = 'register'"
                    class="w-full border border-accent-500 text-accent-500 hover:bg-accent-500 hover:text-neutral-900 rounded-xl px-6 h-10 text-sm font-medium bg-transparent transition-all duration-200 hover:scale-[1.02] flex items-center justify-center"
                    style="line-height: 1.5"
                    aria-label="Register">
                    Register
                </Link>
            </div>

            <!-- Login Form -->
            <form
                v-else-if="currentForm === 'login'"
                @submit.prevent="submitLogin">
                <div class="mb-4">
                    <InputLabel
                        for="login-email"
                        value="Email"
                        class="text-neutral-400" />
                    <TextInput
                        id="login-email"
                        type="email"
                        class="mt-1 block w-full bg-neutral-800 text-white border-neutral-700 focus:border-accent-500 focus:ring-accent-500"
                        v-model="loginForm.email"
                        required
                        autofocus
                        autocomplete="username" />
                    <InputError
                        class="mt-2"
                        :message="loginForm.errors.email" />
                </div>

                <div class="mt-4">
                    <InputLabel
                        for="login-password"
                        value="Password"
                        class="text-neutral-400" />
                    <TextInput
                        id="login-password"
                        type="password"
                        class="mt-1 block w-full bg-neutral-800 text-white border-neutral-700 focus:border-accent-500 focus:ring-accent-500"
                        v-model="loginForm.password"
                        required
                        autocomplete="current-password" />
                    <InputError
                        class="mt-2"
                        :message="loginForm.errors.password" />
                </div>

                <div class="block mt-4">
                    <label class="flex items-center">
                        <Checkbox
                            name="remember"
                            v-model:checked="loginForm.remember"
                            class="form-checkbox text-accent-500 focus:ring-accent-500 rounded-sm border-neutral-700 bg-neutral-800" />
                        <span class="ms-2 text-sm text-neutral-400">
                            Remember me
                        </span>
                    </label>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <Link
                        :href="route('password.request')"
                        class="underline text-sm text-neutral-400 hover:text-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent-500"
                        tabindex="-1">
                        Forgot your password?
                    </Link>

                    <PrimaryButton
                        class="ms-4 bg-accent-500 hover:bg-accent-600 text-neutral-900 rounded-xl px-6 h-10 text-sm font-medium transition-all duration-200"
                        :class="{ 'opacity-25': loginForm.processing }"
                        :disabled="loginForm.processing">
                        Log in
                    </PrimaryButton>
                </div>
                <div class="flex items-center justify-center mt-4">
                    <button
                        type="button"
                        @click="currentForm = 'register'"
                        class="underline text-sm text-neutral-400 hover:text-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent-500">
                        Don't have an account? Register
                    </button>
                </div>
            </form>

            <!-- Register Form -->
            <form
                v-else-if="currentForm === 'register'"
                @submit.prevent="submitRegister">
                <div>
                    <InputLabel
                        for="register-name"
                        value="Name"
                        class="text-neutral-400" />
                    <TextInput
                        id="register-name"
                        type="text"
                        class="mt-1 block w-full bg-neutral-800 text-white border-neutral-700 focus:border-accent-500 focus:ring-accent-500"
                        v-model="registerForm.name"
                        required
                        autofocus
                        autocomplete="name" />
                    <InputError
                        class="mt-2"
                        :message="registerForm.errors.name" />
                </div>

                <div class="mt-4">
                    <InputLabel
                        for="register-email"
                        value="Email"
                        class="text-neutral-400" />
                    <TextInput
                        id="register-email"
                        type="email"
                        class="mt-1 block w-full bg-neutral-800 text-white border-neutral-700 focus:border-accent-500 focus:ring-accent-500"
                        v-model="registerForm.email"
                        required
                        autocomplete="username" />
                    <InputError
                        class="mt-2"
                        :message="registerForm.errors.email" />
                </div>

                <div class="mt-4">
                    <InputLabel
                        for="register-password"
                        value="Password"
                        class="text-neutral-400" />
                    <TextInput
                        id="register-password"
                        type="password"
                        class="mt-1 block w-full bg-neutral-800 text-white border-neutral-700 focus:border-accent-500 focus:ring-accent-500"
                        v-model="registerForm.password"
                        required
                        autocomplete="new-password" />
                    <InputError
                        class="mt-2"
                        :message="registerForm.errors.password" />
                </div>

                <div class="mt-4">
                    <InputLabel
                        for="register-password_confirmation"
                        value="Confirm Password"
                        class="text-neutral-400" />
                    <TextInput
                        id="register-password_confirmation"
                        type="password"
                        class="mt-1 block w-full bg-neutral-800 text-white border-neutral-700 focus:border-accent-500 focus:ring-accent-500"
                        v-model="registerForm.password_confirmation"
                        required
                        autocomplete="new-password" />
                    <InputError
                        class="mt-2"
                        :message="registerForm.errors.password_confirmation" />
                </div>

                <div class="flex items-center justify-between mt-6">
                    <button
                        type="button"
                        @click="currentForm = 'login'"
                        class="underline text-sm text-neutral-400 hover:text-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent-500">
                        Already registered?
                    </button>

                    <PrimaryButton
                        class="ms-4 bg-accent-500 hover:bg-accent-600 text-neutral-900 rounded-xl px-6 h-10 text-sm font-medium transition-all duration-200"
                        :class="{ 'opacity-25': registerForm.processing }"
                        :disabled="registerForm.processing">
                        Register
                    </PrimaryButton>
                </div>
            </form>

            <!-- Additional subtle elements -->
            <div class="mt-8 text-center">
                <p class="text-neutral-500 text-xs">
                    By continuing, you agree to our Terms & Privacy Policy
                </p>
            </div>
        </div>
    </div>
</template>
