<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4Input from "@/S4/Input.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";
import Tooltip from "@/S4/Tooltip.vue";
import question from "/public/images/question.svg";

import { inject, ref, computed, onBeforeMount, watch } from "vue";
import helpers from "@/helpers.js";

const add_edit_form = inject("add_edit_form");

const boxStyleValue = ref("");
const boxStyleUnit = ref("%");
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

watch(maxWidthComputed, (newValue) => {
    add_edit_form.setup.boxStyle["max-width"] = newValue;
});

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
});

// style height % width
const styleWidthValue = ref(parseFloat(add_edit_form.setup.style.width));
const styleWidthUnit = ref(
    add_edit_form.setup.style.width.replace(/[0-9.]/g, "") || "vmin"
);

const styleHeightValue = ref(parseFloat(add_edit_form.setup.style.height));
const styleHeightUnit = ref(
    add_edit_form.setup.style.height.replace(/[0-9.]/g, "") || "vmin"
);

const styleWidth = computed({
    get() {
        return styleWidthUnit.value === "auto"
            ? "auto"
            : `${styleWidthValue.value}${styleWidthUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            styleWidthValue.value = null; // Clear numeric value
            styleWidthUnit.value = "auto";
        } else {
            const match = newValue.match(/^(\d+)([a-z%]+)$/);
            if (match) {
                styleWidthValue.value = parseFloat(match[1]);
                styleWidthUnit.value = match[2];
            }
        }
    },
});

const styleHeight = computed({
    get() {
        return styleHeightUnit.value === "auto"
            ? "auto"
            : `${styleHeightValue.value}${styleHeightUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            styleHeightValue.value = null; // Clear numeric value
            styleHeightUnit.value = "auto";
        } else {
            const match = newValue.match(/^(\d+)([a-z%]+)$/);
            if (match) {
                styleHeightValue.value = parseFloat(match[1]);
                styleHeightUnit.value = match[2];
            }
        }
    },
});

watch(styleWidth, (newValue) => {
    add_edit_form.setup.style.width = newValue;
});

watch(styleHeight, (newValue) => {
    add_edit_form.setup.style.height = newValue;
});
</script>
<template>
    <div class="pt-5">
        <BorderSetup></BorderSetup>

        <!-- Options & Layout: stack as list view below xl, split into two columns only on xl+ -->
        <div class="mt-4 grid grid-cols-1 xl:grid-cols-12 gap-1">
            <div class="col-span-1 xl:col-span-6">
                <div class="col-span-6 gap-1">
                    <div class="col-span-6">
                        <label class="flex items-center cursor-pointer w-[110px]">
                            <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required" />
                            <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                            <Tooltip :imgSrc="question" preset="Required">
                            </Tooltip>
                        </label>
                    </div>
                    <!-- Detect Gibberish Text -->
                    <div class="col-span-6"
                        :set="helpers().initProperty(add_edit_form.setup, 'detectGibberish', 0)">
                        <label class="items-center cursor-pointer flex max-w-[203px]">
                            <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.detectGibberish" />
                            <span class="ml-2 text-sm text-gray-600 select-none">Detect Gibberish Text</span>
                            <Tooltip :imgSrc="question" preset="Gibberish">
                            </Tooltip>
                        </label>
                    </div>
                </div>
                <div class="col-span-3">
                    <div class="grid grid-cols-6 gap-1 mt-4">
                        <div class="col-span-3">
                            <jet-label>Minimum Character</jet-label>
                            <s4-input type="number" color="ssrGray" class="w-full mb-1" v-model.number="add_edit_form.setup.validate.min">
                            </s4-input>
                        </div>
                        <div class="col-span-3">
                            <jet-label>Maximum Character</jet-label>
                            <s4-input type="number" color="ssrGray" class="w-full mb-1" v-model.number="add_edit_form.setup.validate.max">
                            </s4-input>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-1 xl:col-span-6">
                <div class="grid grid-cols-6 gap-1">
                    <div class="grid grid-cols-6 gap-1 col-span-12">
                        <!-- Apply Textbox Dimension -->
                        <div class="col-span-6"
                            :set="helpers().initProperty(add_edit_form.setup.textBox, 'textbox_dimension_switch', 0)">
                            <label class="items-center cursor-pointer flex max-w-[203px]">
                                <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.textBox['textbox_dimension_switch']
                                    " />
                                <span class="ml-2 text-sm text-gray-600 select-none">Edit Textbox Dimension</span>
                                <Tooltip :imgSrc="question" preset="EditTextboxDimension">
                                </Tooltip>
                            </label>
                        </div>
                        <div class="col-span-6 w-full grid grid-cols-3 gap-1 pl-6">
                            <div class="" v-if="add_edit_form.setup.textBox['textbox_dimension_switch'] == 1">
                                <jet-label>Desktop</jet-label>

                                <S4InputSlider2
                                    :set="helpers().initProperty(add_edit_form.setup.textBox, 'textbox_dimension', '20vmin')"
                                    color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.textBox['textbox_dimension']
                                        " :allowAuto="true" @update:modelValue="
                                            (val) => {
                                                add_edit_form.setup.textBox['textbox_dimension'] =
                                                    val;
                                            }
                                        ">
                                </S4InputSlider2>
                            </div>

                            <div class="" v-if="add_edit_form.setup.textBox['textbox_dimension_switch'] == 1">
                                <jet-label>Tablet</jet-label>

                                <S4InputSlider2
                                    :set="helpers().initProperty(add_edit_form.setup.textBox, 'textbox_dimension_tab', '20vmin')"
                                    color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.textBox['textbox_dimension_tab']
                                        " :allowAuto="true" @update:modelValue="
                                            (val) => {
                                                add_edit_form.setup.textBox['textbox_dimension_tab'] =
                                                    val;
                                            }
                                        ">
                                </S4InputSlider2>
                            </div>
                            <div class="" v-if="add_edit_form.setup.textBox['textbox_dimension_switch'] == 1">
                                <jet-label>Mobile</jet-label>

                                <S4InputSlider2
                                    :set="helpers().initProperty(add_edit_form.setup.textBox, 'textbox_dimension_mobile', '20vmin')"
                                    color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.textBox['textbox_dimension_mobile']
                                        " :allowAuto="true" @update:modelValue="
                                            (val) => {
                                                add_edit_form.setup.textBox[
                                                    'textbox_dimension_mobile'
                                                ] = val;
                                            }
                                        ">
                                </S4InputSlider2>
                            </div>

                        </div>

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

                            <div class="col-span-6 w-full grid grid-cols-3 gap-1 pl-6">
                                <div class="" v-if="
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

                                <div class="" v-if="
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
                                <div class="" v-if="
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

        <!-- <div class="col-span-3">
                        <jet-label>Box style size:</jet-label>
                        <s4-input color="ssrGray" class="w-full mb-1" placeholder="e.g. md"
                            v-model="add_edit_form.setup.boxStyle.size">
                        </s4-input>
                    </div> -->
        <!-- <div class="col-span-3">
                        <jet-label>Layout Width</jet-label>
                        
                        <S4InputSlider color="ssrGray" :placeholder="'e.g. 100'" :units="['%', 'vmin', 'vw', 'auto']"
                            v-model.number="boxStyleValue" v-model:unitValue="boxStyleUnit" :allowAuto="true" min="0">
                        </S4InputSlider>
                    </div> -->
    </div>

</template>
