<template>
    <Transition name="modal" appear>
        <div class="modal-backdrop" ref="modalBackdrop">
            <div
                class="modal-container py-5"
                :class="{ 'is-closing': isClosing }"
                :style="[trialUnselectModalContainerStyle]"
            >
                <div class="modal-content">
                    <p class="text-gray-700 text-xl" id="unselect-message">
                        {{ unselectModalData.unselect_message }}
                    </p>
                </div>
                <div
                    class="flex gap-5 justify-center p-6"
                    id="unselect-actions"
                >
                    <JetButton
                        class="rounded-md py-2 px-4 text-lg min-w-32 max-w-full uppercase flex justify-center items-center"
                        @click.prevent="onCancel()"
                        variant="default"
                        size="md"
                    >
                        <span id="unselect-cancel">{{
                            unselectModalData.unselect_cancel
                        }}</span>
                    </JetButton>
                    <JetButton
                        class="submit-unselect rounded-md py-2 px-4 text-lg min-w-32 max-w-full uppercase flex justify-center items-center"
                        @click.prevent="onSubmit()"
                        variant=""
                        size="md"
                    >
                        <span id="unselect-submit">{{
                            unselectModalData.unselect_submit
                        }}</span>
                    </JetButton>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import JetButton from "@/Componentsv2/Button.vue";
import { ref, onMounted, onUpdated } from "vue";
import { trialUnselectModalContainerStyle } from "../../utils/trialStyling";

const emit = defineEmits(["close", "submit"]);
const modalBackdrop = ref(null);
const isClosing = ref(false);

const props = defineProps({
    isEditorMode: {
        type: Boolean,
        default: false,
    },
    unselectModalData: {
        type: Object,
        default: () => ({}),
    },
});

const onCancel = () => {
    if (isClosing.value) return;
    isClosing.value = true;
    setTimeout(() => {
        emit("close");
    }, 200); // Wait for container animation to finish
};

const onSubmit = () => {
    if (props.isEditorMode) return;
    if (isClosing.value) return;
    isClosing.value = true;
    setTimeout(() => {
        emit("submit");
    }, 200); // Wait for container animation to finish
};

function getContrastColor(colorStr) {
    if (!colorStr) {
        return "black";
    }

    let r, g, b;

    const color = colorStr.trim();

    // Check for hex color
    if (color.startsWith("#")) {
        const hex = color.slice(1);
        if (hex.length === 3) {
            r = parseInt(hex[0] + hex[0], 16);
            g = parseInt(hex[1] + hex[1], 16);
            b = parseInt(hex[2] + hex[2], 16);
        } else if (hex.length === 6) {
            r = parseInt(hex.substring(0, 2), 16);
            g = parseInt(hex.substring(2, 4), 16);
            b = parseInt(hex.substring(4, 6), 16);
        } else {
            return "black"; // Invalid hex
        }
    }
    // Check for rgb color
    else if (color.startsWith("rgb")) {
        const parts = color.match(/(\d+)/g);
        if (parts && parts.length >= 3) {
            [r, g, b] = parts.map(Number);
        } else {
            return "black"; // Invalid rgb
        }
    } else {
        return "black"; // Unsupported format
    }

    if (r === undefined) {
        return "black";
    }

    // YIQ formula: (299*R + 587*G + 114*B) / 1000. Threshold is often 128.
    const yiq = (r * 299 + g * 587 + b * 114) / 1000;

    // Returns 'dark' for light backgrounds, 'light' for dark backgrounds
    return yiq >= 128 ? "black" : "white";
}

const updateContrastColor = () => {
    if (modalBackdrop.value) {
        const style = getComputedStyle(modalBackdrop.value);
        const targetColor = style.getPropertyValue(
            "--target-selected-border-color"
        );

        if (targetColor) {
            const contrastColor = getContrastColor(targetColor);
            modalBackdrop.value.style.setProperty(
                "--text-contrast-color",
                contrastColor
            );
        }
    }
};

onMounted(updateContrastColor);
onUpdated(updateContrastColor);
</script>

<style scoped>
#unselect-message {
    font-size: var(--unselect-modal-message-font-size);
    text-align: var(--unselect-modal-message-text-align);
}

#unselect-actions {
    flex-direction: var(
        --unselect-modal-actions-flex-direction
    ); /* row | row-reverse */
    justify-content: var(
        --unselect-modal-actions-justify-content
    ); /* flex-start | flex-end | center */
}

#unselect-cancel {
    font-size: var(--unselect-modal-cancel-font-size);
}

#unselect-submit {
    font-size: var(--unselect-modal-submit-font-size);
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1050;
}

.modal-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 500px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: opacity 0.2s ease-out, transform 0.2s ease-out;
}

.modal-container.is-closing {
    opacity: 0;
    transform: translateY(20px);
}

.modal-content {
    padding: 24px;
    text-align: center;
    font-size: 1rem;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    padding: 16px;
    background-color: #f7fafc;
    border-top: 1px solid #e2e8f0;
}

.button {
    padding: 8px 16px;
    border-radius: 6px;
    /* font-size: 0.9rem; */
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease-in-out;
    border: 1px solid transparent;
}

.cancel-button {
    background-color: #e2e8f0;
    color: #2d3748;
    margin-right: 8px;
}

.cancel-button:hover {
    background-color: #cbd5e0;
}

.submit-button {
    background-color: #4299e1;
    color: white;
}

.submit-button:hover {
    background-color: #3182ce;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .modal-container {
        width: 95%;
    }

    .modal-actions {
        flex-direction: column-reverse;
        padding: 12px;
    }

    .button {
        width: 100%;
    }

    .cancel-button {
        margin-right: 0;
        margin-top: 8px;
    }
}

.submit-unselect {
    background-color: var(--target-selected-border-color);
    color: var(--text-contrast-color);
}

/* Modal Transition */
.modal-enter-active {
    transition: background-color 0.1s ease;
}
.modal-leave-active {
    transition: background-color 0.1s ease; /* Delay is handled by JS */
}

.modal-enter-from,
.modal-leave-to {
    background-color: rgba(0, 0, 0, 0);
}

.modal-enter-active .modal-container {
    transition: all 0.2s ease-out;
}

.modal-enter-from .modal-container {
    opacity: 0;
    transform: translateY(20px);
}
</style>
