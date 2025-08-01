<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { DropdownMenuItem } from "radix-vue";

const props = defineProps({
    className: { type: String, default: "" },
    inset: { type: Boolean, default: false },
    variant: {
        type: String,
        default: "default",
        validator: (value) => ["default", "destructive"].includes(value),
    },
});

const classes = computed(() =>
    cn(
        "relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground",
        "data-[disabled]:pointer-events-none data-[disabled]:opacity-50",
        props.inset && "pl-8",
        props.variant === "destructive" &&
            "text-destructive focus:bg-destructive/10 dark:focus:bg-destructive/20 focus:text-destructive *:text-destructive",
        props.className
    )
);
</script>

<template>
    <DropdownMenuItem
        :class="classes"
        :data-inset="props.inset"
        :data-variant="props.variant"
        v-bind="$attrs">
        <slot />
    </DropdownMenuItem>
</template>
