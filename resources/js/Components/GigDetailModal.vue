<script setup>
import Modal from "@/Components/Modal.vue";
import { XMarkIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    show: {
        type: Boolean,
    },
    gig: {
        type: Object,
    },
});

const emit = defineEmits(["close"]);

/**
 * Formats date and time for display.
 */
const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) {
        return "N/A";
    }

    const options = {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    };

    return new Date(dateTimeString).toLocaleString("en-US", options);
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" :maxWidth="'lg'">
        <div class="relative rounded-lg bg-neutral-900 p-6 text-white">
            <h2 class="mb-4 text-center text-2xl font-bold">Gig Details</h2>

            <!-- Close button -->
            <button
                @click="emit('close')"
                class="absolute right-4 top-4 text-neutral-400 transition-colors hover:text-white">
                <XMarkIcon class="h-6 w-6" />
            </button>

            <div v-if="gig" class="space-y-4">
                <!-- Artist Name (Headline) -->
                <div class="mb-6 text-center">
                    <h3
                        class="text-4xl font-extrabold leading-tight text-accent-500">
                        {{ gig.artist_band_name }}
                    </h3>
                </div>

                <!-- Spotify Artist Image -->
                <div class="mb-6 flex justify-center">
                    <img
                        v-if="gig.artist_image_url"
                        :src="gig.artist_image_url"
                        alt="Artist Image"
                        class="h-32 w-32 rounded-full object-cover shadow-lg ring-2 ring-accent-500" />
                    <div
                        v-else
                        class="flex h-32 w-32 items-center justify-center overflow-hidden rounded-full bg-neutral-800 text-sm text-neutral-500">
                        No Image
                    </div>
                </div>

                <!-- Support Acts -->
                <div class="rounded-lg bg-neutral-800 p-4">
                    <p class="mb-1 text-sm text-neutral-400">Support:</p>
                    <p
                        v-if="
                            Array.isArray(gig.support_acts) &&
                            gig.support_acts.length
                        "
                        class="font-medium text-white">
                        {{ gig.support_acts.join(", ") }}
                    </p>
                    <p v-else class="text-sm text-neutral-500">
                        No support acts listed.
                    </p>
                </div>

                <!-- Venue -->
                <div class="rounded-lg bg-neutral-800 p-4">
                    <p class="mb-1 text-sm text-neutral-400">Venue:</p>
                    <p class="font-medium text-white">{{ gig.venue }}</p>
                </div>

                <!-- Date & Time -->
                <div class="rounded-lg bg-neutral-800 p-4">
                    <p class="mb-1 text-sm text-neutral-400">Date & Time:</p>
                    <p class="font-medium text-white">
                        {{ formatDateTime(gig.gig_date_time) }}
                    </p>
                </div>

                <!-- People Attending -->
                <div class="rounded-lg bg-neutral-800 p-4">
                    <p class="mb-1 text-sm text-neutral-400">Going:</p>
                    <div
                        v-if="
                            Array.isArray(gig.people_attending) &&
                            gig.people_attending.length
                        "
                        class="flex flex-wrap gap-2">
                        <span
                            v-for="person in gig.people_attending"
                            :key="person"
                            class="rounded-full bg-accent-500 px-3 py-1 text-xs font-medium text-neutral-900 shadow">
                            {{ person }}
                        </span>
                    </div>
                    <p v-else class="text-sm text-neutral-500">
                        No one else is going (yet!).
                    </p>
                </div>
            </div>
            <div v-else class="py-8 text-center text-neutral-500">
                Loading gig details...
            </div>
        </div>
    </Modal>
</template>
