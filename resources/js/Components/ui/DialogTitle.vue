<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { DialogTitle as DialogTitlePrimitive } from "radix-vue";
</script>

<template>
    <DialogTitlePrimitive
        :class="cn('text-lg font-semibold leading-none', $attrs.class)"
        v-bind="$attrs">
        <slot />
    </DialogTitlePrimitive>
</template>
