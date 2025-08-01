<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col gap-6 rounded-xl border bg-card text-card-foreground',
                $attrs.class
            )
        ">
        <slot />
    </div>
</template>
