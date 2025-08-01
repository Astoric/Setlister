<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
import {
    DialogContent as DialogContentPrimitive,
    DialogPortal,
} from "radix-vue";
import { XMarkIcon } from "@heroicons/vue/24/outline"; // Using Heroicons for the close icon
import DialogOverlay from "@/Components/ui/DialogOverlay.vue"; // Correct import for DialogOverlay
import DialogClose from "@/Components/ui/DialogClose.vue"; // Correct import for DialogClose

const props = defineProps({
    className: { type: String, default: "" },
});

const classes = computed(() =>
    cn(
        "fixed left-[50%] top-[50%] z-50 grid w-full max-w-[calc(100%-2rem)] translate-x-[-50%] translate-y-[-50%] gap-4 rounded-lg border bg-background p-6 shadow-lg duration-200 sm:max-w-lg",
        "data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95",
        "data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2",
        props.className
    )
);
</script>

<template>
    <DialogPortal>
        <DialogOverlay />
        <DialogContentPrimitive :class="classes" v-bind="$attrs">
            <slot />
            <DialogClose
                class="absolute right-4 top-4 rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-background disabled:pointer-events-none">
                <XMarkIcon class="h-4 w-4" />
                <span class="sr-only">Close</span>
            </DialogClose>
        </DialogContentPrimitive>
    </DialogPortal>
</template>
