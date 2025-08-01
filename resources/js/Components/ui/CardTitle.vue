<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
</script>

<template>
    <h4 :class="cn('leading-none', $attrs.class)">
        <slot />
    </h4>
</template>
