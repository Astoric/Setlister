<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
</script>

<template>
    <p :class="cn('text-muted-foreground', $attrs.class)">
        <slot />
    </p>
</template>
