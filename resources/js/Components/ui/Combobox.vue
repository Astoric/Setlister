<script setup>
import {
    ComboboxContent,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxLabel,
    ComboboxRoot,
    ComboboxTrigger,
    ComboboxViewport,
} from "reka-ui";
import { computed, ref } from "vue";
import { Check, ChevronsUpDown } from "lucide-vue-next";
import { cn } from "@/lib/utils";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: "Select option...",
    },
    emptyMessage: {
        type: String,
        default: "No options found.",
    },
});

const emit = defineEmits(["update:modelValue"]);

const open = ref(false);
const searchTerm = ref("");

// Filter options based on search term
const filteredOptions = computed(() => {
    if (!searchTerm.value) return props.options;
    return props.options.filter((option) =>
        option.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

const handleUpdate = (val) => {
    emit("update:modelValue", val);
    searchTerm.value = val;
};

const handleInput = (e) => {
    emit("update:modelValue", e.target.value);
    searchTerm.value = e.target.value;
};
</script>

<template>
    <ComboboxRoot
        :model-value="modelValue"
        @update:model-value="handleUpdate"
        class="relative">
        <div class="relative w-full">
            <ComboboxInput
                v-bind="$attrs"
                :display-value="(v) => v"
                class="flex h-9 w-full rounded-md border border-gray-600 bg-[#191919] px-3 py-1 text-sm text-white placeholder:text-gray-500 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200 outline-none"
                :placeholder="placeholder"
                @input="handleInput" />
            <ComboboxTrigger class="absolute inset-y-0 right-0 flex items-center pr-2">
                <ChevronsUpDown class="h-4 w-4 text-gray-400" />
            </ComboboxTrigger>
        </div>

        <ComboboxContent
            class="absolute z-50 mt-1 max-h-60 w-full overflow-hidden rounded-md border border-gray-700 bg-[#191919] text-white shadow-md animate-in fade-in-0 zoom-in-95">
            <ComboboxViewport class="p-1">
                <ComboboxEmpty
                    class="py-2 text-center text-sm text-gray-500">
                    {{ emptyMessage }}
                </ComboboxEmpty>

                <ComboboxGroup>
                    <ComboboxItem
                        v-for="option in filteredOptions"
                        :key="option"
                        :value="option"
                        class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none data-[highlighted]:bg-emerald-500/20 data-[highlighted]:text-emerald-400 data-[disabled]:pointer-events-none data-[disabled]:opacity-50">
                        <ComboboxItemIndicator
                            class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                            <Check class="h-4 w-4 text-emerald-500" />
                        </ComboboxItemIndicator>
                        <span>{{ option }}</span>
                    </ComboboxItem>
                </ComboboxGroup>
            </ComboboxViewport>
        </ComboboxContent>
    </ComboboxRoot>
</template>
