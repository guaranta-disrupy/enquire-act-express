<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 transform translate-y-4"
        enter-to-class="opacity-100 transform translate-y-0"
    >
        <div
            class="w-full text-center fixed bottom-0 left-1/2 transform -translate-x-1/2 z-50 text-white"
            v-if="isVisible"
            :style="[customStyles, warningStyle(deviceType)]"
        >
            <div
                class="text-white landscape:text-[1.9vmin] text-center bg-red-500 inline rounded-md landscape:rounded-lg landscape:py-2 landscape:px-4 p-2 px-3 portrait:text-[2.8vmin]"
            >
                {{ message }}
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from "vue";
import { warningStyle } from "../../utils/trialStyling";

const props = defineProps({
    message: {
        type: String,
        default: "Notification message",
    },
    cssStyle: {
        type: Object,
        default: () => ({}),
    },
    show: {
        type: Boolean,
        default: false,
    },
    delayDuration: {
        type: Number,
        default: 3000,
    },
    isEditorMode: {
        type: Boolean,
        default: false,
    },
    deviceType: {
        type: String,
        default: "desktop",
    },
});

const emit = defineEmits(["close"]);

const isVisible = ref(false);
let autoCloseTimer = null;

// Computed custom styles (only for props.cssStyle)
const customStyles = computed(() => {
    return props.cssStyle;
});

// Watch for show prop changes
watch(
    () => props.show,
    (newShow) => {
        if (newShow) {
            showNotification();
        } else {
            hideNotification();
        }
    }
);

// Show notification
const showNotification = () => {
    isVisible.value = true;

    // Auto-close after delay duration
    if (props.delayDuration > 0) {
        autoCloseTimer = setTimeout(() => {
            closeNotification();
        }, props.delayDuration);
    }
};

// Hide notification
const hideNotification = () => {
    isVisible.value = false;
    clearAutoCloseTimer();
};

// Close notification manually
const closeNotification = () => {
    hideNotification();
    emit("close");
};

// Clear auto-close timer
const clearAutoCloseTimer = () => {
    if (autoCloseTimer) {
        clearTimeout(autoCloseTimer);
        autoCloseTimer = null;
    }
};

// Cleanup on unmount
onUnmounted(() => {
    clearAutoCloseTimer();
});
</script>
