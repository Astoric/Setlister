<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
</script>

<template>
    <div :class="cn('px-6 [&:last-child]:pb-6', $attrs.class)">
        <slot />
    </div>
</template>
