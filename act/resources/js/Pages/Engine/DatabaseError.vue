<template>
    <div
        class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-red-600">
                    {{ error }}
                </h3>
            </div>

            <!-- Message Body -->
            <div class="px-6 py-4">
                <p class="text-gray-700 leading-relaxed">
                    {{ message }}
                </p>
            </div>

            <!-- Action Buttons -->
            <div
                class="py-4 border-t border-gray-200 flex justify-center px-20"
            >
                <button
                    @click="retryConnection"
                    :disabled="isRetrying"
                    class="px-4 py-2 text-blue-700 font-medium hover:text-blue-400 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="isRetrying" class="flex items-center text-lg">
                        <svg
                            class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-700"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        Retrying...
                    </span>
                    <span v-else class="text-lg">RETRY</span>
                </button>

                <!-- <button
                    @click="goBack"
                    class="px-4 py-2 text-blue-700 font-medium hover:text-blue-400 transition-colors duration-200"
                >
                    RELOAD
                </button> -->
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, defineEmits } from "vue";
import { useLogger } from "./utils/logger.js";
const { _log } = useLogger();

const props = defineProps({
    error: {
        type: String,
        default: "Database connection failed",
    },
    message: {
        type: String,
        default:
            "The survey system is temporarily unavailable. Please try again later.",
    },
    retryUrl: {
        type: String,
        default: window.location.href,
    },
    isOffline: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["retry"]);

const isRetrying = ref(false);

const retryConnection = async () => {
    isRetrying.value = true;

    if (props.isOffline) {
        // For offline errors, emit an event to retry without reloading.
        _log("Emitting retry event for offline submission.");
        emit("retry");
        // Give user feedback that a retry is happening.
        await new Promise((resolve) => setTimeout(resolve, 1000));
    } else {
        // For other errors (like database), reload the page.
        _log("Reloading page to retry server connection.");
        window.location.reload();
    }

    isRetrying.value = false;
};

const goBack = () => {
    window.location.reload();
};
</script>
