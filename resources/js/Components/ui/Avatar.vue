<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
// Note: Radix Vue also has AvatarRoot, but for simpler components like this
// just applying classes to a div is often sufficient unless you need its primitives' features.
// If problems arise with context later, we can switch to Radix Vue's AvatarRoot.

const props = defineProps({
    className: { type: String, default: "" },
});

const classes = computed(() =>
    cn(
        "relative flex size-10 shrink-0 overflow-hidden rounded-full", // Base classes from avatar.tsx
        props.className
    )
);
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>
