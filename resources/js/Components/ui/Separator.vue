<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { Separator as SeparatorPrimitive } from "radix-vue"; // <--- IMPORTANT: Ensure radix-vue is installed

const props = defineProps({
    className: { type: String, default: "" },
    orientation: {
        type: String,
        default: "horizontal",
        validator: (value) => ["horizontal", "vertical"].includes(value),
    },
    decorative: { type: Boolean, default: true },
});

const classes = computed(() =>
    cn(
        "shrink-0 bg-border", // Base classes from separator.tsx
        props.orientation === "horizontal" ? "h-px w-full" : "h-full w-px", // Conditional height/width based on orientation
        props.className
    )
);
</script>

<template>
    <SeparatorPrimitive
        :orientation="orientation"
        :decorative="decorative"
        :class="classes">
        <slot />
    </SeparatorPrimitive>
</template>
