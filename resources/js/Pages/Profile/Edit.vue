<script setup>
import { computed, ref, h } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

// Import all partials
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdateSpotifyAppCredentialsForm from "./Partials/UpdateSpotifyAppCredentialsForm.vue";

// New UI Components
import Button from "@/Components/ui/Button.vue";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import Dialog from "@/Components/ui/Dialog.vue"; // For generic modal structure
import DialogContent from "@/Components/ui/DialogContent.vue"; // For dialog content
import DialogHeader from "@/Components/ui/DialogHeader.vue"; // For dialog header
import DialogTitle from "@/Components/ui/DialogTitle.vue"; // For dialog title
import DialogDescription from "@/Components/ui/DialogDescription.vue"; // For dialog description

// Lucide Icons (as used in App.jsx for profile tabs)
import {
    User,
    Shield,
    Link,
    AlertTriangle,
    Check,
    ExternalLink,
    Music,
    Settings,
    LogOut,
} from "lucide-vue-next"; // Ensure all used icons are imported

const user = usePage().props.auth.user;

// Flash messages (from controller redirect)
const flashSuccess = computed(() => usePage().props.flash?.success || null);
const flashError = computed(() => usePage().props.flash?.error || null);

// State for active profile tab
const activeProfileTab = ref("profile"); // Default to 'profile' tab

// Profile tabs configuration from App.jsx
const profileTabs = [
    {
        id: "profile",
        label: "Profile Information",
        icon: User,
        description: "Update your personal information",
    },
    {
        id: "security",
        label: "Account Security",
        icon: Shield,
        description: "Manage your password and security settings",
    },
    {
        id: "integrations",
        label: "Integrations",
        icon: Link,
        description: "Connect your music streaming services",
    },
    {
        id: "danger",
        label: "Account Management",
        icon: AlertTriangle,
        description: "Manage your account and data",
    },
];

// Helper to render the content for the active tab
const renderProfileTabContent = () => {
    switch (activeProfileTab.value) {
        case "profile":
            return h(UpdateProfileInformationForm, {
                name: user.name,
                email: user.email,
            });
        case "security":
            return h(UpdatePasswordForm);
        case "integrations":
            return h(UpdateSpotifyAppCredentialsForm);
        case "danger":
            return h(DeleteUserForm);
        default:
            return null;
    }
};
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header for Profile Page -->
                <div class="mb-8">
                    <h2
                        class="text-3xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
                        Profile Settings
                    </h2>
                    <p class="mt-2 text-gray-400">
                        Manage your account settings and preferences
                    </p>
                </div>

                <!-- Flash Messages -->
                <div
                    v-if="flashSuccess"
                    class="mb-4 rounded-lg bg-accent-500 px-4 py-3 shadow-md text-neutral-900"
                    v-html="flashSuccess"></div>
                <div
                    v-if="flashError"
                    class="mb-4 rounded-lg bg-red-500 px-4 py-3 shadow-md text-white">
                    {{ flashError }}
                </div>

                <div class="grid gap-6 lg:grid-cols-4">
                    <!-- Vertical Tabs (Sidebar) -->
                    <Card class="lg:col-span-1 bg-[#191919] border-gray-600">
                        <CardContent class="p-0">
                            <nav class="space-y-1 p-2">
                                <button
                                    v-for="tab in profileTabs"
                                    :key="tab.id"
                                    @click="activeProfileTab = tab.id"
                                    class="flex w-full items-center gap-3 rounded-lg py-3 px-4 text-left transition-all duration-200"
                                    :class="{
                                        'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30':
                                            activeProfileTab === tab.id,
                                        'text-gray-400 hover:text-white hover:bg-gray-700/50':
                                            activeProfileTab !== tab.id,
                                    }">
                                    <component :is="tab.icon" class="h-4 w-4" />
                                    <!-- Render Lucide Icon dynamically -->
                                    <div class="flex-1">
                                        <div class="text-sm font-medium">
                                            {{ tab.label }}
                                        </div>
                                        <div class="text-xs opacity-70">
                                            {{ tab.description }}
                                        </div>
                                    </div>
                                </button>
                            </nav>
                        </CardContent>
                    </Card>

                    <!-- Tab Content -->
                    <Card class="lg:col-span-3 bg-[#191919] border-gray-600">
                        <CardContent class="p-6">
                            <!-- AnimatePresence and motion.div are Framer Motion (React-specific).
                                 For Vue, we use Vue's native <Transition> for simple fades.
                                 For more complex animations like initial/animate/exit with stagger,
                                 a Vue animation library like VueUse Motion or GSAP would be needed. -->
                            <Transition name="fade" mode="out-in">
                                <component
                                    :is="renderProfileTabContent()"
                                    :key="activeProfileTab" />
                            </Transition>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
