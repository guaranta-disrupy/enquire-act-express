<template>
    <div
        v-if="isVisible"
        class="countdown-container breathing-animation"
        :style="trialTimerContainer"
    >
        <svg
            :viewBox="`0 0 ${VIEW_BOX_SIZE} ${VIEW_BOX_SIZE}`"
            class="progress-svg"
            :style="trialTimerSVGContainer"
        >
            <circle
                :r="RADIUS"
                :cx="CENTER"
                :cy="CENTER"
                fill="none"
                :style="trialTimerCircleTrack"
            />

            <circle
                :r="RADIUS"
                :cx="CENTER"
                :cy="CENTER"
                fill="none"
                :stroke-dasharray="CIRCUMFERENCE"
                :stroke-dashoffset="strokeOffset"
                class="progress-circle"
                :style="trialTimerCircle"
            />
        </svg>

        <div class="countdown-text" :style="trialTimerText">
            {{ remainingTime }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from "vue";
import {
    trialTimerContainer,
    trialTimerSVGContainer,
    trialTimerCircle,
    trialTimerCircleTrack,
    trialTimerText,
} from "../../utils/trialStyling";

// --- Constants for SVG Calculation (Same as before) ---
const VIEW_BOX_SIZE = 100;
const STROKE_OFFSET = 10;
const CENTER = VIEW_BOX_SIZE / 2;
const RADIUS = CENTER - STROKE_OFFSET;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

// --- Props Definition ---
const props = defineProps({
    duration: {
        type: Number,
        required: true,
    },
    show: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["timer-complete"]); // Emit an event for external control

// --- State and Reactive Variables ---
const totalTime = props.duration;
const startTime = ref(null);
const elapsedTime = ref(0);
const timerInterval = ref(null);
const isVisible = ref(props.show); // Component manages its own visibility
const lastTickTime = ref(null); // Track the last tick time for discrete animation

// --- Computed Properties (Same as before) ---
const remainingTime = computed(() => {
    const remaining = Math.max(0, totalTime - elapsedTime.value);
    return Math.ceil(remaining / 1000);
});

const progressPercentage = computed(() => {
    if (elapsedTime.value >= totalTime) {
        return 100;
    }
    return (elapsedTime.value / totalTime) * 100;
});

// Calculate seconds elapsed (for reference)
const secondsElapsed = computed(() => {
    const total = Math.floor(totalTime / 1000);
    const remaining = remainingTime.value;
    return total - remaining;
});

// Use smooth continuous progress for the stroke animation
const strokeOffset = computed(() => {
    // Use the continuous progress percentage
    const progress = progressPercentage.value;
    return CIRCUMFERENCE - (progress / 100) * CIRCUMFERENCE;
});

// --- Functions ---

const startTimer = () => {
    stopTimer();
    isVisible.value = true; // Ensure visibility when starting
    startTime.value = Date.now();
    elapsedTime.value = 0;

    // Use a slightly longer interval since we're tracking by seconds
    timerInterval.value = setInterval(() => {
        elapsedTime.value = Date.now() - startTime.value;

        // Get current second
        const currentSecond = Math.floor(elapsedTime.value / 1000);

        // Check if we've moved to a new second (for animation ticking)
        if (lastTickTime.value !== currentSecond) {
            lastTickTime.value = currentSecond;
        }

        if (elapsedTime.value >= totalTime) {
            stopTimer(true); // Pass true to indicate completion
        }
    }, 50); // Slightly less frequent updates
};

// Modified to handle completion logic
const stopTimer = (completed = false) => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
    }

    if (completed) {
        // 1. **Directly hide the timer**
        isVisible.value = false;

        // 2. Emit an event if the parent component needs to know
        emit("timer-complete");
    }
};

// --- Lifecycle & Watchers ---

// Watch the initialShow prop to allow external control (e.g., reset/start)
watch(
    () => props.show,
    (newVal) => {
        if (newVal && !timerInterval.value) {
            startTimer();
        } else if (!newVal) {
            stopTimer();
            isVisible.value = false;
        }
    },
    { immediate: true }
);

onUnmounted(() => {
    stopTimer();
});
</script>

<style scoped>
/* trialTimerContainer */
.countdown-container {
    /* width: 8vmin;
    height: 8vmin; */
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    margin: 10px;
    /* background-color: unset; */
    position: fixed;
    z-index: 1000;
    transform-origin: center;
    transition: transform 0.1s ease-in-out;
}

/* Continuous breathing animation */
.breathing-animation {
    animation: breathe 3s ease-in-out infinite;
}

@keyframes breathe {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

/* trialTimerSVGContainer */
.progress-svg {
    position: absolute;
    transform: rotate(-90deg);
    overflow: visible;
    /* width: 8vmin;
    height: 8vmin; */
}

/* trialTimerCircle */
.progress-circle {
    /* Use a smooth continuous transition */
    transition: stroke-dashoffset 0.05s linear;
    will-change: stroke-dashoffset;
}

/* trialTimerCircleTrack */
/* .progress-circle-track {
    stroke-width: 6px;
    stroke: #25755615;
} */

/* trialTimerText */
.countdown-text {
    position: relative;
    font-weight: bold;
    z-index: 2;
    white-space: nowrap;
    /* color: rgb(82, 81, 81);
    font-size: 1.15rem; */
    transition: transform 0.15s ease;
}
</style>
