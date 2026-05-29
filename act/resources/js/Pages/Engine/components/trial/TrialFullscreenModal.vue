<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-black bg-opacity-80 flex flex-col items-center justify-center z-[9999]"
    >
        <div class="text-white text-center p-4">
            <p class="text-2xl font-semibold mb-4">
                {{ message }}
            </p>
            <button
                @click="emit('enterFullscreen')"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg text-lg transition-colors"
            >
                {{ buttonText }}
            </button>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    message: {
        type: String,
        default: "For the best experience, please enter fullscreen.",
    },
    buttonText: {
        type: String,
        default: "Enter Fullscreen",
    },
});

const emit = defineEmits(["close", "enterFullscreen"]);
</script>
