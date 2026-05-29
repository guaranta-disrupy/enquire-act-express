<script setup>
import { inject, onBeforeMount, watch, ref } from "vue";
import JetButton from "@/Componentsv2/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import TestCriteria from "./TestCriteria.vue";
import Helpers from "@/Mixins/Helpers";
import { useTestsStore } from "@/stores/tests";

const { $testState, toast } = Helpers();
const dataStore = useTestsStore();

const props = defineProps(["form"]);

// Inject the testState from parent
const testState = inject("testState", $testState);

// Modal state
const showConditionModal = ref(false);
const selectedCondition = ref(false);
const workingConditions = ref([]); // Conditions being worked on in modal

// Ensure conditions array exists and keep testState in sync
onBeforeMount(() => {
    if (!props.form.config) {
        props.form.config = {};
    }
    if (!props.form.config.conditions) {
        props.form.config.conditions = [];
    }
    testState.criteria = props.form;
});

// Watch for changes in form and update testState
watch(
    () => props.form,
    (newForm) => {
        if (newForm) {
            testState.criteria = newForm;
        }
    },
    { deep: true }
);

const addSegment = () => {
    workingConditions.value.push({
        method: "process",
        left_operator: "and",
        var: "",
        operator: "==",
        val: "",
        conditions: [],
    });
};

const resetModal = () => {
    workingConditions.value = [];
    selectedCondition.value = false;
};

const openNewConditionModal = () => {
    resetModal();
    // Initialize with one empty process condition
    addSegment();
    showConditionModal.value = true;
};

const editCondition = () => {
    selectedCondition.value = true; // Indicate editing
    // deep copy to prevent mutating props directly before saving
    workingConditions.value = JSON.parse(
        JSON.stringify(props.form.config.conditions || [])
    );
    showConditionModal.value = true;
};

const deleteCondition = async () => {
    if (
        window.confirm(
            "Are you sure you want to delete this skip logic? This action cannot be undone."
        )
    ) {
        // Remove all conditions from the array
        props.form.config.conditions = [];

        // Update the store and persist
        try {
            await dataStore.saveTest();
            toast.success("Skip logic deleted successfully");
        } catch (e) {
            toast.error("Failed to delete skip logic");
        }
    }
};

const saveCondition = async () => {
    try {
        // Save working conditions to the actual form
        dataStore.updateConfig({
            conditions: [...workingConditions.value],
        });
        await dataStore.saveTest();
        showConditionModal.value = false;
        resetModal(); // Reset modal after saving
        toast.success("Condition saved successfully");
    } catch (e) {
        toast.error("Failed to save condition");
    }
};

const handleDelete = (indexes) => {
    let path = [...indexes];
    let lastIndex = path.pop();
    let parent = workingConditions.value;

    for (const index of path) {
        if (!parent[index] || !parent[index].conditions) {
            return;
        }
        parent = parent[index].conditions;
    }

    if (parent && typeof parent.splice === "function") {
        parent.splice(lastIndex, 1);
    }
};
</script>

<template>
    <div class="col-span-8">
        <div class="w-full mx-auto bg-white mt-3 rounded-xl p-4 border">
            <div class="flex items-center justify-between">
                <div class="text-base font-semibold">Skip logic</div>
                <JetButton
                    v-if="
                        !form.config.conditions ||
                        form.config.conditions.length === 0
                    "
                    type="button"
                    variant="primary-dark"
                    size="sm"
                    @click.prevent="openNewConditionModal"
                >
                    Add condition
                </JetButton>
            </div>

            <!-- details of skip logic options -->
            <div
                v-if="
                    !form.config.conditions ||
                    form.config.conditions.length == 0
                "
            >
                <div class="text-gray-500 text-sm space-y-2 mt-5 mb-2">
                    <p>
                        <strong>Skip logic</strong> allows you to control the
                        flow of your survey based on participant responses. By
                        setting up conditions, you can automatically direct
                        respondents to relevant questions or sections, hide
                        unnecessary items, or end the survey early if certain
                        criteria are met. This helps create a more personalized
                        and efficient survey experience for your participants.
                    </p>
                </div>
            </div>

            <div
                v-if="
                    form.config.conditions && form.config.conditions.length > 0
                "
            >
                <div
                    class="mt-4 mb-1 hover:bg-slate-200/90 rounded-xl bg-slate-100 px-4 py-4 flex items-center text-xs transition-all"
                >
                    <div class="w-10/12">
                        <div
                            class="font-mono text-gray-700 font-bold text-normal"
                        >
                          HIDDEN IF: {{
                                $testState.criteriaToString(
                                    form.config.conditions
                                )
                            }}
                        </div>
                    </div>
                    <div class="w-2/12 flex items-center gap-2 justify-end">
                        <JetButton
                            variant="gray-100"
                            size="xs"
                            @click.prevent="editCondition"
                        >
                            Update
                        </JetButton>
                        <JetButton
                            variant="gray-100"
                            class="text-red-500"
                            size="xs"
                            @click.prevent="deleteCondition"
                        >
                            Delete
                        </JetButton>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <JetDialogModal
        :show="showConditionModal"
        @close="showConditionModal = false"
    >
        <template #title>
            <div class="flex items-center justify-between">
                <div>Add condition</div>
                <div class="pt-1">
                    <JetButton
                        type="button"
                        variant="slate"
                        size="xs"
                        @click.prevent="addSegment"
                    >
                        Add condition
                    </JetButton>
                </div>
            </div>
        </template>

        <template #content>
            <div class="modal-scroll h-[60vmin]">
                <div class="">
                    <div
                        v-for="(condition, index) in workingConditions"
                        :key="index"
                        class="relative mb-4"
                    >
                        <TestCriteria
                            :deep="0"
                            :condition="condition"
                            :indexes="[index]"
                            @delete="handleDelete"
                        />
                    </div>

                    <div
                        class="mt-6"
                        v-if="workingConditions && workingConditions.length > 0"
                    >
                        <div class="text-xs text-base mb-1">Current logic:</div>
                        <code
                            class="text-black-500 text-xs bg-[#23aeb830] p-2 rounded block"
                            >HIDDEN IF:{{
                                $testState.criteriaToString(workingConditions)
                            }}</code
                        >
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <JetButton
                variant="default"
                class="uppercase"
                @click="showConditionModal = false"
                size="sm"
            >
                Nevermind
            </JetButton>

            <JetButton
                @click.prevent="saveCondition"
                size="sm"
                type="button"
                class="ml-2 uppercase"
                variant="primary"
            >
                Save condition
            </JetButton>
        </template>
    </JetDialogModal>
</template>

<style scoped>
.c {
    max-height: calc(100vh - 180px);
}

.modal-scroll {
    max-height: 70vh;
    overflow-y: auto;
}
</style>
