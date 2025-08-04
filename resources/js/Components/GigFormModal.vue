<script setup>
import { computed, ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";

// New UI Components
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";
import Dialog from "@/Components/ui/Dialog.vue";
import DialogContent from "@/Components/ui/DialogContent.vue";
import DialogHeader from "@/Components/ui/DialogHeader.vue";
import DialogTitle from "@/Components/ui/DialogTitle.vue";
import DialogDescription from "@/Components/ui/DialogDescription.vue";
import Badge from "@/Components/ui/Badge.vue"; // For tags
// Not needed anymore: TagInput

// Lucide Icons
import { Calendar, Clock, Trash2, X, AlertTriangle } from "lucide-vue-next";

const props = defineProps({
    show: { type: Boolean },
    gig: { type: Object }, // Optional prop for the gig object (if in edit mode)
});

const emit = defineEmits(["close"]);

const form = useForm({
    artist_band_name: "",
    venue: "",
    gig_date_time: "",
    support_acts: [], // Will be array from this component's internal logic
    people_attending: [], // Will be array from this component's internal logic
});

// Internal refs for the individual tag input fields
const supportActInput = ref("");
const attendeeInput = ref("");

// State for delete confirmation modal
const confirmingGigDeletion = ref(false);

/**
 * Watches changes in modal visibility and gig prop to initialize or reset the form.
 */
watch(
    () => [props.show, props.gig],
    ([newShow, newGig]) => {
        if (newShow) {
            // Populate form fields if in edit mode
            if (newGig) {
                form.artist_band_name = newGig.artist_band_name;
                form.venue = newGig.venue;
                form.gig_date_time = newGig.gig_date_time
                    ? new Date(newGig.gig_date_time).toISOString().slice(0, 16)
                    : "";

                // Assign arrays directly, ensuring they are actual arrays or empty arrays
                form.support_acts = Array.isArray(newGig.support_acts)
                    ? newGig.support_acts
                    : [];
                form.people_attending = Array.isArray(newGig.people_attending)
                    ? newGig.people_attending
                    : [];
            } else {
                // Add mode: Reset form to empty with explicit array types
                form.reset({
                    artist_band_name: "",
                    venue: "",
                    gig_date_time: "",
                    support_acts: [],
                    people_attending: [],
                });
            }
            form.clearErrors(); // Clear any previous errors

            // Clear internal tag input fields when modal opens
            supportActInput.value = "";
            attendeeInput.value = "";
        } else {
            // Modal is closing: Always reset form and clear errors
            form.reset({
                artist_band_name: "",
                venue: "",
                gig_date_time: "",
                support_acts: [],
                people_attending: [],
            });
            form.clearErrors();
            confirmingGigDeletion.value = false; // Reset confirmation state on close
            supportActInput.value = ""; // Clear internal tag input fields on close
            attendeeInput.value = "";
        }
    },
    { immediate: true } // Run immediately on component mount/initial show
);

/**
 * Dynamically determines the modal title.
 */
const modalTitle = computed(() => (props.gig ? "Edit Gig" : "Add New Gig"));

/**
 * Dynamically determines the submit button text.
 */
const submitButtonText = computed(() =>
    props.gig ? "Save Changes" : "Create Gig"
);

/**
 * Helper to process text input into tags for supportActs and peopleAttending.
 */
const processTagInput = (inputRef, currentTagsArray) => {
    const inputString = inputRef.value;
    if (inputString.trim() === "") return currentTagsArray; // Return current if input is empty

    const newTags = inputString
        .split(",")
        .map((tag) => tag.trim())
        .filter((tag) => tag.length > 0);

    const combinedTags = [...new Set([...currentTagsArray, ...newTags])]; // Ensure uniqueness

    inputRef.value = ""; // Clear the input field

    return combinedTags;
};

/**
 * Handles adding a support act on Enter key or blur.
 */
const handleAddSupportAct = (event) => {
    if (event.key === "Enter" || event.type === "blur") {
        event.preventDefault(); // Prevent form submission or native blur action
        form.support_acts = processTagInput(supportActInput, form.support_acts);
    }
};

/**
 * Handles removing a support act.
 */
const handleRemoveSupportAct = (index) => {
    form.support_acts = form.support_acts.filter((_, i) => i !== index);
};

/**
 * Handles adding an attendee on Enter key or blur.
 */
const handleAddAttendee = (event) => {
    if (event.key === "Enter" || event.type === "blur") {
        event.preventDefault(); // Prevent form submission or native blur action
        form.people_attending = processTagInput(
            attendeeInput,
            form.people_attending
        );
    }
};

/**
 * Handles removing an attendee.
 */
const handleRemoveAttendee = (index) => {
    form.people_attending = form.people_attending.filter((_, i) => i !== index);
};

/**
 * Handles form submission for both adding and editing gigs.
 */
const submit = () => {
    // Process any remaining text in tag inputs before final submission
    form.support_acts = processTagInput(supportActInput, form.support_acts);
    form.people_attending = processTagInput(
        attendeeInput,
        form.people_attending
    );

    form.transform((data) => {
        let utcDateTime = null;
        if (data.gig_date_time) {
            const localDate = new Date(data.gig_date_time);
            utcDateTime = localDate.toISOString();
        }

        return {
            ...data,
            gig_date_time: utcDateTime,
            support_acts:
                data.support_acts && data.support_acts.length > 0
                    ? JSON.stringify(data.support_acts)
                    : null,
            people_attending:
                data.people_attending && data.people_attending.length > 0
                    ? JSON.stringify(data.people_attending)
                    : null,
        };
    });

    if (props.gig) {
        form.patch(route("gigs.update", props.gig.id), {
            onSuccess: () => {
                emit("close");
                // For edit mode, form reset is handled by watch when modal closes
            },
            onError: () => {
                // Errors displayed by InputError components
            },
        });
    } else {
        form.post(route("gigs.store"), {
            onSuccess: () => {
                form.artist_band_name = ""; // Clear form fields after successful edit
                form.venue = ""; // Clear form fields after successful edit
                form.gig_date_time = ""; // Clear form fields after successful edit
                form.support_acts = [];
                form.people_attending = [];
                form.clearErrors();
                emit("close");
                // Reset form for next new gig entry (already handled by watch when modal closes)
            },
            onError: () => {
                // Errors displayed by InputError components
            },
        });
    }
};

const handleCancel = () => {
    emit("close");
    form.artist_band_name = ""; // Clear form fields after successful edit
    form.venue = ""; // Clear form fields after successful edit
    form.gig_date_time = ""; // Clear form fields after successful edit
    form.support_acts = [];
    form.people_attending = [];
    form.clearErrors();
};

/**
 * Initiates the gig deletion confirmation.
 */
const confirmGigDeletion = () => {
    confirmingGigDeletion.value = true;
};

/**
 * Deletes the gig.
 */
const deleteGig = () => {
    form.delete(route("gigs.destroy", props.gig.id), {
        onSuccess: () => {
            emit("close"); // Close the edit modal
        },
        onError: () => {
            // Error handling for deletion
        },
        onFinish: () => {
            confirmingGigDeletion.value = false; // Close confirmation modal
        },
    });
};

// Computed properties for date/time input splitting/joining
const dateInput = computed({
    get: () => (form.gig_date_time ? form.gig_date_time.split("T")[0] : ""),
    set: (value) => {
        const time = form.gig_date_time
            ? form.gig_date_time.split("T")[1]
            : "00:00";
        form.gig_date_time = value ? `${value}T${time}` : "";
    },
});

const timeInput = computed({
    get: () => (form.gig_date_time ? form.gig_date_time.split("T")[1] : ""),
    set: (value) => {
        const date = form.gig_date_time
            ? form.gig_date_time.split("T")[0]
            : "2000-01-01"; // Default date if time is set first
        form.gig_date_time = value ? `${date}T${value}` : "";
    },
});
</script>

<template>
    <Dialog
        :open="show"
        @update:open="emit('close')"
        :max-width="'2xl'"
        v-if="show">
        <DialogContent class="bg-[#191919] border-gray-600 text-white">
            <DialogHeader>
                <DialogTitle class="text-white text-2xl font-semibold">
                    {{ modalTitle }}
                </DialogTitle>
                <DialogDescription class="text-gray-400">
                    {{
                        gig
                            ? "Make changes to your gig details below."
                            : "Fill in the details for your new gig."
                    }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="mt-6 space-y-6">
                <!-- Artist/Band Name -->
                <div class="space-y-2">
                    <Label for="artist_band_name">Artist/Band Name</Label>
                    <Input
                        id="artist_band_name"
                        type="text"
                        class="mt-1 block w-full bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                        v-model="form.artist_band_name"
                        required
                        autofocus
                        autocomplete="off" />
                    <InputError
                        class="mt-2"
                        :message="form.errors.artist_band_name" />
                </div>

                <!-- Venue -->
                <div class="space-y-2">
                    <Label for="venue">Venue</Label>
                    <Input
                        id="venue"
                        type="text"
                        class="mt-1 block w-full bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                        v-model="form.venue"
                        required
                        autocomplete="off" />
                    <InputError class="mt-2" :message="form.errors.venue" />
                </div>

                <!-- Date & Time -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="gig_date_time_date">Date</Label>
                        <div class="relative">
                            <Input
                                id="gig_date_time_date"
                                type="date"
                                v-model="dateInput"
                                required
                                class="w-full bg-[#191919] border-gray-600 text-white focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200 pr-10" />
                            <Calendar
                                class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.gig_date_time" />
                    </div>
                    <div class="space-y-2">
                        <Label for="gig_date_time_time">Time</Label>
                        <div class="relative">
                            <Input
                                id="gig_date_time_time"
                                type="time"
                                v-model="timeInput"
                                required
                                class="w-full bg-[#191919] border-gray-600 text-white focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200 pr-10" />
                            <Clock
                                class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                        </div>
                    </div>
                </div>

                <!-- Support Acts -->
                <div class="space-y-2">
                    <Label for="support_acts_input">Support Acts</Label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <Badge
                            v-for="(act, index) in form.support_acts"
                            :key="index"
                            variant="secondary"
                            class="bg-emerald-500/20 text-emerald-400 border-emerald-500/30 px-3 py-1 flex items-center gap-2">
                            {{ act }}
                            <button
                                type="button"
                                @click="handleRemoveSupportAct(index)"
                                class="text-emerald-400 hover:text-emerald-300 transition-colors">
                                <X class="w-3 h-3" />
                            </button>
                        </Badge>
                    </div>
                    <Input
                        id="support_acts_input"
                        v-model="supportActInput"
                        @keydown.enter="handleAddSupportAct"
                        @blur="handleAddSupportAct"
                        placeholder="e.g., Artist A, Artist B (Press Enter to add)"
                        class="bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200" />
                    <InputError
                        class="mt-2"
                        :message="form.errors.support_acts" />
                </div>

                <!-- People Attending -->
                <div class="space-y-2">
                    <Label for="people_attending_input">People Attending</Label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <Badge
                            v-for="(attendee, index) in form.people_attending"
                            :key="index"
                            variant="secondary"
                            class="bg-emerald-500/20 text-emerald-400 border-emerald-500/30 px-3 py-1 flex items-center gap-2">
                            {{ attendee }}
                            <button
                                type="button"
                                @click="handleRemoveAttendee(index)"
                                class="text-emerald-400 hover:text-emerald-300 transition-colors">
                                <X class="w-3 h-3" />
                            </button>
                        </Badge>
                    </div>
                    <Input
                        id="people_attending_input"
                        v-model="attendeeInput"
                        @keydown.enter="handleAddAttendee"
                        @blur="handleAddAttendee"
                        placeholder="e.g., John Doe, Jane Smith (Press Enter to add)"
                        class="bg-[#191919] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200" />
                    <InputError
                        class="mt-2"
                        :message="form.errors.people_attending" />
                </div>
            </form>

            <!-- Modal Footer -->
            <div
                class="mt-8 flex items-center justify-between border-t border-gray-700 pt-6">
                <!-- Delete Button (shown only in Edit Mode) -->
                <div>
                    <Button
                        v-if="gig"
                        variant="destructive"
                        @click="confirmGigDeletion"
                        class="bg-red-600 hover:bg-red-700 text-white transition-all duration-200">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete Gig
                    </Button>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Cancel Button -->
                    <Button
                        variant="ghost"
                        @click="handleCancel()"
                        class="text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200">
                        Cancel
                    </Button>
                    <!-- Submit Button -->
                    <Button
                        @click="submit"
                        :disabled="form.processing"
                        class="text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 px-6 shadow-lg shadow-emerald-500/25 transition-all duration-200">
                        {{ submitButtonText }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Modal -->
    <Dialog
        :open="confirmingGigDeletion"
        @update:open="confirmingGigDeletion = $event"
        :max-width="'sm'"
        v-if="confirmingGigDeletion">
        <DialogContent class="bg-[#191919] border-gray-700 text-white">
            <DialogHeader>
                <div class="mb-4 flex justify-center text-red-400">
                    <AlertTriangle class="h-12 w-12" />
                </div>
                <DialogTitle
                    class="text-2xl font-semibold text-white text-center">
                    Are you sure?
                </DialogTitle>
                <DialogDescription class="text-gray-400 text-center">
                    This action is permanent and cannot be undone. All your data
                    will be permanently deleted.
                </DialogDescription>
            </DialogHeader>

            <div class="flex justify-end gap-3">
                <Button
                    variant="ghost"
                    @click="confirmingGigDeletion = false"
                    class="text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200">
                    Cancel
                </Button>
                <Button
                    @click="deleteGig"
                    :class="{
                        'opacity-75 cursor-not-allowed': form.processing,
                    }"
                    :disabled="form.processing"
                    variant="destructive"
                    class="bg-red-600 hover:bg-red-700 text-white transition-all duration-200">
                    Delete Gig
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
