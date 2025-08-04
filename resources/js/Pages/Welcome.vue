<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
// New UI Components
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import InputError from "@/Components/InputError.vue"; // Needed for validation errors

// Lucide Icons
import {
    Mail,
    Lock,
    User,
    Eye,
    EyeOff,
    Music,
    ArrowLeft,
} from "lucide-vue-next";

const props = defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
    formType: { type: String, default: "welcome" },
    status: { type: String },
    errors: { type: Object, default: () => ({}) },
    token: { type: String, default: null },
    email: { type: String, default: null },
});

// State to manage which form is visible
const loginState = ref(props.formType);

// State for password visibility toggle
const showPassword = ref(false);
const showConfirmPassword = ref(false);

// Watch the formType prop to update loginState if URL changes
watch(
    () => props.formType,
    (newType) => {
        loginState.value = newType;
    }
);

// Form data (useForm instances)
const loginData = useForm({ email: "", password: "", remember: false });
const registerData = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});
const forgotPasswordData = useForm({ email: "" });
const resetPasswordData = useForm({
    token: props.token,
    email: props.email,
    password: "",
    password_confirmation: "",
});

// Global status/error messages from backend
const pageStatus = computed(() => usePage().props.status);
const pageErrors = computed(() => usePage().props.errors);

// Helper to get error message for a given form instance and field name
const getError = (formInstance, fieldName) => {
    const formError = formInstance.errors[fieldName];
    if (formError) return formError;

    if (
        pageErrors.value &&
        pageErrors.value[fieldName] &&
        Array.isArray(pageErrors.value[fieldName])
    ) {
        return pageErrors.value[fieldName][0];
    }
    return null;
};

// Form submission handlers
const handleLogin = () => {
    loginData.post(route("login"), {
        onFinish: () => {
            loginData.reset("password");
            loginData.clearErrors();
        },
    });
};

const handleRegister = () => {
    registerData.post(route("register"), {
        onFinish: () => {
            registerData.reset("password", "password_confirmation");
            registerData.clearErrors();
        },
    });
};

const handleForgotPassword = () => {
    forgotPasswordData.post(route("password.email"), {
        onSuccess: () => {
            forgotPasswordData.reset();
        },
        onError: () => {},
    });
};

const handleResetPassword = () => {
    resetPasswordData.post(route("password.store"), {
        onSuccess: () => {},
        onError: () => {
            resetPasswordData.reset("password", "password_confirmation");
        },
    });
};
</script>

<template>
    <Head title="Welcome to Setlister" />

    <!-- Main Page Container with Gradient -->
    <div
        class="min-h-screen bg-gradient-to-br from-emerald-400 via-emerald-500 to-teal-600 flex items-center justify-center p-4 sm:p-6">
        <Card
            class="w-full max-w-md bg-[#121212] border-gray-700 shadow-2xl sm:rounded-2xl rounded-lg">
            <CardContent class="p-4 sm:p-8">
                <!-- Header Section (Logo, Title, Subtitle) -->
                <div class="text-center mb-6 sm:mb-8">
                    <div
                        class="w-14 h-14 sm:w-16 sm:h-16 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                        <img
                            src="/images/logo.svg"
                            alt="Setlister Logo"
                            class="h-12 w-auto sm:h-15" />
                    </div>
                    <h1
                        class="text-xl sm:text-2xl font-semibold text-white mb-1 sm:mb-2">
                        Welcome to Setlister
                    </h1>
                    <p class="text-gray-400 text-xs sm:text-sm">
                        <template v-if="loginState === 'welcome'">
                            Please sign in to your account
                        </template>
                        <template v-else-if="loginState === 'login'">
                            Sign in to your account
                        </template>
                        <template v-else-if="loginState === 'register'">
                            Create your new account
                        </template>
                        <template v-else-if="loginState === 'forgot'">
                            Reset your password
                        </template>
                        <template v-else-if="loginState === 'reset'">
                            Create new password
                        </template>
                        <template v-else>
                            Please sign in to your account
                        </template>
                    </p>
                </div>

                <!-- Global Status / Error Messages -->
                <Transition name="fade" mode="out-in">
                    <div
                        v-if="pageStatus"
                        key="status-message"
                        class="mb-4 text-xs sm:text-sm font-medium text-emerald-400">
                        {{ pageStatus }}
                    </div>
                    <div
                        v-else-if="Object.keys(pageErrors).length > 0"
                        key="error-messages"
                        class="mb-4 text-xs sm:text-sm text-red-400">
                        <p
                            v-for="(errorMessages, field) in pageErrors"
                            :key="field">
                            <span v-if="Array.isArray(errorMessages)">
                                {{ errorMessages.join(", ") }}
                            </span>
                            <span v-else>{{ errorMessages }}</span>
                        </p>
                    </div>
                </Transition>

                <!-- Form Views (Conditional Rendering with Transition) -->
                <Transition name="fade" mode="out-in">
                    <!-- welcome Buttons View -->
                    <div
                        v-if="loginState === 'welcome'"
                        key="welcome"
                        class="space-y-3 sm:space-y-4"
                        v-motion-fade-visible
                        :welcome="{ opacity: 0, y: 20 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :exit="{ opacity: 0, y: -20 }"
                        :duration="500">
                        <Button
                            v-if="canLogin"
                            @click="loginState = 'login'"
                            class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white shadow-lg shadow-emerald-500/25 transition-all duration-200 text-base sm:text-lg py-2 sm:py-3"
                            size="lg">
                            Login
                        </Button>

                        <Button
                            v-if="canRegister"
                            @click="loginState = 'register'"
                            class="w-full bg-[#121212] border-2 border-emerald-500 text-emerald-400 hover:bg-emerald-500/10 hover:text-emerald-300 hover:border-emerald-400 transition-all duration-200 text-base sm:text-lg py-2 sm:py-3"
                            size="lg">
                            Register
                        </Button>
                    </div>

                    <!-- Login Form -->
                    <form
                        v-else-if="loginState === 'login'"
                        key="login"
                        @submit.prevent="handleLogin"
                        class="space-y-5 sm:space-y-6"
                        v-motion-fade-visible
                        :welcome="{ opacity: 0, y: 20 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :exit="{ opacity: 0, y: -20 }"
                        :duration="500">
                        <div class="space-y-1 sm:space-y-2">
                            <Label for="email">Email</Label>
                            <div class="relative mt-1">
                                <Mail
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="Enter your email"
                                    v-model="loginData.email"
                                    class="w-full pl-10 bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                    required
                                    autocomplete="username" />
                            </div>
                            <InputError
                                class="mt-2"
                                :message="getError(loginData, 'email')" />
                        </div>

                        <div class="space-y-1 sm:space-y-2">
                            <Label for="password">Password</Label>
                            <div class="relative mt-1">
                                <Lock
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                                <Input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Enter your password"
                                    v-model="loginData.password"
                                    class="pl-10 pr-10 bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                    required
                                    autocomplete="current-password" />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-300 transition-colors">
                                    <Eye v-if="!showPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError
                                class="mt-2"
                                :message="getError(loginData, 'password')" />
                        </div>

                        <div
                            class="flex flex-col sm:flex-row items-center justify-between gap-2 sm:gap-0">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    v-model="loginData.remember"
                                    class="rounded border-gray-600 bg-gray-700 text-emerald-500 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500/50" />
                                <span class="ml-2 text-sm text-gray-400">
                                    Remember me
                                </span>
                            </label>
                            <Button
                                variant="link"
                                @click.prevent="loginState = 'forgot'"
                                class="text-sm text-emerald-400 hover:text-emerald-300">
                                Forgot password?
                            </Button>
                        </div>

                        <div class="space-y-2 sm:space-y-3">
                            <Button
                                type="submit"
                                :disabled="loginData.processing"
                                class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white shadow-lg shadow-emerald-500/25 transition-all duration-200"
                                size="lg">
                                Sign In
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                @click="loginState = 'welcome'"
                                class="w-full text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200"
                                size="lg">
                                Back
                            </Button>
                        </div>
                    </form>

                    <!-- Register Form -->
                    <form
                        v-else-if="loginState === 'register'"
                        key="register"
                        @submit.prevent="handleRegister"
                        class="space-y-5 sm:space-y-6"
                        v-motion-fade-visible
                        :welcome="{ opacity: 0, y: 20 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :exit="{ opacity: 0, y: -20 }"
                        :duration="500">
                        <div class="space-y-1 sm:space-y-2">
                            <Label for="name">Full Name</Label>
                            <div class="relative">
                                <User
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                                <Input
                                    id="name"
                                    type="text"
                                    placeholder="Enter your full name"
                                    v-model="registerData.name"
                                    class="w-full pl-10 bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                    required
                                    autocomplete="name" />
                            </div>
                            <InputError
                                class="mt-2"
                                :message="getError(registerData, 'name')" />
                        </div>

                        <div class="space-y-1 sm:space-y-2">
                            <Label for="email">Email</Label>
                            <div class="relative">
                                <Mail
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="Enter your email"
                                    v-model="registerData.email"
                                    class="w-full pl-10 bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                    required
                                    autocomplete="username" />
                            </div>
                            <InputError
                                class="mt-2"
                                :message="getError(registerData, 'email')" />
                        </div>

                        <div class="space-y-1 sm:space-y-2">
                            <Label for="password">Password</Label>
                            <div class="relative">
                                <Lock
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                                <Input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Create a password"
                                    v-model="registerData.password"
                                    class="pl-10 pr-10 bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                    required
                                    autocomplete="new-password" />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-300 transition-colors">
                                    <Eye v-if="!showPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError
                                class="mt-2"
                                :message="getError(registerData, 'password')" />
                        </div>

                        <div class="space-y-2">
                            <Label for="confirmPassword">
                                Confirm Password
                            </Label>
                            <div class="relative">
                                <Lock
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                                <Input
                                    id="confirmPassword"
                                    :type="
                                        showConfirmPassword
                                            ? 'text'
                                            : 'password'
                                    "
                                    placeholder="Confirm your password"
                                    v-model="registerData.password_confirmation"
                                    class="w-full pl-10 pr-10 bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                    required
                                    autocomplete="new-password" />
                                <button
                                    type="button"
                                    @click="
                                        showConfirmPassword =
                                            !showConfirmPassword
                                    "
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-300 transition-colors">
                                    <Eye
                                        v-if="!showConfirmPassword"
                                        class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError
                                class="mt-2"
                                :message="
                                    getError(
                                        registerData,
                                        'password_confirmation'
                                    )
                                " />
                        </div>

                        <div class="space-y-2 sm:space-y-3">
                            <Button
                                type="submit"
                                :disabled="registerData.processing"
                                class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white shadow-lg shadow-emerald-500/25 transition-all duration-200"
                                size="lg">
                                Create Account
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                @click="loginState = 'welcome'"
                                class="w-full text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200"
                                size="lg">
                                Back
                            </Button>
                        </div>
                    </form>

                    <!-- Forgot Password Form -->
                    <form
                        v-else-if="loginState === 'forgot'"
                        key="forgot"
                        @submit.prevent="handleForgotPassword"
                        class="space-y-5 sm:space-y-6"
                        v-motion-fade-visible
                        :welcome="{ opacity: 0, y: 20 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :exit="{ opacity: 0, y: -20 }"
                        :duration="500">
                        <div class="space-y-1 sm:space-y-2">
                            <Label for="forgot-email">Email</Label>
                            <div class="relative mt-1">
                                <Mail
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                                <Input
                                    id="forgot-email"
                                    type="email"
                                    placeholder="Enter your email"
                                    v-model="forgotPasswordData.email"
                                    class="w-full pl-10 bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                    required
                                    autofocus />
                            </div>
                            <InputError
                                class="mt-2"
                                :message="
                                    getError(forgotPasswordData, 'email')
                                " />
                        </div>
                        <p class="text-sm text-gray-400">
                            We'll send you a link to reset your password.
                        </p>

                        <div class="space-y-3">
                            <Button
                                type="submit"
                                :disabled="forgotPasswordData.processing"
                                class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white shadow-lg shadow-emerald-500/25 transition-all duration-200"
                                size="lg">
                                Send Reset Link
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                @click="loginState = 'login'"
                                class="w-full text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200"
                                size="lg">
                                Back to Login
                            </Button>
                        </div>
                    </form>

                    <!-- Reset Password Form -->
                    <form
                        v-else-if="loginState === 'reset'"
                        key="reset"
                        @submit.prevent="handleResetPassword"
                        class="space-y-6"
                        v-motion-fade-visible
                        :welcome="{ opacity: 0, y: 20 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :exit="{ opacity: 0, y: -20 }"
                        :duration="500">
                        <div class="space-y-1 sm:space-y-2">
                            <Label for="reset-email">Email</Label>
                            <div class="relative mt-1">
                                <Mail
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                                <Input
                                    id="reset-email"
                                    type="email"
                                    placeholder="Enter your email"
                                    v-model="resetPasswordData.email"
                                    class="w-full pl-10 bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                    required
                                    autocomplete="username" />
                            </div>
                            <InputError
                                class="mt-2"
                                :message="
                                    getError(resetPasswordData, 'email')
                                " />
                        </div>

                        <div class="space-y-2">
                            <Label for="reset-password-new">New Password</Label>
                            <div class="relative mt-1">
                                <Lock
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                                <Input
                                    id="reset-password-new"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Create a password"
                                    v-model="resetPasswordData.password"
                                    class="w-full pl-10 pr-10 bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                    required
                                    autocomplete="new-password" />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-300 transition-colors">
                                    <Eye v-if="!showPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError
                                class="mt-2"
                                :message="
                                    getError(resetPasswordData, 'password')
                                " />
                        </div>

                        <div class="space-y-2">
                            <Label for="reset-password-confirm">
                                Confirm Password
                            </Label>
                            <div class="relative mt-1">
                                <Lock
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                                <Input
                                    id="reset-password-confirm"
                                    :type="
                                        showConfirmPassword
                                            ? 'text'
                                            : 'password'
                                    "
                                    placeholder="Confirm your password"
                                    v-model="
                                        resetPasswordData.password_confirmation
                                    "
                                    class="w-full pl-10 pr-10 bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                                    required
                                    autocomplete="new-password" />
                                <button
                                    type="button"
                                    @click="
                                        showConfirmPassword =
                                            !showConfirmPassword
                                    "
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-300 transition-colors">
                                    <Eye
                                        v-if="!showConfirmPassword"
                                        class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError
                                class="mt-2"
                                :message="
                                    getError(
                                        resetPasswordData,
                                        'password_confirmation'
                                    )
                                " />
                        </div>

                        <div class="space-y-2 sm:space-y-3">
                            <Button
                                type="submit"
                                :disabled="resetPasswordData.processing"
                                class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white shadow-lg shadow-emerald-500/25 transition-all duration-200"
                                size="lg">
                                Reset Password
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                @click="loginState = 'login'"
                                class="w-full text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200"
                                size="lg">
                                Back to Login
                            </Button>
                        </div>
                    </form>
                </Transition>

                <!-- Terms & Privacy Policy (only for welcome view) -->
                <div
                    v-if="loginState === 'welcome'"
                    class="mt-4 sm:mt-6 text-center">
                    <p class="text-[10px] sm:text-xs text-gray-500">
                        By continuing, you agree to our
                        <a
                            href="#"
                            class="text-emerald-400 hover:text-emerald-300 transition-colors">
                            Terms
                        </a>
                        &
                        <a
                            href="#"
                            class="text-emerald-400 hover:text-emerald-300 transition-colors">
                            Privacy Policy
                        </a>
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
