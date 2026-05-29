<script setup>
import S4Checkbox from "@/S4/Checkbox.vue";
import S4TextArea from "@/S4/TextArea.vue";
import S4Input from "@/S4/Input.vue";
import S4InputSelect from "@/S4/InputSelect.vue";
import S4Button from "@/S4/Button.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import JetLabel from "@/Jetstream/Label.vue";
import WarningModal from "../../../../S4/Warning.vue";
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import TE2 from "@/S4/TE2.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";

import { faTrashAlt, faPlus, faImage } from "@fortawesome/free-solid-svg-icons";
// import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    computed,
    nextTick,
    onBeforeMount,
    reactive,
    ref,
    watch,
    inject,
} from "vue";
import { usePage } from "@inertiajs/vue3";
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";
import helpers from "@/helpers.js";

const add_edit_form = inject("add_edit_form");

// Match write-in types with other question types to avoid type/value conflicts
const wr_types = [
    { value: 1, text: "Open Text" },
    { value: 9, text: "Letters Only (A-Z, a-z)" },
    { value: 10, text: "Alphanumeric" },
    { value: 2, text: "Whole Number" },
    { value: 3, text: "Decimal Number" },
    { value: 4, text: "Email Address" },
    { value: 5, text: "Date" },
    { value: 6, text: "Time" },
    { value: 7, text: "Date & Time" },
    // { value: 8, text: "Date Range" },
];

const date_formats = [
    { value: "mm-dd-yyyy", text: "MM/DD/YYYY" },
    { value: "dd-mm-yyyy", text: "DD/MM/YYYY" },
    { value: "yyyy-mm-dd", text: "YYYY-MM-DD" },
    { value: "dd-mmm-yyyy", text: "DD-MMM-YYYY" },
    { value: "mmm-dd-yyyy", text: "MMM DD, YYYY" },
    { value: "dd-month-yyyy", text: "DD Month YYYY" },
];

const time_formats = [
    { value: "12-hour", text: "12-Hour (e.g., 2:30 PM)" },
    { value: "24-hour", text: "24-Hour (e.g., 14:30)" },
    { value: "12-hour-seconds", text: "12-Hour with Seconds (e.g., 2:30:45 PM)" },
    { value: "24-hour-seconds", text: "24-Hour with Seconds (e.g., 14:30:45)" },
];

const datetime_formats = [
    { value: "dd-mmm-yyyy hh:mm", text: "dd-mmm-yyyy hh:mm" },
    { value: "dd-mmm-yyyy hh:mm:ss.s", text: "dd-mmm-yyyy hh:mm:ss.s" },
    { value: "dd-mmm-yyyy hh:mm:ss", text: "dd-mmm-yyyy hh:mm:ss" },
    { value: "yyyy-mm-dd hh:mm", text: "yyyy-mm-dd hh:mm" },
    { value: "yyyy-mm-dd hh:mm:ss", text: "yyyy-mm-dd hh:mm:ss" },
    { value: "yyyy-mm-dd hh:mm:ss.s", text: "yyyy-mm-dd hh:mm:ss.s" },
];

const write_in_choice = reactive({
    enable: 1,
    equivalent: "",
    "max-width": "",
    validate: {
        type: 1, //[Specific Length, Whole Number, Decimal Number, Email Address, Date, Time, Date Time]
        min: 1, //For SL, WN, DN
        max: 10, //For SL, WN, DN
        errorMsg: "This question requires an answer",
        dateFormat: "mm-dd-yyyy", // Default date format
        timeFormat: "12-hour", // Default time format
        datetimeFormat: "dd-mmm-yyyy hh:mm", // Default datetime format
    },
});

const matrixRefs = reactive({
    colRefs: [],
    rowRefs: [],
});


const newcolumn_choice = ref("");
watch(newcolumn_choice, async (val) => {
    if (val !== "" && val !== "<p><br></p>") {

        // ** NEW **
        let len = add_edit_form.columns.length
        if (len) {
            if (add_edit_form.columns[len - 1]?.index) {
                indexedItems()
            } else {
                add_edit_form.columns.push({
                    name: val,
                    value: add_edit_form.columns.length + 1,
                    style: { width: "20%" },
                });
            }
        } else {
            indexedItems()
        }
        function indexedItems() {
            add_edit_form.columns.push({
                name: val,
                value: add_edit_form.columns.length + 1,
                style: { width: "20%" },
                index: add_edit_form.columns.length + 1,
                score: add_edit_form.columns.length + 1,
            });
        }

        // ** OLD **
        // add_edit_form.columns.push({
        //     name: val,
        //     value: add_edit_form.columns.length,
        //     style: { width: "20%" },
        // });

        await nextTick();
        matrixRefs.colRefs[add_edit_form.columns.length - 1].focus();
        newcolumn_choice.value = "<p><br></p>";
    }
});

const newrow_choice = ref("");
watch(newrow_choice, async (val) => {
    if (val !== "" && val !== "<p><br></p>") {

        // ** NEW **
        let len = add_edit_form.rows.length
        if (len) {
            if (add_edit_form.rows[len - 1]?.index) {
                indexedItems()
            } else {
                add_edit_form.rows.push({
                    name: val,
                    value: add_edit_form.rows.length + 1,
                    optional: false,
                    enable_wr: false,
                });
            }
        } else {
            indexedItems()
        }
        function indexedItems() {
            add_edit_form.rows.push({
                name: val,
                value: add_edit_form.rows.length + 1,
                optional: false,
                enable_wr: false,
                index: add_edit_form.rows.length + 1,
                // score: add_edit_form.rows.length + 1,
            });
        }

        // ** OLD **
        // add_edit_form.rows.push({
        //     name: val,
        //     value: add_edit_form.rows.length,
        //     optional: false,
        //     enable_wr: false,
        // });

        await nextTick();
        matrixRefs.rowRefs[add_edit_form.rows.length - 1].focus();
        newrow_choice.value = "<p><br></p>";
    }
});

const faIcon = {
    trash: faTrashAlt,
    plus: faPlus,
    image: faImage,
};
const showColTextArea = ref(false);
const showRowTextArea = ref(false);
const rowArea = ref(null);
const equivalentRowArea = ref(null);
const columnArea = ref(null);
const equivalentColumnArea = ref(null);
const gallery = reactive({
    show: false,
});

const action = ref("add");
const selected_item = ref({});

const randomChoices = computed(() => {
    let list = {
        // random: [],
        select_all: [],
        unselect_all: [],
    };
    for (const key in add_edit_form.columns) {
        const val = parseInt(key) + 1;
        //var choice_label = "Column " + val + ": ";
        var choice_label = "<span>" + val + ". </span>";
        if (add_edit_form.columns[key].name != null) {
            // if (add_edit_form.columns[key].name.length > 60) {
            //     choice_label += add_edit_form.columns[key].name.substring(
            //         0,
            //         60
            //     );
            // } else {
            choice_label += add_edit_form.columns[key].name;
            //}
        }

        let data = {
            value: val,
            label: choice_label,
        };

        //disable some select
        if (add_edit_form.setup.selectAll.includes(val)) {
            list.unselect_all.push({ ...data, ...{ disabled: true } });
        } else {
            list.unselect_all.push({ ...data });
        }
        if (add_edit_form.setup.unSelectAll.includes(val)) {
            list.select_all.push({ ...data, ...{ disabled: true } });
        } else {
            list.select_all.push({ ...data });
        }
    }
    return list;
});

onBeforeMount(() => {
    initialized();
});

const initialized = () => {
    for (const key in add_edit_form.rows) {
        //add enable_wr for old choices
        if ("enable_wr" in add_edit_form.rows[key] == false) {
            if ("wc" in add_edit_form.rows[key]) {
                add_edit_form.rows[key]["enable_wr"] = 1;
            } else {
                add_edit_form.rows[key]["enable_wr"] = 0;
            }
        }
    }
    _.forEach(add_edit_form.columns, function (cols) {
        if ("style" in cols == false) {
            cols["style"] = {
                width: "20%",
            };
        }
    });
};

const showColArea = () => {
    showColTextArea.value = !showColTextArea.value;
};
const showRowArea = () => {
    showRowTextArea.value = !showRowTextArea.value;
};
const revertCol = () => {
    showColTextArea.value = false;
    // Clear all text areas
    columnArea.value = '<p><br></p>';
    equivalentColumnArea.value = '';
};
const revertRow = () => {
    showRowTextArea.value = false;
    // Clear all text areas
    rowArea.value = '<p><br></p>';
    equivalentRowArea.value = '';
};

const splitForColumn = () => {
    if (columnArea.value != null) {
        const newEq = equivalentColumnArea.value?.split("\n") ?? [];
        const newCols =
            columnArea.value.match(/<p>(.*?)<\/p>/g)?.map((p) =>
                p.replace(/<\/?p>/g, "").trim()
            ) || [];

        _.forEach(newCols, function (col, index) {
            if (!col) {
                return;
            }

            const eqRaw = newEq[index]?.trim?.();
            const equivalent = eqRaw && eqRaw !== "" ? eqRaw : col;

            const len = add_edit_form.columns.length;

            if (len) {
                if (add_edit_form.columns[len - 1]?.index) {
                    indexedItems();
                } else {
                    add_edit_form.columns.push({
                        name: "<p>" + col + "</p>",
                        equivalent,
                        value: add_edit_form.columns.length + 1,
                        style: { width: "20%" },
                    });
                }
            } else {
                indexedItems();
            }

            function indexedItems() {
                add_edit_form.columns.push({
                    name: col,
                    equivalent,
                    value: add_edit_form.columns.length + 1,
                    style: { width: "20%" },
                    index: add_edit_form.columns.length + 1,
                    score: add_edit_form.columns.length + 1,
                });
            }
        });
    }
    columnArea.value = "<p><br></p>";
    equivalentColumnArea.value = "";
    showColTextArea.value = false;
};

const splitForRow = () => {
    if (rowArea.value != null) {
        const newEq = equivalentRowArea.value?.split("\n") ?? [];
        const newRow =
            rowArea.value.match(/<p>(.*?)<\/p>/g)?.map((p) =>
                p.replace(/<\/?p>/g, "").trim()
            ) || [];
        _.forEach(newRow, (row, index) => {

            let len = add_edit_form.rows.length

            if (len) {
                if (add_edit_form.rows[len - 1]?.index) {
                    indexedItems()
                } else {
                    const eqRaw = newEq[index]?.trim?.();
                    const equivalent = eqRaw && eqRaw !== "" ? eqRaw : row;

                    add_edit_form.rows.push({
                        name: "<p>" + row + "</p>",
                        equivalent,
                        value: add_edit_form.rows.length + 1,
                        optional: false,
                        enable_wr: false,
                    });
                }
            } else {
                indexedItems()
            }

            function indexedItems() {
                const eqRaw = newEq[index]?.trim?.();
                const equivalent = eqRaw && eqRaw !== "" ? eqRaw : row;

                add_edit_form.rows.push({
                    name: row,
                    equivalent,
                    value: add_edit_form.rows.length + 1,
                    optional: false,
                    enable_wr: false,
                    index: add_edit_form.rows.length + 1,
                    // score: add_edit_form.rows.length + 1,
                });
            }
        });
    }

    rowArea.value = "<p><br></p>";
    equivalentRowArea.value = "";
    showRowTextArea.value = false;
};

const showGallery = (index, action) => {
    //action interact with add or edit
    selected_item.value = add_edit_form.rows[index];
    action.value = action;
    gallery.show = true;
};
const closeGallery = () => {
    gallery.show = false;
};
const selectGallery = (el) => {
    const { ImageWithHeaders } = helpers();
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    const src_value = el.file_path
        ? `${fluentBase}/storage/${el.file_path}`
        : ImageWithHeaders(usePage().props.app_url + '/gallery/' + (el.filename || el.file_name));
    if (action.value == "add")
        newrow_choice.value = '<img src="' + src_value + '"/>';
    else selected_item.value.name = '<img src="' + src_value + '"/>';
};

const addWC = (index) => {
    if (add_edit_form.rows[index].enable_wr) {
        add_edit_form.rows[index]["wc"] = _.cloneDeep(write_in_choice);
    } else {
        delete add_edit_form.rows[index].wc;
    }
};

//const for box style max-width
const boxStyleValue = ref('');
const boxStyleUnit = ref('%');

const maxWidthComputed = computed({
    get() {
        return boxStyleUnit.value === "auto"
            ? "auto"
            : `${boxStyleValue.value}${boxStyleUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            boxStyleValue.value = null; // Clear the numeric value
            boxStyleUnit.value = "auto";
        } else {
            const match = newValue.match(/^(\d+)([a-z%]+)$/);
            if (match) {
                boxStyleValue.value = parseFloat(match[1]);
                boxStyleUnit.value = match[2];
            }
        }
    }
});

watch(maxWidthComputed, (newValue) => {
    add_edit_form.setup.boxStyle['max-width'] = newValue;
});

onBeforeMount(() => {
    if (!('boxStyle' in add_edit_form.setup)) {
        add_edit_form.setup['boxStyle'] = { 'max-width': '100%' };
    }
    const maxWidth = add_edit_form.setup.boxStyle['max-width'] || '100%';
    if (maxWidth === "auto") {
        boxStyleValue.value = null;
        boxStyleUnit.value = "auto";
    } else {
        const match = maxWidth.match(/^(\d+)([a-z%]+)$/);
        if (match) {
            boxStyleValue.value = parseFloat(match[1]);
            boxStyleUnit.value = match[2];
        }
    }
})

const showDeleteAllColumn = ref(false);
const deleteAllColumns = () => {
    add_edit_form.columns = [];
    showDeleteAllColumn.value = false;
};
const showDeleteAllRow = ref(false);
const deleteAllRows = () => {
    add_edit_form.rows = [];
    showDeleteAllRow.value = false;
};

if (!add_edit_form.setup.boxStyle['max-width_enabler']) {
    add_edit_form.setup.boxStyle['max-width_enabler'] = true;
}
</script>
<template>
    <div class="grid grid-cols-12 gap-1">
        <div class="flex justify-end col-span-12" v-if="add_edit_form.columns.length > 0">
            <button @click="showDeleteAllColumn = true"
                class="inline-flex items-center hover:bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 mr-2 transition-all duration-300 ease-in-out hover:text-red-600" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>

                <span class="transition-all duration-300 ease-in-out hover:text-red-600">
                    Delete All Columns
                </span>
            </button>
        </div>

        <div class="col-span-12 mt-3">
            <!-- Headers -->
            <div v-if="add_edit_form.columns.length > 0" class="grid grid-cols-[50px_1fr_1fr_100px] gap-1 mb-2">
                <div class="flex items-center justify-center">
                    <jet-label class="font-bold">Index</jet-label>
                </div>
                <div class="flex items-center">
                    <jet-label class="font-bold pl-4">Column</jet-label>
                </div>
                <div class="flex items-center">
                    <jet-label class="font-bold">Equivalent</jet-label>
                </div>
                <div class="flex items-center">
                    <jet-label class="font-bold">Width</jet-label>
                </div>
            </div>

            <!-- Data Rows -->
            <template v-for="(cols, index) in add_edit_form.columns" :key="index">
                <div class="grid grid-cols-[50px_1fr_1fr_110px] gap-1 mb-1">
                    <div class="flex items-center justify-center">
                        <span class="text-sm font-medium">{{ index + 1 }}</span>
                    </div>
                    <div class="flex">
                        <TE2 placeholder="Enter Column Name" :modelValue="cols.name"
                            @update:modelValue="(val) => { cols.name = val }" :ref="(el) => {
                                matrixRefs.colRefs[index] = el;
                            }" @mousedown.stop></TE2>
                    </div>
                    <div class="flex gap-1">
                        <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                            v-model="cols.equivalent">
                        </s4-input>
                    </div>
                    <div class="flex gap-1">
                        <s4-input color="ssrGray" class="w-full" placeholder="Width e.g. 20%"
                            v-model="cols.style.width">
                        </s4-input>
                        <ButtonIconsVue class="pl-2 pr-2" icon="delete"
                            @click="add_edit_form.columns.splice(index, 1)" />
                    </div>
                </div>
            </template>

            <!-- <s4-input v-if="add_edit_form.columns.length > 0" color="orange" class="w-full mb-1" placeholder="Input new choice" v-model="newcolumn_choice"></s4-input> -->
            <div class="col-span-12 mt-1">
                <jet-label class="font-bold">Columns</jet-label>
                <!-- <s4-input color="ssrGray" class="w-full" placeholder="Input new column"
                    v-model="newcolumn_choice"></s4-input> -->
                <TE2 v-if="showColTextArea == false" placeholder="Input new column" :modelValue="newcolumn_choice"
                    @update:modelValue="(val) => { newcolumn_choice = val }"></TE2>
            </div>
            <!-- <s4-button :theme="'ssrAddColRow'" v-if="showColTextArea == false" class="py-1 px-2 mt-2 mb-1"
                @click="showColArea()" style="width: 131px !important">
               
                Multiple Columns
            </s4-button> -->
            <button v-if="showColTextArea == false" :theme="'ssrAddColRow'" color="ssrB" @click="showColArea()"
                class="inline-flex w-[175px] items-center px-2 py-1 mb-2 mt-1 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
                <svg width="32" height="32" viewBox="0 0 980 980" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z"
                        fill="white" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z"
                        fill="white" />
                    <path
                        d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z"
                        stroke="white" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z"
                        stroke="white" />
                    <path d="M551 270V558M695 414H407" stroke="white" stroke-width="32" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>

                <span class="transition-all duration-300 ease-in-out hover:text-white">
                    Add Multiple Columns
                </span>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-1 w-full col-span-12">
                <!-- <s4-text-area color="ssrGray" v-show="showColTextArea" v-model="columnArea" class="w-full h-40"
                    placeholder="Enter choices separated by new line..."></s4-text-area> -->
                <TE2 placeholder="Enter columns separated by new line..." :modelValue="columnArea"
                    @update:modelValue="(val) => { columnArea = val }" v-show="showColTextArea" height="150px"></TE2>
                <s4-text-area v-show="showColTextArea" v-model="equivalentColumnArea" :filter="true" color="ssrGray"
                    class="w-full h-40" placeholder="Enter equivalent separated by new line..."></s4-text-area>
            </div>
            <div class="col-span-3" v-show="showColTextArea">
                <s4-button :theme="'ssrAddColRow'"
                    class="py-1 px-2 mt-1 mb-2 !bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white"
                    @click="splitForColumn()">Add</s4-button>
                <s4-button :theme="'ssrAddColRow'"
                    class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                    @click="revertCol()">Cancel</s4-button>
            </div>
        </div>
        <div class="flex justify-end col-span-12" v-if="add_edit_form.rows.length > 0">
            <button @click="showDeleteAllRow = true"
                class="inline-flex items-center hover:bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 mr-2 transition-all duration-300 ease-in-out hover:text-red-600" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>

                <span class="transition-all duration-300 ease-in-out hover:text-red-600">
                    Delete All Rows
                </span>
            </button>
        </div>
        <div class="col-span-12">
            <template v-for="(row, index) in add_edit_form.rows" :key="index">
                <div class="grid grid-cols-[50px_1fr_1fr_100px]">
                    <jet-label v-if="index == 0">Index</jet-label>
                    <jet-label v-if="index == 0">Rows</jet-label>
                    <jet-label v-if="index == 0">Equivalent</jet-label>
                    <div v-if="index == 0"></div>
                </div>
                <div class="grid grid-cols-[50px_1fr_1fr_100px] gap-1 items-start">
                    <div class="!pt-2 flex items-center justify-center mr-2"> {{ index + 1 }} </div>


                    <div>


                        <div class="">

                            <!-- <s4-input color="ssrGray" class="w-full mb-1" placeholder="Enter choice" v-model="row.name"
                                :ref="(el) => {
                                        matrixRefs.rowRefs[index] = el;
                                    }
                                    ">
                            </s4-input> -->

                            <TE2 placeholder="Enter Row Name" :modelValue="row.name"
                                @update:modelValue="(val) => { row.name = val }" :ref="(el) => {
                                    matrixRefs.rowRefs[index] = el;
                                }" @mousedown.stop></TE2>

                            <!-- <s4-button color="ssrBlueIcon" class="mb-1 px-3 ml-2" @click="showGallery(index,'edit')">
                                <font-awesome-icon class="fa-w-20 fa-h-20" :icon="faIcon.image" />
                            </s4-button>    -->
                            <!-- <ButtonIconsVue icon="gallery" @click="showGallery(index,'edit')"/>  -->

                            <!-- <s4-button color="ssrRedIcon" class="mb-1 ml-1" @click="add_edit_form.rows.splice(index, 1)">
                                <font-awesome-icon class="fa-w-20 fa-h-20" :icon="faIcon.trash" />
                            </s4-button> -->
                        </div>

                    </div>

                    <div class="">

                        <div class="">

                            <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                                v-model="add_edit_form.rows[index].equivalent">
                            </s4-input>

                        </div>
                        <!-- <label class="items-center cursor-pointer ml-1">
                            <s4-checkbox class="cursor-pointer border-ssr-blue4" v-model:checked="add_edit_form.rows[index].enable_wr
                                " @change="addWC(index)" />
                            <span class="ml-2 text-xs text-gray-500 select-none">Write-in Choice</span>
                        </label> -->
                        <label class="flex items-center cursor-pointer">
                            <s4-checkbox class="cursor-pointer border-ssr-blue4"
                                v-model:checked="add_edit_form.rows[index].optional" />
                            <span class="ml-2 text-xs text-gray-500 select-none">Optional Row</span>
                            <Tooltip :imgSrc="question"
                                label="When enabled, this row is optional even if the question is marked as Required. The Optional Row setting takes precedence over the Required checkbox.">
                            </Tooltip>
                        </label>


                    </div>
                    <div class="grid grid-cols-2 ">
                        <ButtonIconsVue @click="add_edit_form.rows.splice(index, 1)" icon="delete" />
                        <ButtonIconsVue @click="add_edit_form.rows[index].enable_wr = !add_edit_form.rows[index].enable_wr;
                        addWC(index);" :icon="add_edit_form.rows[index].enable_wr ? 'en_write' : 'write'" />
                    </div>

                    <div class="col-span-12">
                        <!-- Match write-in layout/spacing to MultipleChoice.vue -->
                        <div class="col-span-6 grid grid-cols-6 gap-1 mb-2 ml-12" v-if="'wc' in row">
                            <div class="col-span-6 ml-[60px] flex flex-wrap gap-1">
                                <div class="w-[200px]">
                                    <span class="text-xs text-gray-500">Write-in Type</span>
                                    <s4-input-select color="ssrGray" class="w-full" :lists="wr_types"
                                        v-model.number="row.wc.validate.type">
                                    </s4-input-select>
                                </div>
                                <div class="w-[170px]" v-if="
                                    row.wc.validate.type !== 5 &&
                                    row.wc.validate.type !== 6 &&
                                    row.wc.validate.type !== 7
                                ">
                                    <span class="text-xs text-gray-500">
                                        {{
                                            row.wc.validate.type === 2 ||
                                                row.wc.validate.type === 3
                                                ? 'Minimum Number'
                                                : 'Minimum Character'
                                        }}
                                    </span>
                                    <s4-input color="ssrGray" type="number" class="w-full"
                                        v-model.number="row.wc.validate.min"></s4-input>
                                </div>
                                <div class="w-[170px]" v-if="
                                    row.wc.validate.type !== 5 &&
                                    row.wc.validate.type !== 6 &&
                                    row.wc.validate.type !== 7
                                ">
                                    <span class="text-xs text-gray-500">
                                        {{
                                            row.wc.validate.type === 2 ||
                                                row.wc.validate.type === 3
                                                ? 'Maximum Number'
                                                : 'Maximum Character'
                                        }}
                                    </span>
                                    <s4-input color="ssrGray" type="number" class="w-full"
                                        v-model.number="row.wc.validate.max"></s4-input>
                                </div>
                                <div class="w-[180px]" v-if="row.wc.validate.type === 5">
                                    <span class="text-xs text-gray-500">Date Format</span>
                                    <s4-input-select color="ssrGray" class="w-full" :lists="date_formats"
                                        v-model="row.wc.validate.dateFormat">
                                    </s4-input-select>
                                </div>
                                <div class="w-[auto]" v-if="row.wc.validate.type === 6">
                                    <span class="text-xs text-gray-500">Time Format</span>
                                    <s4-input-select color="ssrGray" class="w-full" :lists="time_formats"
                                        v-model="row.wc.validate.timeFormat">
                                    </s4-input-select>
                                </div>
                                <div class="w-[auto]" v-if="row.wc.validate.type === 7">
                                    <span class="text-xs text-gray-500">Date & Time Format</span>
                                    <s4-input-select color="ssrGray" class="w-full" :lists="datetime_formats"
                                        v-model="row.wc.validate.datetimeFormat">
                                    </s4-input-select>
                                </div>
                                <div class="w-[350px]">
                                    <span class="text-xs text-gray-500">Placeholder</span>
                                    <s4-input color="ssrGray" class="w-full" v-model="row.wc.placeholder" />
                                </div>
                                <div class="w-[170px]">
                                    <span class="text-xs text-gray-500">Set Max Width</span>
                                    <s4-input color="ssrGray" placeholder="e.g. 100%" class="w-full"
                                        v-model="row.wc['max-width']" />
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </template>
            <!-- <s4-input v-if="add_edit_form.rows.length > 0" color="orange" class="w-full mb-1" placeholder="Input new choice" v-model="newrow_choice"></s4-input> -->
            <div class="col-span-12">
                <jet-label class="font-bold">Rows</jet-label>
                <div class="flex" v-if="showRowTextArea == false">
                    <!-- <s4-input color="ssrGray" class="w-full mb-1 mr-1" placeholder="Input new row"
                        v-model="newrow_choice"></s4-input> -->
                    <TE2 placeholder="Input new row" :modelValue="newrow_choice"
                        @update:modelValue="(val) => { newrow_choice = val }"></TE2>
                    <!-- <ButtonIconsVue icon="gallery" @click="showGallery(index,'add')"/>  -->
                    <!-- <s4-button @click="showGallery(index,'add')" color="ssrOrangeIcon" class="mb-1 px-3 text-ssr-orange2" style="border:1px solid;"><font-awesome-icon class="fa-w-20 fa-h-20" :icon="faIcon.image" /></s4-button> -->
                </div>
            </div>

            <!-- <s4-button v-if="showRowTextArea == false" :theme="'ssrAddColRow'" class="py-1 px-2 mt-2 mb-10" color="ssrB"
                @click="showRowArea()">
                Multiple Rows
            </s4-button> -->
            <button v-if="showRowTextArea == false" :theme="'ssrAddColRow'" color="ssrB" @click="showRowArea()"
                class="inline-flex w-[175px] mt-1 items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
                <svg width="32" height="32" viewBox="0 0 980 980" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z"
                        fill="white" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z"
                        fill="white" />
                    <path
                        d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z"
                        stroke="white" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z"
                        stroke="white" />
                    <path d="M551 270V558M695 414H407" stroke="white" stroke-width="32" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>

                <span class="transition-all duration-300 ease-in-out">
                    Add Multiple Rows
                </span>
            </button>
            <!-- <s4-text-area color="ssrGray" v-show="showRowTextArea" v-model="rowArea" class="w-full h-40"
                placeholder="Enter choices separated by new line..."></s4-text-area>
                 -->
            <div class="grid grid-cols-2 gap-1 w-full col-span-12">
                <TE2 placeholder="Enter rows separated by new line..." :modelValue="rowArea"
                    @update:modelValue="(val) => { rowArea = val }" v-show="showRowTextArea" height="150px"></TE2>
                <s4-text-area v-show="showRowTextArea" v-model="equivalentRowArea" :filter="true" color="ssrGray"
                    class="w-full h-40" placeholder="Enter equivalent separated by new line..."></s4-text-area>
            </div>

            <div class="col-span3" v-show="showRowTextArea">
                <s4-button :theme="'ssrAddColRow'"
                    class="py-1 px-2 mt-1 mb-2 !bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white"
                    @click="splitForRow()">Add</s4-button>
                <s4-button :theme="'ssrAddColRow'"
                    class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                    @click="revertRow()">Cancel</s4-button>
            </div>
        </div>
    </div>

    <BorderSetup></BorderSetup>

    <div class="mt-4 grid grid-cols-1 lg:grid-cols-12 gap-2 lg:gap-1">
        <div class="col-span-12 lg:col-span-6">
            <div class="col-span-6">
                <label class="flex items-center cursor-pointer w-[110px]">
                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required" />
                    <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                    <Tooltip :imgSrc="question" preset="Required">
                    </Tooltip>
                </label>
                <!-- <div class="col-span-6 pt-2 pl-6">
                    <jet-label class="mt-2">Error Message:</jet-label>
                    <s4-input color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.validate.required == 0"
                        v-model="add_edit_form.setup.validate.errorMsg">
                    </s4-input>
                    <TE2 :modelValue="add_edit_form.setup.validate.errorMsg"
                        @update:modelValue="(val) => { add_edit_form.setup.validate.errorMsg = val }"></TE2>
                </div> -->
            </div>
            <div class="col-span-6 rounded-lg" :class="[
                add_edit_form.setup.random
                    ? 'bg-white mb-3'
                    : '',
            ]">
                <div class="col-span-6">
                    <label class="flex items-center cursor-pointer w-[170px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.random" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Random Rows</span>
                        <Tooltip :imgSrc="question" preset="RandomChoices">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6">
                    <div class="grid grid-cols-1 md:grid-cols-6 mt-2" v-if="
                        add_edit_form.setup.random == true ||
                        add_edit_form.setup.random == 1
                    ">
                        <div class="col-span-6 ml-6">

                            <!-- <select v-model="add_edit_form.setup.randomRows" multiple="multiple"
                                class="w-full border-gray-200 rounded-lg focus:border-ssr-blue3 focus:ring-1 focus:ring-ssr-blue3"
                                color="ssGray">
                                <option v-for="(row, index) in add_edit_form.rows" :key="index" :value="row.value">
                                    {{ row.name }}
                                </option>
                            </select> -->

                            <vue-form-multiselect placeholder="Click to select choices" :options="add_edit_form.rows.map((row, index) => ({
                                value: row.value,
                                label: `${index + 1}. ${row.name}`
                            }))" v-model="add_edit_form.setup.randomRows" />

                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-6 gap-2 col-span-6">
                <div class="col-span-6">
                    <label class="items-center cursor-pointer">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.selectMultiple" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Apply Multiple Selection</span>
                    </label>
                </div>

                <div class="col-span-1 md:col-span-3 flex flex-col gap-1 md:ml-6" v-if="add_edit_form.setup.selectMultiple === 1">
                    <label class="items-center cursor-pointer flex max-w-[160px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.selectAll" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Apply Select All</span>
                        <Tooltip :imgSrc="question" preset="ApplySelectAll">
                        </Tooltip>
                    </label>
                    <div class="ml-6" v-if="add_edit_form.setup.validate.selectAll">
                        <vue-form-multiselect placeholder="Select an option to auto select all items"
                            :options="randomChoices.select_all" v-model="add_edit_form.setup.selectAll" />
                    </div>
                </div>

                <div class="col-span-1 md:col-span-3 flex flex-col gap-1" v-if="add_edit_form.setup.selectMultiple === 1">
                    <label class="items-center cursor-pointer flex max-w-[170px]">
                        <s4-checkbox class="cursor-pointer"
                            v-model:checked="add_edit_form.setup.validate.unSelectAll" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Apply Unselect All</span>
                        <Tooltip :imgSrc="question" preset="ApplyUnselectAll">
                        </Tooltip>
                    </label>
                    <div class="ml-6" v-if="add_edit_form.setup.validate.unSelectAll">
                        <vue-form-multiselect placeholder="Select an option to auto unselect all items"
                            :options="randomChoices.unselect_all" v-model="add_edit_form.setup.unSelectAll" />
                    </div>
                </div>
            </div>

            <div class="col-span-6" v-if="add_edit_form.setup.selectMultiple == 0">
                    <label class="items-center cursor-pointer">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.displayByColumn
                            " :disabled="add_edit_form.setup.displayByRow == 1 ||
                                add_edit_form.setup.selectMultiple == 1
                                " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Single Answer Per Column</span>
                    </label>
                </div>
            <div class="mt-1 col-span-6">

                <div class="grid grid-cols-1 md:grid-cols-6 gap-2 col-span-6 mt-1">
                    <!-- <div class="col-span-3">
                        <jet-label>Minimum Select:</jet-label>
                        <s4-input type="number" color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.validate.required == 0 ||
                            add_edit_form.setup.selectMultiple == 0
                            " v-model.number="add_edit_form.setup.validate.min">
                        </s4-input>
                    </div>
                    <div class="col-span-3">
                        <jet-label>Maximum Select:</jet-label>
                        <s4-input type="number" color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.validate.required == 0 ||
                            add_edit_form.setup.selectMultiple == 0
                            " v-model.number="add_edit_form.setup.validate.max">
                        </s4-input>
                    </div> -->

                    <!-- <div class="col-span-3">
                        <div class="flex items-center">
                            <jet-label>Layout Width</jet-label>
                            <Tooltip :imgSrc="question"
                                label="Sets the maximum width of the content box. Adjust this setting to control how wide the content can appear on the page.">
                            </Tooltip>
                        </div>
                        <S4InputSlider color="ssrGray" :placeholder="'e.g. 100'" :units="['%', 'vmin', 'vw', 'auto']"
                            v-model.number="boxStyleValue" v-model:unitValue="boxStyleUnit" :allowAuto="true" min="0">
                        </S4InputSlider>
                    </div> -->

                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-6">
       
            <div class="col-span-6">
                <label class="cursor-pointer flex items-center gap-0.5 w-[170px]">
                    <s4-checkbox class="cursor-pointer"
                        v-model:checked="add_edit_form.setup.boxStyle['max-width_enabler']" />
                    <span class="ml-2 text-sm text-gray-600 select-none">Edit Layout Width</span>
                    <Tooltip :imgSrc="question"
                        label="Sets the maximum width of the content box. Adjust this setting to control how wide the content can appear on the page.">
                    </Tooltip>
                </label>
            </div>
            <div class="col-span-6 w-full grid grid-cols-1 md:grid-cols-3 gap-2 pl-0 md:pl-6">
                <div class="" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
                    <jet-label>Desktop</jet-label>
                    <!-- <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="e.g. 100%"
                                v-model="add_edit_form.setup.boxStyle['max-width']"
                            >
                            </s4-input> -->
                    <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                        :units="['%', 'vmin', 'vw', 'auto']"
                        :modelValue="add_edit_form.setup.boxStyle['max-width'] ?? '100%'" :allowAuto="true"
                        @update:modelValue="(val) => { add_edit_form.setup.boxStyle['max-width'] = val; }">
                    </S4InputSlider2>
                </div>

                <div class="" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
                    <jet-label>Tablet</jet-label>
                    <!-- <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="e.g. 100%"
                                v-model="add_edit_form.setup.boxStyle['max-width']"
                            >
                            </s4-input> -->

                    <!-- <S4InputSlider
                                color="ssrGray"
                                class="w-full mt-1"
                                :placeholder="'e.g. 100'"
                                :units="['%', 'vmin', 'vw', 'auto']"
                                v-model.number="boxStyleValue_tab"
                                v-model:unitValue="boxStyleUnit"
                                :allowAuto="true"
                                min="0"
                                v-model="add_edit_form.setup.boxStyle"
                            >
                            </S4InputSlider> -->

                    <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                        :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width-tab'] ??
                            '100%'
                            " :allowAuto="true" @update:modelValue="
                                        (val) => {
                                            add_edit_form.setup.boxStyle['max-width-tab'] =
                                                val;
                                        }
                                    ">
                    </S4InputSlider2>
                </div>
                <div class="" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
                    <jet-label>Mobile</jet-label>
                    <!-- <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="e.g. 100%"
                                v-model="add_edit_form.setup.boxStyle['max-width']"
                            >
                            </s4-input> -->

                    <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                        :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width-mobile'] ??
                            '100%'
                            " :allowAuto="true" @update:modelValue="
                                        (val) => {
                                            add_edit_form.setup.boxStyle[
                                                'max-width-mobile'
                                            ] = val;
                                        }
                                    ">
                    </S4InputSlider2>
                </div>
            </div>



        </div>
    </div>
    <!-- delete all modal for column -->
    <teleport to="body">
        <WarningModal :show="showDeleteAllColumn" @close="showDeleteAllColumn = false">
            <template #header>
                Confirm Deletion
            </template>
            <template #content>
                Are you sure you want to delete <span class="font-semibold">all columns</span>?
                This action is irreversible.
            </template>
            <template #footer>
                <button @click="deleteAllColumns"
                    class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                    type="button">
                    Delete
                </button>
                <button @click="showDeleteAllColumn = false"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
                    type="button">
                    Cancel
                </button>
            </template>
        </WarningModal>
    </teleport>
    <!-- delete all modal for row -->
    <teleport to="body">
        <WarningModal :show="showDeleteAllRow" @close="showDeleteAllRow = false">
            <template #header>
                Confirm Deletion
            </template>
            <template #content>
                Are you sure you want to delete <span class="font-semibold">all rows</span>?
                This action is irreversible.
            </template>
            <template #footer>
                <button @click="deleteAllRows"
                    class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                    type="button">
                    Delete
                </button>
                <button @click="showDeleteAllRow = false"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
                    type="button">
                    Cancel
                </button>
            </template>
        </WarningModal>
    </teleport>
    <div class="grid grid-cols-6 mt-3 gap-1">
        <s4-gallery-modal :modal="gallery" @close="closeGallery()" @selectGallery="selectGallery" />
    </div>
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
