<template>
    <div class="bg-white shadow-md rounded-xl">
        <div class="px-2 py-2 sm:px-3">
            <slot name="header"></slot>
            <!-- <p class="mt-1 max-w-2xl text-sm text-gray-600">
                <slot name="sub-header"></slot>
            </p> -->
        </div>
        <slot name="content"></slot>
        <div class="grid grid-cols-3 bg-gray-50 rounded-lg">
            <div class="col-span-1 flex items-center justify-start px-4 py-2 text-right sm:px-6 border-t" v-if="hasActionsLeft">
                <slot name="actions-left"></slot>
            </div>
            <div class="col-span-2 flex items-center justify-end px-4 py-2 text-right sm:px-6 border-t" v-if="hasActions">
                <slot name="actions"></slot>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, useSlots }   from "vue";

const slots = useSlots();

defineProps({
    align: {
        type: String,
        default: 'right'
    }
});

const hasActions = computed(() => {
    return !! slots.actions;
});

const hasActionsLeft = computed(() => {
    return !! slots['actions-left'];
});
</script>
