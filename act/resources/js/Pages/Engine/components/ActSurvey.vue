<template>
    <div
        v-if="status === 'not_started'"
        class="w-full h-full flex flex-col items-center justify-center relative"
    >
        <!-- Survey Content -->
        <!-- Test/Trial Components -->
        <TrialComponent
            :id="surveyTestState.currentTest.id"
            :config="surveyTestState.currentTest.config"
            @trial-complete="handleTrialComplete"
        />
    </div>
</template>

<script setup>
import { inject } from "vue";
import { surveyTestState } from "../utils/surveyTestStore";
import TrialComponent from "./trial/TrialComponent.vue";
import { useLogger } from "../utils/logger.js";
const { _log } = useLogger();

const props = defineProps({
    surveyData: Object,
});

const status = inject("status");

const handleTrialComplete = (response) => {
    _log("Trial completed:", response);
};
</script>

<style scoped></style>
