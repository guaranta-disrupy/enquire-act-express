<script setup>
import S4Button from "@/S4/Button.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4Input from "@/S4/Input.vue";
import S4TextArea from "@/S4/TextArea.vue";
import JetLabel from "@/Jetstream/Label.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import ChoiceSettingModal from "./matrix-multi/ChoiceSetting.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import {
    computed,
    nextTick,
    onBeforeMount,
    ref,
    watch,
    inject,
    reactive,
} from "vue";
import helpers from "@/helpers.js";
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";

const { assign } = helpers();

const add_edit_form = inject("add_edit_form");

const matrixRefs = reactive({
    colRefs: [],
    rowRefs: [],
});
const showColTextArea = ref(false);
const showRowTextArea = ref(false);
const rowArea = ref(null);
const columnArea = ref(null);
const new_column = ref("");
watch(new_column, async (val) => {
    if (val != "") {
        add_edit_form.items.push({
            title: val,
            column: [],
            style: { width: "20%" },
        });
        await nextTick();
        matrixRefs.colRefs[add_edit_form.items.length - 1].focus();
        new_column.value = "";
    }
});

const new_row = ref("");
watch(new_row, async (val) => {
    if (val != "") {
        add_edit_form.rows.push({
            name: val,
            value: add_edit_form.rows.length,
        });
        await nextTick();
        matrixRefs.rowRefs[add_edit_form.rows.length - 1].focus();
        new_row.value = "";
    }
});
//modals
const modals = reactive({
    question: {
        choice_setting: false,
        method: "add",
        data: null,
        choiceIndex: null,
        index: 0,
    },
});

const randomChoices = computed(() => {
    let list = {
        random: [],
    };
    for (const key in add_edit_form.rows) {
        const val = parseInt(key) + 1;
        var choice_label = "Row " + val + ": ";
        if (add_edit_form.rows[key].name != null) {
            if (add_edit_form.rows[key].name.length > 60) {
                choice_label += add_edit_form.rows[key].name.substring(0, 60);
            } else {
                choice_label += add_edit_form.rows[key].name;
            }
        }

        let data = {
            value: val,
            label: choice_label,
        };

        list.random.push({ ...data });
    }
    return list;
});

onBeforeMount(() => {
    initialized();
});

const initialized = () => {
    _.forEach(add_edit_form.items, (cols) => {
        if ("style" in cols == false) {
            cols["style"] = {
                width: "20%",
            };
        }
    });
};

//modal methods
const showModal = (table, modal, method, index = -1) => {
    modals[table][modal] = true;
    modals[table].method = method;
    modals[table].choiceIndex = index;
    if (index >= 0) {
        modals[table].data = assign(add_edit_form.items[index]);
        modals[table].index = index;
    } else {
        modals[table].data = null;
    }
};

const closeModal = (table, modal) => {
    modals[table][modal] = false;
    modals[table].data = null;
};

const splitForColumn = () => {
    if (columnArea.value != null) {
        let newCols = columnArea.value.split("\n");
        _.forEach(newCols, function (col, index) {
            add_edit_form.items.push({
                title: col,
                column: [],
                style: { width: "20%" },
            });
        });
    }
    columnArea.value = null;
    showColTextArea.value = false;
};
const splitForRow = () => {
    if (rowArea.value != null) {
        let newRow = rowArea.value.split("\n");
        _.forEach(newRow, (row, index) => {
            add_edit_form.rows.push({
                name: row,
                value: add_edit_form.rows.length,
            });
        });
    }

    rowArea.value = null;
    showRowTextArea.value = false;
};

const cancelMultipleColumns = () => {
    showColTextArea.value = false;
    // Clear the text area
    columnArea.value = '';
};

const cancelMultipleRows = () => {
    showRowTextArea.value = false;
    // Clear the text area
    rowArea.value = '';
};
</script>
<template>
    <div class="grid grid-cols-12 gap-1">
        <jet-label class="font-bold">Columns:</jet-label>
        <div class="col-span-12 -mt-6 grid grid-cols-12 gap-1">
            <template v-if="add_edit_form.items.length > 0">
                <template
                    v-for="(cols, index) in add_edit_form.items"
                    :key="index"
                >
                    <div class="col-span-8">
                        <jet-label class="font-bold">&nbsp;</jet-label>
                        <div class="flex">
                            <span class="mt-2 mr-2">{{ index + 1 }}</span>
                            <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="Enter title"
                                v-model="add_edit_form.items[index].title"
                                :ref="
                                    (el) => {
                                        matrixRefs.colRefs[index] = el;
                                    }
                                "
                            >
                            </s4-input>
                        </div>
                    </div>
                    <div class="col-span-4">
                        <jet-label class="font-bold" v-if="index == 0"
                            >Width:</jet-label
                        >
                        <div class="flex gap-1">
                            <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="Width e.g. 20%"
                                v-model="cols.style.width"
                            >
                            </s4-input>
                            <!-- <s4-button
                                color="ssrBlueIcon"
                                class="mb-1 ml-1"
                                @click="
                                    showModal(
                                        'question',
                                        'choice_setting',
                                        'add',
                                        index
                                    )
                                "
                            >
                                <font-awesome-icon
                                    class="fa-w-20 fa-h-20"
                                    :icon="afaIcon.cog"
                                />
                            </s4-button> -->
                            <ButtonIconsVue
                                @click="
                                    showModal(
                                        'question',
                                        'choice_setting',
                                        'add',
                                        index
                                    )
                                "
                                icon="setting"
                            />
                            <ButtonIconsVue
                                @click="add_edit_form.items.splice(index, 1)"
                                icon="delete"
                            />
                            <!-- <s4-button color="ssrRedIcon" class="mb-1 ml-1" @click="add_edit_form.items.splice(index,1)">
                                <font-awesome-icon class="fa-w-20 fa-h-20" :icon="faIcon.trash" />
                            </s4-button> -->
                        </div>
                    </div>
                </template>
            </template>
        </div>
        <div class="col-span-12">
            <s4-input
                color="ssrGray2"
                class="w-full mb-1"
                placeholder="Input new column"
                v-model="new_column"
            ></s4-input>
            <s4-button
                :theme="'ssrAddColRow'"
                v-if="showColTextArea == false"
                class="py-1 px-2 mt-2 mb-1"
                @click="showColTextArea = !showColTextArea"
            >
                <!--<font-awesome-icon :icon="faIcon.plus" class="mr-2" />-->
                Multiple Columns
            </s4-button>
            <div class="col-span-12">
                <s4-text-area
                    color="ssrGray"
                    v-show="showColTextArea"
                    v-model="columnArea"
                    class="w-full h-40"
                    placeholder="Enter choices separated by new line..."
                ></s4-text-area>
            </div>
            <div class="col-span-3" v-show="showColTextArea">
                <s4-button
                    :theme="'ssrAddColRow'"
                    class="py-1 px-2 mt-1 mb-2"
                    @click="splitForColumn()"
                    >Add</s4-button
                >
                <s4-button
                    :theme="'ssrAddColRow'"
                    class="py-1 px-2 ml-1 mt-1"
                    @click="cancelMultipleColumns()"
                    >Cancel</s4-button
                >
            </div>
        </div>

        <div class="col-span-12 mt-5">
            <jet-label class="font-bold">Rows:</jet-label>
            <template v-for="(row, index) in add_edit_form.rows" :key="index">
                <div class="flex gap-1">
                    <span class="mt-2 mr-2">{{ index + 1 }}</span>
                    <s4-input
                        color="ssrGray"
                        class="w-full mb-1"
                        placeholder="Enter choice"
                        v-model="add_edit_form.rows[index].name"
                        :ref="
                            (el) => {
                                matrixRefs.rowRefs[index] = el;
                            }
                        "
                    >
                    </s4-input>
                    <ButtonIconsVue
                        @click="add_edit_form.rows.splice(index, 1)"
                        icon="delete"
                    />
                    <!-- <s4-button color="ssrRedIcon" class="mb-1 ml-1" @click="add_edit_form.rows.splice(index, 1)">
                        <font-awesome-icon class="fa-w-20 fa-h-20" :icon="faIcon.trash" />
                    </s4-button> -->
                </div>
            </template>
            <s4-input
                color="ssrGray2"
                class="w-full"
                placeholder="Input new choice"
                v-model="new_row"
            ></s4-input>
            <s4-button
                v-if="showRowTextArea == false"
                :theme="'ssrAddColRow'"
                class="py-1 px-2 mt-3 mb-5"
                color="ssrB"
                @click="showRowTextArea = !showRowTextArea"
            >
                <!--<font-awesome-icon :icon="faIcon.plus" class="mr-2" />-->
                Multiple Rows
            </s4-button>
            <s4-text-area
                color="ssrGray"
                v-show="showRowTextArea"
                v-model="rowArea"
                class="w-full h-40"
                placeholder="Enter choices separated by new line..."
            ></s4-text-area>
            <div class="col-span3" v-show="showRowTextArea">
                <s4-button
                    :theme="'ssrAddColRow'"
                    class="py-1 px-2 mt-2 mb-2 mb-2"
                    @click="splitForRow()"
                    >Add</s4-button
                >
                <s4-button
                    :theme="'ssrAddColRow'"
                    class="py-1 px-2 ml-1 mt-2 mb-10"
                    @click="cancelMultipleRows()"
                    >Cancel</s4-button
                >
            </div>
        </div>
    </div>

    <BorderSetup></BorderSetup>

    <div class="mt-4 grid grid-cols-12 gap-1">
        <div class="col-span-6 grid grid-cols-6 gap-1">
            <div class="col-span-6">
                <label class="items-center cursor-pointer">
                    <s4-checkbox
                        class="cursor-pointer"
                        v-model:checked="add_edit_form.setup.validate.required"
                    />
                    <span class="ml-2 text-sm text-gray-600 select-none"
                        >Required</span
                    >
                </label>
                <jet-label class="mt-2">Error Message:</jet-label>
                <s4-input
                    color="ssrGray"
                    class="w-full mb-1"
                    :disabled="add_edit_form.setup.validate.required == 0"
                    v-model="add_edit_form.setup.validate.errorMsg"
                >
                </s4-input>
            </div>
            <div class="col-span-3">
                <jet-label>Box style size:</jet-label>
                <s4-input
                    color="ssrGray"
                    class="w-full mb-1"
                    placeholder="e.g. md"
                    v-model="add_edit_form.setup.boxStyle.size"
                >
                </s4-input>
            </div>
            <div class="col-span-3">
                <jet-label>Box style max-width:</jet-label>
                <s4-input
                    color="ssrGray"
                    class="w-full mb-1"
                    placeholder="e.g. 100%"
                    v-model="add_edit_form.setup.boxStyle['max-width']"
                >
                </s4-input>
            </div>
        </div>

        <div class="col-span-6 mt-7">
            <div class="col-span-6 border border-grey-200 p-1 rounded-lg">
                <div class="col-span-6">
                    <label class="items-center cursor-pointer">
                        <s4-checkbox
                            class="cursor-pointer"
                            v-model:checked="add_edit_form.setup.selectMultiple"
                        />
                        <span class="ml-2 text-sm text-gray-600 select-none"
                            >Multiple Select</span
                        >
                    </label>
                </div>
            </div>

            <div
                class="mt-1 col-span-6 p-1 rounded-lg"
                :class="[
                    add_edit_form.setup.random
                        ? 'bg-white mb-3'
                        : 'bg-gray-100',
                ]"
            >
                <div class="col-span-6">
                    <label class="items-center cursor-pointer">
                        <s4-checkbox
                            class="cursor-pointer"
                            v-model:checked="add_edit_form.setup.random"
                        />
                        <span class="ml-2 text-sm text-gray-600 select-none"
                            >Random</span
                        >
                    </label>
                </div>
                <div class="col-span-6">
                    <div
                        class="grid grid-cols-6 mt-3"
                        v-if="
                            add_edit_form.setup.random == true ||
                            add_edit_form.setup.random == 1
                        "
                    >
                        <div class="col-span-6">
                            <jet-label>Select Rows to be randomized:</jet-label>
                            <vue-form-multiselect
                                :disabled="add_edit_form.setup.random == 0"
                                placeholder="Click to select choices"
                                :options="randomChoices.random"
                                v-model="add_edit_form.setup.randomRows"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <choice-setting-modal
        :modal="modals.question"
        @close="closeModal('question', 'choice_setting')"
    />
</template>

<style scoped>
.ssrBT {
    border: none;
    border-radius: 50%;
    width: 2.65rem;
    /* width:2rem;
    height:2rem; */
}

.ssrBT:hover {
    border: 1px solid;
    border-radius: 50%;
}
</style>
