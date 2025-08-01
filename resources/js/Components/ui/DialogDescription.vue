<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { DialogDescription as DialogDescriptionPrimitive } from "radix-vue";
</script>

<template>
    <DialogDescriptionPrimitive
        :class="cn('text-sm text-muted-foreground', $attrs.class)"
        v-bind="$attrs">
        <slot />
    </DialogDescriptionPrimitive>
</template>
