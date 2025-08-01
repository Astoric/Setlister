<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
</script>

<template>
    <div
        :class="
            cn(
                'col-start-2 row-span-2 row-start-1 self-start justify-self-end',
                $attrs.class
            )
        ">
        <slot />
    </div>
</template>
