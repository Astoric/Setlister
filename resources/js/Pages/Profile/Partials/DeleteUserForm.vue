<script setup>
import { useForm } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";
// New UI Components
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";
import Dialog from "@/Components/ui/Dialog.vue";
import DialogContent from "@/Components/ui/DialogContent.vue";
import DialogHeader from "@/Components/ui/DialogHeader.vue";
import DialogTitle from "@/Components/ui/DialogTitle.vue";
import DialogDescription from "@/Components/ui/DialogDescription.vue";

// Lucide Icons
import { Trash2, AlertTriangle } from "lucide-vue-next";

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: "",
});

/**
 * Initiates user deletion confirmation.
 */
const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

/**
 * Handles user deletion.
 */
const deleteUser = () => {
    form.delete(route("profile.destroy"), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

/**
 * Closes the deletion confirmation modal.
 */
const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h3 class="text-xl font-semibold text-white mb-2">
                Delete Account
            </h3>
            <p class="text-gray-400 text-sm">
                Once your account is deleted, all of its resources and data will
                be permanently deleted.
            </p>
        </header>

        <Button
            @click="confirmUserDeletion"
            variant="destructive"
            class="bg-red-600 hover:bg-red-700 text-white transition-all duration-200">
            <Trash2 class="mr-2 h-4 w-4" />
            Delete Account
        </Button>

        <Dialog
            :open="confirmingUserDeletion"
            @update:open="confirmingUserDeletion = $event">
            <DialogContent
                class="bg-[#191919] border-gray-700 text-white max-w-sm">
                <DialogHeader>
                    <div class="mb-4 flex justify-center text-red-400">
                        <AlertTriangle class="h-12 w-12" />
                    </div>
                    <DialogTitle
                        class="text-2xl font-semibold text-white text-center">
                        Are you sure?
                    </DialogTitle>
                    <DialogDescription class="text-gray-400 text-center">
                        This action is permanent and cannot be undone. All your
                        data will be permanently deleted.
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-6 space-y-4">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full bg-[#212121] border-gray-600 text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200"
                        placeholder="Password"
                        @keydown.enter="deleteUser" />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <Button
                        variant="ghost"
                        @click="closeModal"
                        class="text-gray-400 hover:bg-gray-700 hover:text-white transition-all duration-200">
                        Cancel
                    </Button>
                    <Button
                        @click="deleteUser"
                        :class="{
                            'opacity-75 cursor-not-allowed': form.processing,
                        }"
                        :disabled="form.processing"
                        variant="destructive"
                        class="bg-red-600 hover:bg-red-700 text-white transition-all duration-200">
                        Delete Account
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </section>
</template>
