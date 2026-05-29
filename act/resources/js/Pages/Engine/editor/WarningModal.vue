<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="isOpen"
                class="fixed inset-0 bg-gray-900 bg-opacity-20 z-50 flex items-center justify-center"
            >
                <div
                    class="bg-white rounded-lg shadow-xl p-6 m-4 max-w-md w-full"
                >
                    <div class="flex items-start">
                        <div
                            class="flex-shrink-0 bg-yellow-100 rounded-full p-2"
                        >
                            <svg
                                class="w-6 h-6 text-yellow-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                ></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">
                                Unsaved Changes
                            </h3>
                            <p class="mt-2 text-sm text-gray-600">
                                You have unsaved changes. Do you want to save
                                them before navigating away?
                            </p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="handleCancel"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="handleDiscard"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
                        >
                            Discard Changes
                        </button>
                        <button
                            @click="handleSave"
                            class="px-4 py-2 text-sm font-medium text-white bg-[#15919A] rounded-md hover:bg-[#0C7C84]"
                        >
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(["save", "discard", "cancel"]);

const handleSave = () => emit("save");
const handleDiscard = () => emit("discard");
const handleCancel = () => emit("cancel");
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>
