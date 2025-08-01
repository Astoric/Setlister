<script setup>
import { computed, h } from "vue";
import { cn } from "@/lib/utils";
import { Label as LabelPrimitive } from "radix-vue"; // <--- IMPORTANT: Ensure radix-vue is installed

const props = defineProps({
    className: { type: String, default: "" },
    htmlFor: { type: String, default: "" }, // Replaces 'for' in HTML, but 'for' prop is aliased to 'htmlFor' in Vue
});

const classes = computed(() =>
    cn(
        "flex items-center gap-2 text-sm leading-none font-medium select-none group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50", // Base classes from label.tsx
        props.className
    )
);
</script>

<template>
    <LabelPrimitive :for="htmlFor" :class="classes">
        <slot />
    </LabelPrimitive>
</template>
