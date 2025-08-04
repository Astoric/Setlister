<script setup>
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
// Fix: Import all Lucide icons explicitly that are used in the template
import { X, MapPin, Calendar, Clock, Music, Users } from "lucide-vue-next";
// Fix: Import all Dialog sub-components explicitly that are used in the template
import Dialog from "@/Components/ui/Dialog.vue";
import DialogContent from "@/Components/ui/DialogContent.vue";
import DialogHeader from "@/Components/ui/DialogHeader.vue";
import DialogTitle from "@/Components/ui/DialogTitle.vue";
import DialogDescription from "@/Components/ui/DialogDescription.vue";
import DialogClose from "@/Components/ui/DialogClose.vue"; // If used directly in template
import DialogPortal from "@/Components/ui/DialogPortal.vue";
// No need for DialogClose, DialogPortal here, they are internal to DialogContent

// Import other UI components used
import Avatar from "@/Components/ui/Avatar.vue";
import AvatarImage from "@/Components/ui/AvatarImage.vue";
import AvatarFallback from "@/Components/ui/AvatarFallback.vue";
import Badge from "@/Components/ui/Badge.vue";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";

const props = defineProps({
    show: { type: Boolean }, // Controls modal visibility
    gig: { type: Object }, // The gig object to display details for
});

const emit = defineEmits(["close"]);

/**
 * Formats date and time for display.
 */
const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return "N/A";
    const options = {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    };
    return new Date(dateTimeString).toLocaleString("en-US", options);
};

/**
 * Gets avatar fallback initials.
 */
const getAvatarFallback = (name) => {
    return name
        ? name
              .split(" ")
              .map((n) => n[0])
              .join("")
              .substring(0, 2)
              .toUpperCase()
        : "??";
};
</script>

<template>
    <!-- FIX: Add v-if="show" to the root Dialog component -->
    <Dialog
        :open="show"
        @update:open="emit('close')"
        :max-width="'lg'"
        v-if="show">
        <DialogContent
            class="bg-[#191919] border-gray-600 text-white w-full max-w-[95vw] sm:max-w-lg p-2 sm:p-6 rounded-lg sm:rounded-2xl overflow-y-auto max-h-[90vh]">
            <DialogHeader>
                <div class="mb-4 flex justify-center">
                    <Avatar class="h-24 w-24 ring-2 ring-emerald-500/30">
                        <AvatarImage
                            v-if="gig.artist_image_url"
                            :src="gig.artist_image_url"
                            :alt="gig.artist_band_name" />
                        <AvatarFallback
                            v-else
                            class="bg-gradient-to-r from-gray-700 to-gray-600 text-3xl text-white">
                            {{ getAvatarFallback(gig.artist_band_name) }}
                        </AvatarFallback>
                    </Avatar>
                </div>
                <DialogTitle
                    class="text-center text-4xl font-extrabold leading-tight text-white">
                    {{ gig.artist_band_name }}
                </DialogTitle>
                <DialogDescription class="text-center text-gray-400">
                    Detailed information about this gig.
                </DialogDescription>
            </DialogHeader>

            <div v-if="gig" class="space-y-4 border-t border-gray-700 pt-4">
                <!-- Venue & Date/Time Block -->
                <div
                    class="flex flex-col justify-around gap-4 text-center text-gray-400 sm:flex-row">
                    <div class="flex items-center justify-center gap-2">
                        <MapPin class="h-4 w-4" />
                        <span>{{ gig.venue }}</span>
                    </div>
                    <div class="flex items-center justify-center gap-2">
                        <Calendar class="h-4 w-4" />
                        <span>{{ formatDateTime(gig.gig_date_time) }}</span>
                    </div>
                </div>

                <!-- Support Acts -->
                <Card class="bg-[#191919] border-gray-600">
                    <CardContent class="p-4">
                        <p
                            class="mb-2 flex items-center gap-2 text-sm text-gray-400">
                            <Music class="h-4 w-4" />
                            Support Acts:
                        </p>
                        <span
                            v-if="
                                (Array.isArray(gig.support_acts)
                                    ? gig.support_acts
                                    : []
                                ).length > 0
                            "
                            class="font-medium text-white">
                            {{
                                (Array.isArray(gig.support_acts)
                                    ? gig.support_acts
                                    : []
                                ).join(", ")
                            }}
                        </span>
                        <span v-else class="text-sm text-gray-500">
                            No support acts listed.
                        </span>
                    </CardContent>
                </Card>

                <!-- People Attending -->
                <Card class="bg-[#191919] border-gray-600">
                    <CardContent class="p-4">
                        <p
                            class="mb-2 flex items-center gap-2 text-sm text-gray-400">
                            <Users class="h-4 w-4" />
                            Going:
                        </p>
                        <div
                            v-if="
                                (Array.isArray(gig.people_attending)
                                    ? gig.people_attending
                                    : []
                                ).length > 0
                            "
                            class="flex flex-wrap gap-2">
                            <Badge
                                v-for="person in gig.people_attending"
                                :key="person"
                                variant="secondary"
                                class="bg-emerald-500/20 text-emerald-400 border-emerald-500/30">
                                {{ person }}
                            </Badge>
                        </div>
                        <span v-else class="text-sm text-gray-500">
                            No one else is going (yet!).
                        </span>
                    </CardContent>
                </Card>
            </div>
            <div v-else class="py-8 text-center text-neutral-500">
                Loading gig details...
            </div>
        </DialogContent>
    </Dialog>
</template>
