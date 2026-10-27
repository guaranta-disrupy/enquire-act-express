<template>
    <div class="w-full p-5">
        <VariableGuide
            v-if="variableGuide"
            @close-guide="variableGuide = false"
            @proceed="proceedToCreate"
        />
        <div v-else class="flex flex-col gap-4">
            <!-- Form Section -->
            <div v-if="showForm">
                <div class="grid grid-cols-6 gap-6">
                    <div
                        class="col-span-6 flex justify-between items-center gap-2"
                    >
                        <div class="flex-1">
                            <JetLabel value="Variable Name" />
                            <JetInput
                                v-model="form.name"
                                type="text"
                                class="w-full"
                                placeholder="Variable Name"
                            />
                            <JetInputError
                                :message="form.errors.name"
                                class="mt-2"
                            />
                        </div>
                        <div class="">
                            <JetLabel value="&nbsp;" />
                            <JetButton
                                variant="orange-light"
                                @click="showPredefinedVariableNames"
                                size="sm"
                            >
                                Use predefined variable names
                            </JetButton>
                        </div>
                    </div>
                    <div class="col-span-6 grid grid-cols-3 gap-4">
                        <div>
                            <JetLabel value="Criteria (one per line)" />
                            <textarea
                                v-model="bulkData.labels"
                                class="w-full focus:ring-[#257556] focus:border-[#257556] rounded-lg border-gray-300"
                                rows="8"
                            ></textarea>
                        </div>
                        <div>
                            <JetLabel
                                value="Value (align by line with criteria)"
                            />
                            <textarea
                                v-model="bulkData.values"
                                class="w-full focus:ring-[#257556] focus:border-[#257556] rounded-lg border-gray-300"
                                rows="8"
                            ></textarea>
                        </div>
                        <div>
                            <JetLabel
                                value="Equivalent (align by line with criteria)"
                            />
                            <textarea
                                v-model="bulkData.equivalents"
                                class="w-full focus:ring-[#257556] focus:border-[#257556] rounded-lg border-gray-300"
                                rows="8"
                            ></textarea>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 flex mt-4">
                    <div class="flex gap-2">
                        <JetButton
                            size="xs"
                            @click="saveVariable"
                            variant="slate"
                        >
                            {{
                                editingVariableIndex === null
                                    ? "Save Variable"
                                    : "Update Variable"
                            }}
                        </JetButton>
                        <JetButton
                            size="xs"
                            @click="cancelCreate"
                            variant="default"
                        >
                            Cancel
                        </JetButton>
                    </div>
                </div>
            </div>

            <!-- List Section -->
            <div v-if="!showForm">
                <div
                    v-if="mergedVariables.length === 0"
                    class="h-[40vmin] flex items-center justify-center"
                >
                    <EmptyPlaceholder
                        label="Variable"
                        @create="showForm = true"
                    />
                </div>
                <div v-else>
                    <div class="col-span-6 flex mb-2">
                        <div class="flex items-center justify-end w-full">
                            <JetButton
                                size="xs"
                                @click="showForm = true"
                                variant="slate"
                            >
                                Add New Variable
                            </JetButton>
                        </div>
                    </div>
                    <div class="col-span-6 max-h-[45vh] overflow-y-auto">
                        <template
                            v-for="(variable, index) in mergedVariables.sort(
                                (a, b) => b.id - a.id
                            )"
                            :key="variable.id || index"
                        >
                            <div
                                class="mt-3 hover:bg-slate-200/90 rounded-xl transform transition-all w-full bg-slate-100"
                            >
                                <!-- Row Header -->
                                <div class="px-6 py-4 w-full">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div
                                            class="flex items-center space-x-4"
                                        >
                                            <div>
                                                <div
                                                    class="flex items-center space-x-2 cursor-pointer select-none"
                                                    @click="
                                                        toggleExpandedVar(
                                                            variable.id || index
                                                        )
                                                    "
                                                >
                                                    <svg
                                                        :class="{
                                                            'rotate-180':
                                                                isExpandedVar(
                                                                    variable.id ||
                                                                        index
                                                                ),
                                                        }"
                                                        class="w-4 h-4 text-gray-500 transition-transform"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 9l-7 7-7-7"
                                                        />
                                                    </svg>
                                                    <h3
                                                        class="text-md font-medium text-gray-900"
                                                    >
                                                        {{ variable.name }}
                                                        <span
                                                            v-if="!variable.id"
                                                            class="text-xs text-green-500 bg-green-500/10 px-2 py-1 rounded-full"
                                                            >New</span
                                                        >
                                                    </h3>
                                                </div>
                                                <p
                                                    class="text-[0.7rem] text-gray-500 ml-6"
                                                >
                                                    {{
                                                        variable?.config
                                                            ?.criteria
                                                            ?.length || 0
                                                    }}
                                                    items
                                                </p>
                                            </div>
                                        </div>
                                        <div
                                            v-if="!variable.id"
                                            class="flex items-center space-x-3"
                                        >
                                            <JetButton
                                                size="xs"
                                                variant="primary-light"
                                                class="font-medium"
                                                @click.prevent="
                                                    editVariable(index)
                                                "
                                            >
                                                Update
                                            </JetButton>
                                            <JetButton
                                                size="xs"
                                                variant="danger-light"
                                                class="font-medium"
                                                @click.prevent="
                                                    deleteVariable(index)
                                                "
                                            >
                                                Delete
                                            </JetButton>
                                        </div>
                                    </div>
                                </div>

                                <!-- Collapsible Content -->
                                <div
                                    v-if="isExpandedVar(variable.id || index)"
                                    class=""
                                >
                                    <div class="overflow-x-auto">
                                        <table
                                            class="min-w-full divide-y divide-gray-200 table-fixed border border-t-0"
                                        >
                                            <thead class="bg-white">
                                                <tr>
                                                    <th
                                                        class="pl-7 px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8"
                                                    >
                                                        #
                                                    </th>
                                                    <th
                                                        class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3"
                                                    >
                                                        Criteria
                                                    </th>
                                                    <th
                                                        class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                    >
                                                        Condition
                                                    </th>
                                                    <th
                                                        class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3"
                                                    >
                                                        Value
                                                    </th>
                                                    <th
                                                        class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3"
                                                    >
                                                        Equivalent
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody
                                                class="bg-white divide-y divide-gray-200"
                                            >
                                                <tr
                                                    v-for="(
                                                        cr, key2
                                                    ) in variable.config
                                                        .criteria"
                                                    :key="key2"
                                                    class="hover:bg-gray-50"
                                                >
                                                    <td
                                                        class="pl-7 px-3 py-2 whitespace-nowrap text-sm text-gray-500"
                                                    >
                                                        {{ key2 + 1 }}
                                                    </td>
                                                    <td
                                                        class="px-3 py-2 text-sm text-gray-900 whitespace-normal break-words max-w-[420px]"
                                                    >
                                                        {{ cr.label }}
                                                    </td>
                                                    <td
                                                        class="px-3 py-2 text-sm text-gray-900"
                                                    >
                                                        {{
                                                            $varState.criteriaToString(
                                                                cr.conditions
                                                            )
                                                        }}
                                                    </td>
                                                    <td
                                                        class="px-3 py-2 text-sm text-gray-900 w-1/3 text-center align-middle"
                                                    >
                                                        <div
                                                            class="variable_value text-xs w-full h-full grid place-items-center"
                                                            v-html="
                                                                setProxiedHtml(
                                                                    cr.value
                                                                )
                                                            "
                                                        ></div>
                                                    </td>
                                                    <td
                                                        class="px-3 py-2 text-sm text-gray-900 w-1/3 whitespace-normal break-words"
                                                    >
                                                        {{ cr.equivalent }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <PredefinedVariablesModal
                :show="showPredefinedVariablesModal"
                @close="showPredefinedVariablesModal = false"
                @select-name="form.name = $event"
            />
        </div>
    </div>
</template>

<script setup>
import VariableGuide from "./VariableGuide.vue";
import { ref, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import Helpers from "@/Mixins/Helpers";
import { setProxiedHtml } from "@/utils/setProxiedHtml";
import JetButton from "@/Componentsv2/Button.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import EmptyPlaceholder from "../../PrimesTargets/EmptyPlaceholder.vue";
import PredefinedVariablesModal from "./PredefinedVariablesModal.vue";

const props = defineProps(["variables", "projectVariables"]);
const emit = defineEmits(["update:variables"]);
const { $varState, toast } = Helpers();
const variableGuide = ref(
    props.projectVariables.length === 0 && props.variables.length === 0
);
const showForm = ref(false);
const editingVariableIndex = ref(null);
const showPredefinedVariablesModal = ref(false);

const mergedVariables = computed(() => {
    // Important: reverse copies, not the original arrays (reverse() mutates)
    const localVars = [...props.variables].reverse();
    const localProjectVars = [...props.projectVariables].reverse();
    return [...localVars, ...localProjectVars];
});

function findVariableIndexInProps(variable) {
    if (!variable) return -1;
    return props.variables.findIndex((v) => {
        if (v?.id && variable?.id) return v.id === variable.id;
        if (!v?.id && !variable?.id) return v.name === variable.name;
        return false;
    });
}

const showPredefinedVariableNames = () => {
    showPredefinedVariablesModal.value = true;
};

const proceedToCreate = () => {
    variableGuide.value = false;
    showForm.value = true;
};

const form = useForm({
    name: "",
    config: {
        criteria: [],
    },
});

const bulkData = ref({
    labels: "",
    values: "",
    equivalents: "",
});

const expandedVariables = ref(new Set());

const editVariable = (index) => {
    const variable = mergedVariables.value[index];
    const indexInProps = findVariableIndexInProps(variable);
    if (indexInProps === -1) {
        toast.error("Only newly added variables can be edited here.");
        return;
    }
    editingVariableIndex.value = indexInProps;
    form.name = variable.name;
    bulkData.value.labels = variable.config.criteria
        .map((c) => c.label)
        .join("\n");
    bulkData.value.values = variable.config.criteria
        .map((c) => c.value)
        .join("\n");
    bulkData.value.equivalents = variable.config.criteria
        .map((c) => c.equivalent)
        .join("\n");
    showForm.value = true;
};

const saveVariable = () => {
    if (!form.name.trim()) {
        toast.error("Variable name is required.");
        return;
    }

    if (!bulkData.value.labels.trim()) {
        toast.error("Criteria field is required.");
        return;
    }

    const valuesForProcessing = bulkData.value.values.trim()
        ? bulkData.value.values
        : bulkData.value.labels;
    const equivalentsForProcessing = bulkData.value.equivalents.trim()
        ? bulkData.value.equivalents
        : bulkData.value.labels;

    const labels = bulkData.value.labels.split("\n");
    const values = valuesForProcessing.split("\n");
    const equivalents = equivalentsForProcessing.split("\n");

    const maxLength = Math.max(
        labels.length,
        values.length,
        equivalents.length
    );
    const newCriteria = [];

    for (let i = 0; i < maxLength; i++) {
        const label = labels[i] || "";
        const value = values[i] || "";
        const equivalent = equivalents[i] || "";

        if (label.trim() || value.trim() || equivalent.trim()) {
            newCriteria.push({
                label: label.trim(),
                value: value.trim(),
                equivalent: equivalent.trim(),
                conditions: [
                    {
                        method: "process",
                        var: "brc",
                        operator: "==",
                        val: `${newCriteria.length + 1}`,
                        left_operator: "and",
                        conditions: [],
                    },
                ],
            });
        }
    }

    const updatedVariables = [...props.variables];

    if (editingVariableIndex.value !== null) {
        const updatedVariable = {
            ...updatedVariables[editingVariableIndex.value],
            name: form.name,
            config: {
                criteria: newCriteria,
            },
        };
        updatedVariables[editingVariableIndex.value] = updatedVariable;
    } else {
        const newVariable = {
            name: form.name,
            config: {
                criteria: newCriteria,
            },
        };
        updatedVariables.push(newVariable);
    }

    emit("update:variables", updatedVariables);

    form.reset("name");
    bulkData.value = { labels: "", values: "", equivalents: "" };
    showForm.value = false;
    editingVariableIndex.value = null;
};

const cancelCreate = () => {
    form.reset();
    bulkData.value = { labels: "", values: "", equivalents: "" };
    showForm.value = false;
    editingVariableIndex.value = null;
};

const deleteVariable = (index) => {
    const variable = mergedVariables.value[index];
    const indexInProps = findVariableIndexInProps(variable);
    if (indexInProps === -1) {
        toast.error("This variable cannot be deleted here.");
        return;
    }
    const updatedVariables = [...props.variables];
    updatedVariables.splice(indexInProps, 1);
    emit("update:variables", updatedVariables);
};

function toggleExpandedVar(id) {
    const next = new Set(expandedVariables.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    expandedVariables.value = next;
}

function isExpandedVar(id) {
    return expandedVariables.value.has(id);
}
</script>
