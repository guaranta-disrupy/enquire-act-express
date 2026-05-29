<template>
    <Transition
        enter-active-class="transition duration-500 ease-out"
        enter-from-class="opacity-0 translate-y-6"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-300 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-6"
    >
        <div
            v-if="isVisible"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4"
        >
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4"
                    @click.stop
                >
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-red-600">
                            {{ headerText }}
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
                            v-for="button in buttons"
                            :key="button.text"
                            @click="handleButtonClick(button)"
                            class="px-4 py-2 text-blue-700 font-medium text-lg hover:text-blue-400 transition-colors duration-200"
                        >
                            {{ button.text }}
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";

const emit = defineEmits(["close", "show", "hide"]);

const isVisible = ref(false);
const headerText = ref("Error");
const message = ref(
    "Something went wrong while we were saving your progress. We'll try again! If this keeps on happening please refresh the page."
);
const buttons = ref([
    {
        text: "RETRY",
        onClick: () => {},
    },
]);

// Show the modal
const showModal = () => {
    isVisible.value = true;
    document.body.style.overflow = "hidden"; // Prevent background scrolling
    emit("show"); // Emit show event
};

// Hide the modal
const hideModal = () => {
    isVisible.value = false;
    document.body.style.overflow = ""; // Restore scrolling
    emit("close");
    emit("hide"); // Emit hide event
};

// Handle button clicks
const handleButtonClick = (button) => {
    if (button.onClick) {
        button.onClick();
    }
    hideModal();
};

// Handle escape key
const handleEscapeKey = (event) => {
    if (event.key === "Escape") {
        hideModal();
    }
};

// Listen for custom event to show modal
const handleShowErrorModal = (event) => {
    const {
        headerText: newHeaderText,
        message: newMessage,
        buttons: newButtons,
    } = event.detail;
    headerText.value = newHeaderText;
    message.value = newMessage;
    buttons.value = newButtons;
    showModal();
};

// Lifecycle
onMounted(() => {
    window.addEventListener("keydown", handleEscapeKey);
    window.addEventListener("survey.error", handleShowErrorModal);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleEscapeKey);
    window.removeEventListener("survey.error", handleShowErrorModal);
    document.body.style.overflow = "";
});

// Expose methods for external use
defineExpose({
    show: showModal,
    hide: hideModal,
});
</script>
