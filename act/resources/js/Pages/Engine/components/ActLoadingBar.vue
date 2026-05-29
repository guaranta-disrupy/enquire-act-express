<template>
    <div v-if="isVisible" class="loading-bar-overlay">
        <div class="loading-bar-container max-w-[600px] m-auto">
            <div
                class="ell-prog"
                :style="{ width: `${100 - currentProgress}%` }"
            ></div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, computed } from "vue";

const props = defineProps({
    isVisible: {
        type: Boolean,
        default: false,
    },
    progress: {
        type: Number,
        default: 0,
        validator: (value) => value >= 0 && value <= 100,
    },
});

const currentProgress = computed(() =>
    Math.max(0, Math.min(100, props.progress))
);
</script>

<style scoped>
.loading-bar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-bar-container {
    background: linear-gradient(
        to right,
        #e94f1d 0%,
        #e94f1d 23%,
        #e28733 27%,
        #e28733 48%,
        #1e71b8 52%,
        #1e71b8 73%,
        #00519e 77%,
        #00519e 100%
    );
    display: flex;
    height: 24px;
    width: 100%;
    border-radius: 16px;
    overflow: hidden;
    position: relative;
    border: solid 8px #fff;
    box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.2);
    box-sizing: border-box;
    animation: entranceEffect 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
}

.ell-prog {
    position: absolute;
    top: 0;
    bottom: 0;
    right: 0;
    background: rgba(210, 210, 210, 1);
    box-sizing: border-box;
    transition: width 0.3s ease-out;
}

@keyframes entranceEffect {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
