<template>
    <div class="flex flex-col items-center justify-center w-full h-full">
        <div class="flex flex-col items-center justify-center gap-4">
            <img
                v-if="surveyTestState.testState !== 'editor_survey_end'"
                :src="nextsensusLoading"
                alt="Carregando"
                class="w-[34vmin] h-auto landscape:w-[24vmin]"
                :class="{
                    'w-[28vmin]':
                        deviceType === 'ipad' || deviceType === 'tablet',
                }"
            />
            <h1
                v-if="type !== 'opening'"
                class="text-[5vmin] landscape:text-[3vmin] text-center"
                :class="{
                    'text-[2.5vmin]':
                        deviceType === 'ipad' || deviceType === 'tablet',
                }"
                :style="{ color: 'var(--redirect-text-color)' }"
                v-html="redirectingText"
            ></h1>
            <button
                v-if="surveyTestState.testState === 'editor_survey_end'"
                @click="restartPreview"
                class="btn rounded-full bg-[#15919A] text-white px-4 py-2 hover:shadow-lg"
            >
                Restart Preview
            </button>
        </div>
    </div>
</template>

<script setup>
import nextsensusLoading from "../../editor/assets/nextsensus/nextsensus-carregando.svg";
import { surveyTestState } from "../../utils/surveyTestStore";
import { computed, ref } from "vue";
import {
    isAndroid,
    isIOS,
    isIPad,
    isTablet,
} from "@basitcodeenv/vue3-device-detect";

const props = defineProps({
    type: {
        type: String,
        default: "opening",
    },
    isEditorMode: {
        type: Boolean,
        default: false,
    },
});

const deviceType = computed(() => {
    if (isIPad) return "ipad";
    if (isIOS) return "iphone";
    if (isTablet) return "tablet";
    if (isAndroid) return "android";
    return "desktop";
});

const restartPreview = () => {
    window.location.reload();
};

const redirectingText = computed(() => {
    switch (surveyTestState.testState) {
        case "saving":
            return surveyTestState.texts.saving;
        case "redirecting":
            return surveyTestState.texts.redirecting;
        case "error_saving":
        case "error_redirecting":
            return surveyTestState.texts.error_saving;
        case "survey_completed":
            return "You've successfully completed the survey.";
        case "editor_survey_end":
            return "End of survey preview.";
        default:
            return "";
    }
});
</script>

<style scoped>
.redirect-loader {
    /* background: linear-gradient(
        to right,
        #e94f1d 0%,
        #e94f1d 23%,
        #e28733 27%,
        #e28733 48%,
        #1e71b8 52%,
        #1e71b8 73%,
        #00519e 77%,
        #00519e 100%
    ); */
    border-radius: 50%;
}
</style>
