<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { DialogOverlay as DialogOverlayPrimitive } from "radix-vue";
</script>

<template>
    <DialogOverlayPrimitive
        :class="
            cn(
                'fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
                $attrs.class
            )
        "
        v-bind="$attrs">
        <slot />
    </DialogOverlayPrimitive>
</template>
