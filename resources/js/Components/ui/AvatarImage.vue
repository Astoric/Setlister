<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
// Note: Radix Vue also has AvatarImage, but for direct <img> tag, a wrapper is simple.

const props = defineProps({
    className: { type: String, default: "" },
    src: { type: String, default: "" },
    alt: { type: String, default: "" },
});

const classes = computed(() =>
    cn(
        "aspect-square size-full", // Base classes from avatar.tsx
        props.className
    )
);
</script>

<template>
    <img :src="props.src" :alt="props.alt" :class="classes" />
</template>
