<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4Input from "@/S4/Input.vue";
import S4InputSelect from "@/S4/InputSelect.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import { inject, ref, watch, computed, onBeforeMount } from "vue";
import helpers from "@/helpers.js";
import TE2 from "@/S4/TE2.vue";

const add_edit_form = inject("add_edit_form");

const types = [
    {
        value: 1,
        text: "Open Text",
    },
    {
        value: 8,
        text: "Letters Only (A-Z, a-z)",
    },
    {
        value: 9,
        text: "Alphanumeric",
    },
    {
        value: 2,
        text: "Whole Number",
    },
    {
        value: 3,
        text: "Decimal Number",
    },
    {
        value: 4,
        text: "Email Address",
    },
    {
        value: 5,
        text: "Date",
    },
    {
        value: 6,
        text: "Time",
    },
    {
        value: 7,
        text: "Date & Time",
    },
    {
        value: 10,
        text: "Pricing",
    }
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
    { value: "12-hour", text: "12-Hour (2:30 PM)" },
    { value: "24-hour", text: "24-Hour (14:30)" },
    { value: "12-hour-seconds", text: "12-Hour with Seconds (2:30:45 PM)" },
    { value: "24-hour-seconds", text: "24-Hour with Seconds (14:30:45)" },
];

const datetime_formats = [
    { value: "dd-mmm-yyyy hh:mm", text: "dd-mmm-yyyy hh:mm" },
    { value: "dd-mmm-yyyy hh:mm:ss.s", text: "dd-mmm-yyyy hh:mm:ss.s" },
    { value: "dd-mmm-yyyy hh:mm:ss", text: "dd-mmm-yyyy hh:mm:ss" },
    { value: "yyyy-mm-dd hh:mm", text: "yyyy-mm-dd hh:mm" },
    { value: "yyyy-mm-dd hh:mm:ss", text: "yyyy-mm-dd hh:mm:ss" },
    { value: "yyyy-mm-dd hh:mm:ss.s", text: "yyyy-mm-dd hh:mm:ss.s" },
];

const currency_formats = [
    { value: "USD", text: "USD - US Dollar" },
    { value: "EUR", text: "EUR - Euro" },
    { value: "INR", text: "INR - Indian Rupee" },
    { value: "GBP", text: "GBP - British Pound Sterling" },
    { value: "CAD", text: "CAD - Canadian Dollar" },
    { value: "JPY", text: "JPY - Japanese Yen" },
    { value: "PHP", text: "PHP - Philippine Peso" },
    { value: "CNY", text: "CNY - Chinese Yuan" },
];
const boxStyleValue = ref("");
const boxStyleUnit = ref("%");
const isEmail = ref(false);
const filterDomain = ref(false);

//const for box style max-width
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
    },
});

const StyleValue = ref("");
const StyleUnit = ref("%");
const styleMaxWidthComputed = computed({
    get() {
        return StyleUnit.value === "auto"
            ? "auto"
            : `${StyleValue.value}${StyleUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            StyleValue.value = null; // Clear the numeric value
            StyleUnit.value = "auto";
        } else {
            const match = newValue.match(/^(\d+)([a-z%]+)$/);
            if (match) {
                StyleValue.value = parseFloat(match[1]);
                StyleUnit.value = match[2];
            }
        }
    },
});

watch(maxWidthComputed, (newValue) => {
    add_edit_form.setup.boxStyle["max-width"] = newValue;
});

watch(styleMaxWidthComputed, (newValue) => {
    add_edit_form.setup.style["max-width"] = newValue;
});

watch(filterDomain, (change) => {
    // console.log("LOYLOY X JAM", change)
    if (change == false) {
        add_edit_form.setup.validate.filter_domain = null;
    }
});

// Reset min/max when changing validation type (e.g. from Alphanumeric to Whole Number)
watch(
    () => add_edit_form?.setup?.validate?.type,
    (newVal, oldVal) => {
        // Ignore initial setup where oldVal may be undefined/null
        if (oldVal === undefined || oldVal === null) {
            return;
        }

        if (
            add_edit_form &&
            add_edit_form.setup &&
            add_edit_form.setup.validate
        ) {
            add_edit_form.setup.validate.min = null;
            add_edit_form.setup.validate.max = null;
        }
    }
);

onBeforeMount(() => {
    if (!("boxStyle" in add_edit_form.setup)) {
        add_edit_form.setup["boxStyle"] = { "max-width": "100%" };
    }

    const maxWidth = add_edit_form.setup.boxStyle["max-width"] || "100%";
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

    if (!("style" in add_edit_form.setup)) {
        add_edit_form.setup["style"] = { "max-width": "100%" };
    }

    const StylemaxWidth = add_edit_form.setup.style["max-width"] || "100%";
    if (StylemaxWidth === "auto") {
        StyleValue.value = null;
        StyleUnit.value = "auto";
    } else {
        const match = StylemaxWidth.match(/^(\d+)([a-z%]+)$/);
        if (match) {
            StyleValue.value = parseFloat(match[1]);
            StyleUnit.value = match[2];
        }
    }

    if (add_edit_form.setup.validate.filter_domain != null) {
        filterDomain.value = true;
    }

    // Initialize format properties if they don't exist
    if (!("dateFormat" in add_edit_form.setup.validate)) {
        add_edit_form.setup.validate.dateFormat = "mm-dd-yyyy";
    }
    if (!("timeFormat" in add_edit_form.setup.validate)) {
        add_edit_form.setup.validate.timeFormat = "12-hour";
    }
    if (!("datetimeFormat" in add_edit_form.setup.validate)) {
        add_edit_form.setup.validate.datetimeFormat = "dd-mmm-yyyy hh:mm";
    }
    if (!("currencyFormat" in add_edit_form.setup.validate)) {
        add_edit_form.setup.validate.currencyFormat = "USD";
    }
    // console.log("DOMAIdN", add_edit_form)
});
</script>

<template>
    <BorderSetup></BorderSetup>

    <div class="mt-4 grid grid-cols-12 max-xl:!grid-cols-6 gap-1"
        :set="helpers().initProperty(add_edit_form.setup, 'textBox')">
        <div class="col-span-6">
            <div class="grid grid-cols-6 gap-1 col-span-6 pr-8">
                <div class="col-span-6">
                    <label class="flex items-center cursor-pointer">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                        <Tooltip :imgSrc="question" preset="Required"></Tooltip>
                    </label>
                </div>
                <div class="col-span-6" v-if="Number(add_edit_form.setup.validate.type) === 1"
                    :set="helpers().initProperty(add_edit_form.setup, 'detectGibberish', 0)">
                    <label class="items-center cursor-pointer flex max-w-[203px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.detectGibberish" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Detect Gibberish Text</span>
                        <Tooltip :imgSrc="question" preset="Gibberish">
                        </Tooltip>
                    </label>
                </div>
                <!-- <div class="col-span-6 pl-5" v-if="add_edit_form.setup.validate.required">
                    <TE2 placeholder="" :disabled="add_edit_form.setup.validate.required == 0"
                        :modelValue="add_edit_form.setup.validate.errorMsg" @update:modelValue="
                            (val) => {
                                add_edit_form.setup.validate.errorMsg = val;
                            }
                        "></TE2>
                </div> -->

                <div class="col-span-3 pl-5">
                    <!-- <jet-label>Before Textbox</jet-label> -->
                    <span class="text-sm text-gray-600 select-none">Before Textbox</span>
                    <!-- <s4-input color="ssrGray" class="w-[100%] mb-1" placeholder="Enter text"
                        v-model="add_edit_form.setup.prependString">
                    </s4-input> -->
                    <TE2 color="ssrGray" class="w-[100%] mb-1" placeholder="Enter text"
                        :modelValue="add_edit_form.setup.prependString" @update:modelValue="
                            (val) => {
                                add_edit_form.setup.prependString = val;
                            }
                        " @mousedown.stop></TE2>
                </div>
                <div class="col-span-3">
                    <!-- <jet-label>After Textbox</jet-label> -->
                    <span class="text-sm text-gray-600 select-none">After Textbox</span>
                    <!-- <s4-input color="ssrGray" class=" mb-1" placeholder="Enter text"
                        v-model="add_edit_form.setup.appendString">
                    </s4-input> -->
                    <TE2 color="ssrGray" class="w-[100%] mb-1" placeholder="Enter text"
                        :modelValue="add_edit_form.setup.appendString" @update:modelValue="
                            (val) => {
                                add_edit_form.setup.appendString = val;
                            }
                        " @mousedown.stop></TE2>
                </div>

                <!-- Select Type and related validation fields moved below Before/After Textbox -->
                <div class="col-span-6 mb-1 mt-2">
                    <label class="items-center cursor-pointer">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.type" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Select Type</span>
                    </label>
                    <s4-input-select class="mt-1 pl-5" v-if="add_edit_form.setup.validate.type"
                        :lists="types" v-model="add_edit_form.setup.validate.type">
                    </s4-input-select>
                </div>

                <div class="col-span-6">
                    <label v-if="add_edit_form.setup.validate.type == 4"
                        class="flex items-center cursor-pointer space-x-2">
                        <s4-checkbox class="cursor-pointer" v-model:checked="filterDomain" />
                        <span class="text-sm text-gray-600 select-none">Email Providers</span>
                        <Tooltip :imgSrc="question" preset="EmailProviders">
                        </Tooltip>
                    </label>
                </div>

                <div class="col-span-3 pl-6" v-if="
                        add_edit_form.setup.validate.type == 4 &&
                        filterDomain == true
                    ">
                    <span class="text-sm text-gray-600 select-none">Accepted Domain</span>
                    <s4-input color="ssrGray" class="mt-1" placeholder="@domain.com" v-model="add_edit_form.setup.validate
                            .filter_domain
                        ">
                    </s4-input>
                </div>

                <div class="col-span-3 ml-5" v-if="
                        add_edit_form.setup.validate.type == 1 ||
                        add_edit_form.setup.validate.type == 8 ||
                        add_edit_form.setup.validate.type == 9
                    ">
                    <jet-label>Minimum Character</jet-label>
                    <s4-input type="number" color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.validate.required ==
                            0
                        " v-model="add_edit_form.setup.validate.min">
                    </s4-input>
                </div>

                <div class="col-span-3" v-if="
                        add_edit_form.setup.validate.type == 1 ||
                        add_edit_form.setup.validate.type == 8 ||
                        add_edit_form.setup.validate.type == 9
                    ">
                    <jet-label>Maximum Character</jet-label>
                    <s4-input type="number" color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.validate.required ==
                            0
                        " v-model="add_edit_form.setup.validate.max">
                    </s4-input>
                </div>

                <!-- Pricing specific controls: show Currency & Minor Unit above Min/Max when type is Pricing -->
                <div class="col-span-3 ml-5" v-if="add_edit_form.setup.validate.type == 10">
                    <jet-label>Currency</jet-label>
                    <s4-input-select color="ssrGray" class="w-full mb-1" :lists="currency_formats"
                        v-model="add_edit_form.setup.validate.currencyFormat">
                    </s4-input-select>
                </div>

                <div class="col-span-3 ml-5" v-if="add_edit_form.setup.validate.type == 10"
                    :set="helpers().initProperty(add_edit_form.setup, 'minorUnitSymbol', 0)">
                    <label class="items-center cursor-pointer flex max-w-[203px]">
                        <s4-checkbox class="cursor-pointer"
                            v-model:checked="add_edit_form.setup.minorUnitSymbol"
                            :true-value="1"
                            :false-value="0"
                        />
                        <span class="ml-2 text-sm text-gray-600 select-none">Minor Unit Symbol</span>
                        <Tooltip :imgSrc="question" preset="EditTextboxDimension">
                        </Tooltip>
                    </label>
                </div>

                <div class="col-span-3 ml-5" v-if="
                        add_edit_form.setup.validate.type == 2 ||
                        add_edit_form.setup.validate.type == 3 ||
                        add_edit_form.setup.validate.type == 10
                    ">
                    <jet-label>Minimum Number</jet-label>
                    <s4-input type="number" color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.validate.required ==
                            0
                        " v-model="add_edit_form.setup.validate.min">
                    </s4-input>
                </div>

                <div class="col-span-3" v-if="
                        add_edit_form.setup.validate.type == 2 ||
                        add_edit_form.setup.validate.type == 3 ||
                        add_edit_form.setup.validate.type == 10
                    ">
                    <jet-label>Maximum Number</jet-label>
                    <s4-input type="number" color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.validate.required ==
                            0
                        " v-model="add_edit_form.setup.validate.max">
                    </s4-input>
                </div>

                <div class="col-span-3 ml-5" v-if="add_edit_form.setup.validate.type == 5">
                    <jet-label>Date Format</jet-label>
                    <s4-input-select color="ssrGray" class="w-full mb-1" :lists="date_formats"
                        v-model="add_edit_form.setup.validate.dateFormat">
                    </s4-input-select>
                </div>

                <div class="col-span-3 ml-5" v-if="add_edit_form.setup.validate.type == 6">
                    <jet-label>Time Format</jet-label>
                    <s4-input-select color="ssrGray" class="w-full mb-1" :lists="time_formats"
                        v-model="add_edit_form.setup.validate.timeFormat">
                    </s4-input-select>
                </div>

                <div class="col-span-3 ml-5" v-if="add_edit_form.setup.validate.type == 7">
                    <jet-label>Date & Time Format</jet-label>
                    <s4-input-select color="ssrGray" class="w-full mb-1" :lists="datetime_formats"
                        v-model="add_edit_form.setup.validate.datetimeFormat">
                    </s4-input-select>
                </div>
            </div>
        </div>

        <div class="col-span-6">
            <div class="grid grid-cols-6 gap-1">
                <div class="grid grid-cols-6 gap-1 col-span-12">
                    <div class="col-span-6">
                        <div class="grid grid-cols-6 gap-1">
                            <!-- Apply Textbox Dimension -->
                            <div class="col-span-6" :set="helpers().initProperty(
                                add_edit_form.setup.textBox,
                                'textbox_dimension_switch',
                                0
                            )
                                ">
                                <label class="items-center cursor-pointer flex max-w-[205px]">
                                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.textBox[
                                        'textbox_dimension_switch'
                                    ]
                                        " />
                                    <span class="ml-2 text-sm text-gray-600 select-none">Edit Textbox Dimension</span>
                                    <Tooltip :imgSrc="question" preset="EditTextboxDimension">
                                    </Tooltip>
                                </label>
                            </div>
                            <div class="col-span-2 pl-6" v-if="
                                add_edit_form.setup.textBox[
                                'textbox_dimension_switch'
                                ] == 1
                            ">
                                <jet-label>Desktop</jet-label>
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
                            :placeholder="'e.g. 50'"
                            :units="['%', 'vmin', 'vw', 'auto']"
                            v-model.number="itemMaxWidthValue_desktop"
                            v-model:unitValue="itemMaxWidthUnit"
                            :allowAuto="true"
                            min="0"
                            v-model="add_edit_form.setup.itemMaxWidth"
                        >
                        </S4InputSlider> -->
                                <!-- <div>{{ add_edit_form.setup }}</div> -->
                                <S4InputSlider2 :set="helpers().initProperty(
                                    add_edit_form.setup.textBox,
                                    'textbox_dimension',
                                    '20vmin'
                                )
                                    " color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.textBox[
                                        'textbox_dimension'
                                    ]
                                        " :allowAuto="true" @update:modelValue="
                                            (val) => {
                                                add_edit_form.setup.textBox[
                                                    'textbox_dimension'
                                                ] = val;
                                            }
                                        ">
                                </S4InputSlider2>
                            </div>

                            <div class="col-span-2" v-if="
                                add_edit_form.setup.textBox[
                                'textbox_dimension_switch'
                                ] == 1
                            ">
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
                            :placeholder="'e.g. 50'"
                            :units="['%', 'vmin', 'vw', 'auto']"
                            v-model.number="itemMaxWidthValue_tab"
                            v-model:unitValue="itemMaxWidthUnit"
                            :allowAuto="true"
                            min="0"
                            v-model="add_edit_form.setup.itemMaxWidth"
                        >
                        </S4InputSlider> -->

                                <S4InputSlider2 :set="helpers().initProperty(
                                    add_edit_form.setup.textBox,
                                    'textbox_dimension_tab',
                                    '20vmin'
                                )
                                    " color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.textBox[
                                        'textbox_dimension_tab'
                                    ]
                                        " :allowAuto="true" @update:modelValue="
                                            (val) => {
                                                add_edit_form.setup.textBox[
                                                    'textbox_dimension_tab'
                                                ] = val;
                                            }
                                        ">
                                </S4InputSlider2>
                            </div>
                            <div class="col-span-2" v-if="
                                add_edit_form.setup.textBox[
                                'textbox_dimension_switch'
                                ] == 1
                            ">
                                <jet-label>Mobile</jet-label>
                                <!-- <s4-input
                            color="ssrGray"
                            class="w-full mb-1"
                            placeholder="e.g. 100%"
                            v-model="add_edit_form.setup.boxStyle['max-width']"
                        >
                        </s4-input> -->
                                <S4InputSlider2 :set="helpers().initProperty(
                                    add_edit_form.setup.textBox,
                                    'textbox_dimension_mobile',
                                    '20vmin'
                                )
                                    " color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.textBox[
                                        'textbox_dimension_mobile'
                                    ]
                                        " :allowAuto="true" @update:modelValue="
                                            (val) => {
                                                add_edit_form.setup.textBox[
                                                    'textbox_dimension_mobile'
                                                ] = val;
                                            }
                                        ">
                                </S4InputSlider2>
                            </div>

                            <!-- <div class="col-span-2">
                        <jet-label>Box style size:</jet-label>
                        <s4-input color="ssrGray" class="w-full mb-1" placeholder="e.g. md"
                            v-model="add_edit_form.setup.boxStyle.size">
                        </s4-input>
                    </div> -->
                            <!-- <div class="col-span-3">
                    <jet-label>Edit Layout Width</jet-label>
                    
                    <S4InputSlider
                        color="ssrGray"
                        :placeholder="'e.g. 100'"
                        :units="['%', 'vmin', 'vw', 'auto']"
                        v-model.number="boxStyleValue"
                        v-model:unitValue="boxStyleUnit"
                        :allowAuto="true"
                        min="0"
                    >
                    </S4InputSlider>
                </div> -->
                            <div class="col-span-6">
                                <label class="cursor-pointer flex items-center gap-0.5 w-[170px]">
                                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.boxStyle[
                                        'max-width_enabler'
                                    ]
                                        " />
                                    <span class="ml-2 text-sm text-gray-600 select-none">Edit Layout Width</span>
                                    <Tooltip :imgSrc="question" preset="EditLayoutWidth">
                                    </Tooltip>
                                </label>
                            </div>
                            <div class="col-span-2 pl-6" v-if="
                                add_edit_form.setup.boxStyle[
                                'max-width_enabler'
                                ]
                            ">
                                <jet-label>Desktop</jet-label>
                                <!-- <s4-input
                            color="ssrGray"
                            class="w-full mb-1"
                            placeholder="e.g. 100%"
                            v-model="add_edit_form.setup.boxStyle['max-width']"
                        >
                        </s4-input> -->
                                <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle[
                                        'max-width'
                                    ]
                                        " :allowAuto="true" @update:modelValue="
                                            (val) => {
                                                add_edit_form.setup.boxStyle[
                                                    'max-width'
                                                ] = val;
                                            }
                                        ">
                                </S4InputSlider2>
                            </div>

                            <div class="col-span-2" v-if="
                                add_edit_form.setup.boxStyle[
                                'max-width_enabler'
                                ]
                            ">
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
                                    :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle[
                                        'max-width-tab'
                                    ] ?? '100%'
                                        " :allowAuto="true" @update:modelValue="
                                            (val) => {
                                                add_edit_form.setup.boxStyle[
                                                    'max-width-tab'
                                                ] = val;
                                            }
                                        ">
                                </S4InputSlider2>
                            </div>
                            <div class="col-span-2" v-if="
                                add_edit_form.setup.boxStyle[
                                'max-width_enabler'
                                ]
                            ">
                                <jet-label>Mobile</jet-label>
                                <!-- <s4-input
                            color="ssrGray"
                            class="w-full mb-1"
                            placeholder="e.g. 100%"
                            v-model="add_edit_form.setup.boxStyle['max-width']"
                        >
                        </s4-input> -->

                                <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle[
                                        'max-width-mobile'
                                    ] ?? '100%'
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
            </div>
        </div>
    </div>
</template>