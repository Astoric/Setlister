<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
</script>

<template>
    <div
        :class="
            cn('flex items-center px-6 pb-6 [.border-t]:pt-6', $attrs.class)
        ">
        <slot />
    </div>
</template>
