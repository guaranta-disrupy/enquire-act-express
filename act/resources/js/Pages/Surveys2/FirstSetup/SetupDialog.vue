<template>
    <JetDialogModal :show="show" maxWidth="6xl">
        <template #title>Test Configuration</template>

        <template #content>
            <div class="w-full max-h-[70vmin] pt-2">
                <div class="flex justify-center">
                    <ul
                        class="steps steps-horizontal text-sm mx-auto mt-[-4px] mb-4 w-3/4 cursor-pointer"
                    >
                        <li
                            class="step"
                            :class="{ 'step-primary': currentStep >= 1 }"
                        >
                            <span>Select Test Type</span>
                        </li>
                        <li
                            class="step"
                            :class="{
                                'step-primary': currentStep >= 2,
                                'disable-step': type == 2,
                            }"
                        >
                            <span :class="{ 'text-gray-300': type == 2 }"
                                >Add Variables</span
                            >
                        </li>
                    </ul>
                </div>
                <!-- content here -->
                <div class="w-full ">
                    <!-- step 1 : Test Type -->
                    <div v-if="currentStep === 1" class="step-content">
                        <div class="flex justify-center items-center">
                            <StepSelectType @set-type="setType" :type="type" />
                        </div>
                    </div>
                    <!-- step 2 : Add Variables -->
                    <div v-if="currentStep === 2" class="step-content">
                        <div class="flex justify-center items-center">
                            <StepAddVariables
                                :variables="variables"
                                :projectVariables="projectVariables"
                                @update:variables="variables = $event"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="w-full flex justify-between px-4 pb-2">
                <JetButton
                    variant="primary-dark"
                    @click="$emit('close-first-setup')"
                    size="sm"
                >
                    CANCEL
                </JetButton>
                <div class="step-actions">
                    <div class="gap-2 flex">
                        <JetButton
                            v-if="currentStep > 1"
                            variant="default"
                            @click="prevStep"
                            size="sm"
                        >
                            PREVIOUS
                        </JetButton>
                        <JetButton
                            variant="primary"
                            @click="nextStep"
                            :disabled="!canProceed"
                            size="sm"
                        >
                            {{
                                type === 2
                                    ? "SAVE"
                                    : currentStep === 1
                                    ? "NEXT"
                                    : "SAVE"
                            }}
                        </JetButton>
                    </div>
                </div>
            </div>
        </template>
    </JetDialogModal>
</template>

<script setup>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetButton from "@/Componentsv2/Button.vue";
import { ref, computed, inject } from "vue";
import StepSelectType from "./StepSelectType.vue";
import StepAddVariables from "./StepAddVariables.vue";

const form = inject("form");

const props = defineProps(["show", "survey", "projectVariables"]);
const emit = defineEmits([
    "close-first-setup",
    "set-type",
    "save-configuration",
    "save-configuration-variables",
]);

const type = ref(null);
const variables = ref([]);

const currentStep = ref(1);

const setType = (val) => {
    type.value = val;
    form.config.theme = val;
    form.config2.theme = val;
};

function nextStep() {
    if (type.value === 2) {
        emit("save-configuration");
    } else if (currentStep.value === 1) {
        currentStep.value++;
    } else if (currentStep.value === 2) {
        emit("save-configuration-variables", variables.value);
    }
}

// Stepper logic
const canProceed = computed(() => {
    if (currentStep.value === 1) return type.value !== null;
    if (currentStep.value === 2) return true;
    return false;
});

function prevStep() {
    if (currentStep.value > 1 && currentStep.value <= 3) currentStep.value--;
}
</script>

<style scoped>
.steps .step-primary::before,
.steps .step-primary .step-inner {
    background-color: #257556 !important;
    color: white;
}
.steps .step-primary:after {
    background-color: #257556 !important;
    border-color: #257556 !important;
}
.steps .disable-step.step {
    color: #9ca3af !important;
}

.steps .disable-step.step:after {
    background-color: #b6bbc4 !important;
    color: #d9dadd !important;
}
</style>
