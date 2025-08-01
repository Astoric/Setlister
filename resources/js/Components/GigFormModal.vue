<script setup>
import { computed, watch, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TagInput from "@/Components/TagInput.vue";
import TextInput from "@/Components/TextInput.vue";
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

const form = useForm({
    artist_band_name: "",
    venue: "",
    gig_date_time: "",
    support_acts: [],
    people_attending: [],
});

const confirmingGigDeletion = ref(false);

/**
 * Watches changes in modal visibility and gig prop to initialize or reset the form.
 */
watch(
    () => [props.show, props.gig],
    ([newShow, newGig]) => {
        if (newShow) {
            if (newGig) {
                form.artist_band_name = newGig.artist_band_name;
                form.venue = newGig.venue;
                form.gig_date_time = newGig.gig_date_time
                    ? new Date(newGig.gig_date_time).toISOString().slice(0, 16)
                    : "";

                form.support_acts = Array.isArray(newGig.support_acts)
                    ? newGig.support_acts
                    : [];
                form.people_attending = Array.isArray(newGig.people_attending)
                    ? newGig.people_attending
                    : [];

                form.clearErrors();
            } else {
                form.reset({
                    artist_band_name: "",
                    venue: "",
                    gig_date_time: "",
                    support_acts: [],
                    people_attending: [],
                });
                form.clearErrors();
            }
        } else {
            form.reset({
                artist_band_name: "",
                venue: "",
                gig_date_time: "",
                support_acts: [],
                people_attending: [],
            });
            form.clearErrors();
        }
    },
    { immediate: true }
);

/**
 * Dynamically determines the modal title.
 */
const modalTitle = computed(() => (props.gig ? "Edit Gig" : "Add New Gig"));

/**
 * Dynamically determines the submit button text.
 */
const submitButtonText = computed(() =>
    props.gig ? "Save Changes" : "Add Gig"
);

/**
 * Handles form submission for both adding and editing gigs.
 */
const submit = () => {
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
                form.artist_band_name = "";
                form.venue = "";
                form.gig_date_time = "";
                form.support_acts = [];
                form.people_attending = [];
                form.clearErrors();
            },
            onError: () => {},
        });
    } else {
        form.post(route("gigs.store"), {
            onSuccess: () => {
                emit("close");
                form.artist_band_name = "";
                form.venue = "";
                form.gig_date_time = "";
                form.support_acts = [];
                form.people_attending = [];
                form.clearErrors();
            },
            onError: () => {},
        });
    }
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
            emit("close");
        },
        onError: () => {},
        onFinish: () => {
            confirmingGigDeletion.value = false;
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" :maxWidth="'2xl'">
        <div class="relative rounded-lg bg-neutral-900 p-6 text-white">
            <h2 class="mb-6 text-2xl font-semibold">{{ modalTitle }}</h2>

            <!-- Close button -->
            <button
                @click="emit('close')"
                class="absolute right-4 top-4 text-neutral-400 transition-colors hover:text-white">
                <XMarkIcon class="h-6 w-6" />
            </button>

            <form @submit.prevent="submit">
                <!-- Artist/Band Name -->
                <div class="mb-4">
                    <InputLabel
                        for="artist_band_name"
                        value="Artist/Band Name" />
                    <TextInput
                        id="artist_band_name"
                        type="text"
                        class="mt-1 block w-full border-neutral-700 bg-neutral-800 text-white focus:border-accent-500 focus:ring-accent-500"
                        v-model="form.artist_band_name"
                        required
                        autofocus />
                    <InputError
                        class="mt-2"
                        :message="form.errors.artist_band_name" />
                </div>

                <!-- Venue -->
                <div class="mb-4">
                    <InputLabel for="venue" value="Venue" />
                    <TextInput
                        id="venue"
                        type="text"
                        class="mt-1 block w-full border-neutral-700 bg-neutral-800 text-white focus:border-accent-500 focus:ring-accent-500"
                        v-model="form.venue"
                        required />
                    <InputError class="mt-2" :message="form.errors.venue" />
                </div>

                <!-- Date & Time -->
                <div class="mb-4">
                    <InputLabel for="gig_date_time" value="Date & Time" />
                    <TextInput
                        id="gig_date_time"
                        type="datetime-local"
                        class="mt-1 block w-full border-neutral-700 bg-neutral-800 text-white focus:border-accent-500 focus:ring-accent-500"
                        v-model="form.gig_date_time"
                        required />
                    <InputError
                        class="mt-2"
                        :message="form.errors.gig_date_time" />
                </div>

                <!-- Support Acts -->
                <div class="mb-4">
                    <TagInput
                        v-model="form.support_acts"
                        label="Support Acts"
                        placeholder="e.g., Artist A, Artist B"
                        delimiter=","
                        add-on-blur
                        add-on-tab />
                    <InputError
                        class="mt-2"
                        :message="form.errors.support_acts" />
                </div>

                <!-- People Attending -->
                <div class="mb-6">
                    <TagInput
                        v-model="form.people_attending"
                        label="People Attending"
                        placeholder="e.g., John Doe, Jane Smith"
                        delimiter=","
                        add-on-blur
                        add-on-tab />
                    <InputError
                        class="mt-2"
                        :message="form.errors.people_attending" />
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex items-center justify-between space-x-4">
                    <!-- Delete Button (shown only in Edit Mode) -->
                    <button
                        v-if="gig"
                        type="button"
                        @click="confirmGigDeletion"
                        class="rounded-lg bg-red-600 px-4 py-2 text-white transition-colors hover:bg-red-700">
                        Delete Gig
                    </button>
                    <!-- Spacer if not in edit mode -->
                    <div v-else></div>

                    <div class="flex space-x-4">
                        <!-- Cancel Button -->
                        <button
                            type="button"
                            @click="emit('close')"
                            class="rounded-lg bg-neutral-700 px-4 py-2 text-white transition-colors hover:bg-neutral-600">
                            Cancel
                        </button>
                        <!-- Submit Button -->
                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing">
                            {{ submitButtonText }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal
        :show="confirmingGigDeletion"
        @close="confirmingGigDeletion = false"
        :maxWidth="'sm'">
        <div class="p-6 bg-neutral-900 text-white rounded-lg">
            <h3 class="text-lg font-medium text-white mb-4">
                Are you sure you want to delete this gig?
            </h3>
            <p class="text-sm text-neutral-400 mb-6">
                This action cannot be undone. All associated data, including its
                setlist, will be permanently removed.
            </p>
            <div class="flex justify-end space-x-3">
                <button
                    type="button"
                    @click="confirmingGigDeletion = false"
                    class="rounded-lg bg-neutral-700 px-4 py-2 text-white transition-colors hover:bg-neutral-600">
                    Cancel
                </button>
                <PrimaryButton
                    @click="deleteGig"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700">
                    Delete
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
