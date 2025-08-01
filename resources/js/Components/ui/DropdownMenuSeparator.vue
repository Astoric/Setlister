<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { DropdownMenuSeparator } from "radix-vue";

const props = defineProps({
    className: { type: String, default: "" },
});

const classes = computed(() =>
    cn("-mx-1 my-1 h-px bg-border", props.className)
);
</script>

<template>
    <DropdownMenuSeparator :class="classes" v-bind="$attrs">
        <slot />
    </DropdownMenuSeparator>
</template>
