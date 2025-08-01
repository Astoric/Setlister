<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { DropdownMenuLabel } from "radix-vue";

const props = defineProps({
    className: { type: String, default: "" },
    inset: { type: Boolean, default: false },
});

const classes = computed(() =>
    cn(
        "px-2 py-1.5 text-sm font-medium",
        props.inset && "pl-8",
        props.className
    )
);
</script>

<template>
    <DropdownMenuLabel :class="classes" :data-inset="inset" v-bind="$attrs">
        <slot />
    </DropdownMenuLabel>
</template>
