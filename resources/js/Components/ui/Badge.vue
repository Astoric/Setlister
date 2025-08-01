<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils"; // Import the cn utility

const props = defineProps({
    variant: {
        type: String,
        default: "default",
        validator: (value) =>
            ["default", "secondary", "destructive", "outline"].includes(value),
    },
    className: {
        // For custom classes passed from parent
        type: String,
        default: "",
    },
});

const badgeVariants = computed(() => {
    const baseClasses =
        "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2";

    const variants = {
        default:
            "border-transparent bg-primary text-primary-foreground hover:bg-primary/80",
        secondary:
            "border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80",
        destructive:
            "border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80",
        outline: "text-foreground",
    };

    return cn(baseClasses, variants[props.variant], props.className);
});
</script>

<template>
    <div :class="badgeVariants">
        <slot />
    </div>
</template>
