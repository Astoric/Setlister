<script setup>
import {
    TagsInputInput,
    TagsInputItem,
    TagsInputItemDelete,
    TagsInputItemText,
    TagsInputRoot,
} from "reka-ui";
import { computed, ref, watch } from "vue";
import { XMarkIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    label: { type: String, default: "" },
    placeholder: { type: String, default: "Add items" },
    addOnBlur: { type: Boolean, default: true },
    addOnPaste: { type: Boolean, default: true },
    addOnTab: { type: Boolean, default: true },
    duplicate: { type: Boolean, default: false },
    max: { type: Number, default: 0 },
    delimiter: { type: [String, RegExp], default: "," },
});

const emit = defineEmits([
    "update:modelValue",
    "addTag",
    "removeTag",
    "invalid",
]);

const internalTags = ref([]);

const inputText = ref("");

/**
 * Watches props.modelValue to update internalTags and inputText for initial population.
 */
watch(
    () => props.modelValue,
    (newVal) => {
        const normalizedNewVal = Array.isArray(newVal) ? newVal : [];

        if (
            JSON.stringify(normalizedNewVal) !==
            JSON.stringify(internalTags.value)
        ) {
            internalTags.value = normalizedNewVal;
        }

        if (normalizedNewVal.length > 0 && inputText.value === "") {
            inputText.value = normalizedNewVal.join(", ");
        }
    },
    { immediate: true, deep: true }
);

/**
 * Watches internalTags to emit changes back to parent.
 */
watch(
    internalTags,
    (newVal) => {
        emit("update:modelValue", newVal);
    },
    { deep: true }
);

/**
 * Handles changes from Reka-UI's primary v-model.
 */
const handleTagsInputChange = (newTags) => {
    internalTags.value = Array.isArray(newTags) ? newTags : [];
};

/**
 * Handles keydown events on the input field to process tag addition or removal.
 */
const handleInputKeyDown = (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        if (inputText.value.trim() !== "") {
            const newValues = inputText.value
                .split(props.delimiter)
                .map((t) => t.trim())
                .filter((t) => t.length > 0);
            const combined = [
                ...new Set([...internalTags.value, ...newValues]),
            ];
            internalTags.value = combined;
            inputText.value = "";
        }
    } else if (
        event.key === "Backspace" &&
        inputText.value === "" &&
        internalTags.value.length > 0
    ) {
        event.preventDefault();
        const updatedTags = [...internalTags.value];
        updatedTags.pop();
        internalTags.value = updatedTags;
    }
};

/**
 * Handles blur event on the input field to commit any remaining text.
 */
const handleInputBlur = () => {
    if (props.addOnBlur && inputText.value.trim() !== "") {
        const newValues = inputText.value
            .split(props.delimiter)
            .map((t) => t.trim())
            .filter((t) => t.length > 0);
        const combined = [...new Set([...internalTags.value, ...newValues])];
        internalTags.value = combined;
        inputText.value = "";
    }
};

/**
 * Handles the Reka-UI 'add-tag' event.
 */
const handleRekaAddTag = (payload) => {
    emit("addTag", payload);
};

/**
 * Handles the Reka-UI 'remove-tag' event.
 */
const handleRekaRemoveTag = (value) => {
    internalTags.value = internalTags.value.filter((tag) => tag !== value);
    emit("removeTag", value);
};

/**
 * Handles the Reka-UI 'invalid' event.
 */
const handleRekaInvalid = (value) => {
    emit("invalid", value);
};
</script>

<template>
    <div>
        <!-- Label -->
        <label
            v-if="label"
            :for="label.replace(/\s+/g, '-').toLowerCase()"
            class="mb-1 block text-sm font-medium text-neutral-400">
            {{ label }}
        </label>

        <!-- Tags Input Root -->
        <TagsInputRoot
            v-model="internalTags"
            :add-on-blur="addOnBlur"
            :add-on-paste="addOnPaste"
            :add-on-tab="addOnTab"
            :duplicate="duplicate"
            :max="max"
            :delimiter="delimiter"
            @update:model-value="handleTagsInputChange"
            @add-tag="handleRekaAddTag"
            @remove-tag="handleRekaRemoveTag"
            @invalid="handleRekaInvalid"
            class="flex w-full flex-wrap items-center gap-2 rounded-md border border-neutral-700 bg-neutral-800 p-2 shadow-sm transition-colors duration-200 focus-within:border-accent-500 focus-within:ring-2 focus-within:ring-accent-500">
            <!-- Individual Tags -->
            <TagsInputItem
                v-for="item in internalTags"
                :key="item"
                :value="item"
                class="flex items-center justify-center gap-1 rounded-full bg-accent-500 px-2 py-0.5 text-xs font-medium text-neutral-900 select-none">
                <TagsInputItemText class="flex-grow" />
                <!-- Tag Delete Button -->
                <TagsInputItemDelete
                    class="rounded-full p-0.5 transition-colors duration-200 hover:bg-neutral-900/20"
                    aria-label="remove tag">
                    <XMarkIcon class="h-3 w-3 text-neutral-900" />
                </TagsInputItemDelete>
            </TagsInputItem>

            <!-- Tags Input Field -->
            <TagsInputInput
                :id="label.replace(/\s+/g, '-').toLowerCase()"
                :placeholder="placeholder"
                class="flex-1 rounded-md bg-transparent px-1 py-0.5 text-sm text-white placeholder:text-neutral-500 focus:outline-none min-w-[80px]"
                v-model="inputText"
                @keydown="handleInputKeyDown"
                @blur="handleInputBlur" />
        </TagsInputRoot>
    </div>
</template>
