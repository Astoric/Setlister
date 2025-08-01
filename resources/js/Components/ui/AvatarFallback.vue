<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
// Note: Radix Vue also has AvatarFallback.

const props = defineProps({
    className: { type: String, default: "" },
});

const classes = computed(() =>
    cn(
        "bg-muted flex size-full items-center justify-center rounded-full", // Base classes from avatar.tsx
        props.className
    )
);
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>
