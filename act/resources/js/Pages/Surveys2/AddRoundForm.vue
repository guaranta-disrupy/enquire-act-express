<template>
    <div>
        <Create
            :survey="survey"
            :tests="tests"
            :action="action"
            :test="test"
            :userPermissions="userPermissions"
            :selectedTrialMode="selectedTrialMode"
            :templatePrimes="templatePrimes"
            :templateTargets="templateTargets"
            :project="project"
        />
    </div>
</template>

<script setup>
import Create from "./Create.vue";
import { ref } from "vue";

const props = defineProps({
    survey: {
        type: Object,
        default: () => ({}),
    },
    selectedTrialMode: {
        type: String,
        default: null,
    },
    templatePrimes: {
        type: Array,
        default: () => [],
    },
    templateTargets: {
        type: Array,
        default: () => [],
    },
    project: {
        type: Object,
        default: () => ({}),
    },
});

const tests = ref([]);
const action = ref("create");
const test = ref({});
const userPermissions = ref({
    canUpdateSurvey: true,
});
</script>

<style scoped></style>
