<template>
    <div
        class="progress-bar transition-all duration-300 landscape:h-[0.6vmin] h-[1.3vmin]"
        :class="{
            'opacity-0': surveyTestState.testState === 'redirecting',
            'h-[0.8vmin]': deviceType === 'iPad' || deviceType === 'Tablet',
            'landscape:h-[1.2vmin]':
                deviceType === 'iPhone' || deviceType === 'Android',
        }"
        :style="{ '--progress': percentageStat + '%' }"
        v-if="status === 'not_started'"
    >
        <div class="progress-fill"></div>
    </div>
</template>

<script setup>
import { surveyTestState } from "../../utils/surveyTestStore";
import { computed } from "vue";
import { inject } from "vue";
import {
    isIPad,
    isTablet,
    isIOS,
    isAndroid,
} from "@basitcodeenv/vue3-device-detect";

const status = inject("status");

const percentageStat = computed(
    () => (surveyTestState.progress.count / surveyTestState.trialCount) * 100
);

const deviceType = computed(() => {
    if (isIPad) return "iPad";
    if (isTablet) return "Tablet";
    if (isIOS) return "iPhone";
    if (isAndroid) return "Android";
    return "Desktop";
});
</script>

<style scoped>
.progress-bar {
    width: 100%;
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
    overflow: hidden;
    position: relative;
    /* margin-bottom: 0.5rem; */
}

.progress-fill {
    height: 100%;
    background: lightgray;
    transition: width 0.3s ease;
    position: absolute;
    top: 0;
    right: 0;
    width: calc(100% - var(--progress));
}
</style>
