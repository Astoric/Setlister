<script setup>
import { computed, ref } from "vue"; // Import ref for expose
import { cn } from "@/lib/utils"; // Import the cn utility

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: "",
    },
    type: {
        type: String,
        default: "text",
    },
    className: {
        // For custom classes passed from parent
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["update:modelValue"]);

const input = ref(null); // Ref to the native input element

// Expose focus and select methods for parent components
defineExpose({
    focus: () => input.value?.focus(),
    select: () => input.value?.select(),
});
</script>

<template>
    <input
        :type="type"
        :value="modelValue"
        @input="emit('update:modelValue', $event.target.value)"
        :placeholder="placeholder"
        :class="
            cn(
                'flex h-9 w-full min-w-0 rounded-md border border-input bg-input px-3 py-1 text-base transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                'aria-invalid:ring-destructive/20 aria-invalid:border-destructive',
                props.className
            )
        "
        ref="input" />
</template>
