<template>
    <div v-if="isVisible" class="external-redirect-progress">
        <div class="progress-container">
            <div class="progress-bar">
                <div
                    class="progress-fill"
                    :style="{ width: progress + '%' }"
                ></div>
            </div>
            <div class="progress-text" v-if="text">{{ text }}</div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, ref, onMounted, onUnmounted, watch } from "vue";

const props = defineProps({
    isVisible: {
        type: Boolean,
        default: false,
    },
    text: {
        type: String,
        default: "Redirecting...",
    },
    showSpinner: {
        type: Boolean,
        default: true,
    },
    targetUrl: {
        type: String,
        required: true,
    },
    useDynamicLoading: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["redirect"]);

const progress = ref(0);
const isVisible = ref(props.isVisible);
const isLoading = ref(true);

let progressInterval = null;
let loadingCheckInterval = null;
let iframe = null;

const startDynamicProgress = () => {
    if (!isVisible.value) return;

    // Start with a smooth progress animation from 0 to 90%
    let currentProgress = 0;
    const maxProgress = 90; // Don't go to 100% until we detect loading
    const startTime = Date.now();
    const duration = 0; // 2 seconds to reach 90%

    progressInterval = setInterval(() => {
        const elapsed = Date.now() - startTime;
        const progressPercent = Math.min(
            (elapsed / duration) * maxProgress,
            maxProgress
        );

        progress.value = progressPercent;

        // Stop the interval when we reach maxProgress
        if (progressPercent >= maxProgress) {
            clearInterval(progressInterval);
            progressInterval = null;
        }
    }, 16); // ~60fps for smooth animation

    // Check if external app is loading
    checkExternalAppLoading();
};

const checkExternalAppLoading = () => {
    if (!props.useDynamicLoading) {
        // Fallback to immediate redirect if dynamic loading is disabled
        setTimeout(() => {
            completeRedirect();
        }, 2000);
        return;
    }

    // Create a hidden iframe to test the external URL
    iframe = document.createElement("iframe");
    iframe.style.display = "none";
    iframe.style.position = "absolute";
    iframe.style.left = "-9999px";
    iframe.src = props.targetUrl;

    document.body.appendChild(iframe);

    let hasStartedLoading = false;
    let loadingTimeout = null;

    const onLoad = () => {
        if (hasStartedLoading) return;
        hasStartedLoading = true;

        // External app has loaded, complete the progress
        progress.value = 100;
        setTimeout(() => {
            completeRedirect();
        }, 300);
    };

    const onError = () => {
        // If there's an error, still redirect but show completion
        progress.value = 100;
        setTimeout(() => {
            completeRedirect();
        }, 300);
    };

    // Set up event listeners
    iframe.addEventListener("load", onLoad);
    iframe.addEventListener("error", onError);

    // Fallback timeout in case the external app doesn't respond
    loadingTimeout = setTimeout(() => {
        if (!hasStartedLoading) {
            progress.value = 100;
            setTimeout(() => {
                completeRedirect();
            }, 300);
        }
    }, 10000); // 10 second timeout

    // Clean up function
    const cleanup = () => {
        if (iframe) {
            iframe.removeEventListener("load", onLoad);
            iframe.removeEventListener("error", onError);
            if (iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
            iframe = null;
        }
        if (loadingTimeout) {
            clearTimeout(loadingTimeout);
        }
    };

    // Store cleanup function for later use
    iframe._cleanup = cleanup;
};

const completeRedirect = () => {
    isLoading.value = false;

    // Clean up intervals
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }

    // Clean up iframe
    if (iframe && iframe._cleanup) {
        iframe._cleanup();
    }

    // Emit redirect event
    emit("redirect", props.targetUrl);
};

const stopProgress = () => {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
    if (loadingCheckInterval) {
        clearInterval(loadingCheckInterval);
        loadingCheckInterval = null;
    }
    if (iframe && iframe._cleanup) {
        iframe._cleanup();
    }
};

onMounted(() => {
    if (isVisible.value) {
        startDynamicProgress();
    }
});

onUnmounted(() => {
    stopProgress();
});

// Watch for visibility changes
watch(
    () => props.isVisible,
    (newValue) => {
        if (newValue) {
            startDynamicProgress();
        } else {
            stopProgress();
        }
    }
);

// Expose methods for external control
defineExpose({
    startDynamicProgress,
    stopProgress,
    completeRedirect,
});
</script>

<style scoped>
.external-redirect-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    max-width: 400px;
    width: 90%;
}

.progress-bar {
    width: 100%;
    height: 4px;
    background-color: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2bc8d3 0%, #2bc8d3 100%);
    border-radius: 2px;
    transition: width 0.1s ease-out;
    position: relative;
}

.progress-fill::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(255, 255, 255, 0.3) 50%,
        transparent 100%
    );
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.spinner {
    display: flex;
    align-items: center;
    justify-content: center;
}

.spinner-circle {
    width: 20px;
    height: 20px;
    border: 2px solid #e5e7eb;
    border-top: 2px solid #08c6d4;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

.progress-text {
    color: #374151;
    font-size: 14px;
    font-weight: 500;
    text-align: center;
}

/* Match Inertia's progress styling */
.external-redirect-progress {
    background-color: rgba(255, 255, 255, 0.95);
}

@media (prefers-reduced-motion: reduce) {
    .progress-fill::after {
        animation: none;
    }

    .spinner-circle {
        animation: none;
    }
}
</style>
