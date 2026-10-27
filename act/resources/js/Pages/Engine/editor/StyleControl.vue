<template>
    <div class="style-control-wrapper">
        <label
            :for="variable"
            class="block text-xs font-medium text-gray-700 mb-1 capitalize"
        >
            {{ label }}
        </label>
        <div class="grid grid-cols-2">
            <div v-if="variable === '--font-family'" class="col-span-2">
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="w-full py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm dark:text-gray-700 text-base"
                >
                    <!-- <option :value="''">Default (Roboto)</option> -->
                    <option
                        v-for="font in engineFontFamilies"
                        :key="font.value"
                        :value="font.value"
                    >
                        {{ font.label }}
                    </option>
                </select>
            </div>

            <!-- Targets: Font Weight dropdown -->
            <div
                class="col-span-2"
                v-else-if="
                    variable === '--target-font-weight' ||
                    variable === '--prime-font-weight'
                "
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm dark:text-gray-700 text-base w-full"
                >
                    <option :value="''">Default</option>
                    <option
                        v-for="weight in engineFontWeights"
                        :key="weight.value"
                        :value="weight.value"
                    >
                        {{ weight.label }}
                    </option>
                </select>
            </div>

            <!-- Footer / Bottom Text: Font Weight dropdown -->
            <div
                class="col-span-2"
                v-else-if="variable === '--bottom-text-font-weight'"
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="w-full py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm"
                >
                    <option :value="''">Default</option>
                    <option
                        v-for="weight in engineFontWeights"
                        :key="weight.value"
                        :value="weight.value"
                    >
                        {{ weight.label }}
                    </option>
                </select>
            </div>

            <!-- Prime: Border Style dropdown -->
            <div
                class="col-span-2"
                v-else-if="variable === '--prime-border-style'"
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-full"
                >
                    <option :selected="modelValue === ''">Default</option>
                    <option value="solid">solid</option>
                    <option value="dashed">dashed</option>
                    <option value="dotted">dotted</option>
                    <option value="double">double</option>
                    <option value="groove">groove</option>
                    <option value="ridge">ridge</option>
                    <option value="inset">inset</option>
                    <option value="outset">outset</option>
                    <option value="none">none</option>
                </select>
            </div>

            <!-- Logo Display dropdown -->
            <div class="col-span-2" v-else-if="variable === '--logo-display'">
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-full"
                >
                    <option value="block">block</option>
                    <option value="none">none</option>
                </select>
            </div>

            <div
                class="col-span-2"
                v-else-if="variable === '--target-border-style'"
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-full"
                >
                    <option :selected="modelValue === ''">Default</option>
                    <option value="solid">solid</option>
                    <option value="dashed">dashed</option>
                    <option value="dotted">dotted</option>
                    <option value="double">double</option>
                    <option value="groove">groove</option>
                    <option value="ridge">ridge</option>
                    <option value="inset">inset</option>
                    <option value="outset">outset</option>
                    <option value="none">none</option>
                </select>
            </div>

            <div
                v-else-if="variable === '--bottom-text-font-style'"
                class="col-span-2"
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="w-full py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm"
                >
                    <option :selected="modelValue === ''">Default</option>
                    <option value="normal">Normal</option>
                    <option value="italic">Italic</option>
                    <option value="oblique">Oblique</option>
                    <option value="initial">Initial</option>
                    <option value="inherit">Inherit</option>
                </select>
            </div>

            <div
                v-else-if="
                    variable === '--prime-text-wrap-style' ||
                    variable === '--target-text-wrap-style'
                "
                class="col-span-2"
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="w-full py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm"
                >
                    <option value="auto">auto</option>
                    <option value="balance">balance</option>
                    <option value="pretty">pretty</option>
                    <option value="stable">stable</option>
                </select>
            </div>

            <!-- <div
                v-else-if="variable === '--engine-background-repeat'"
                class="col-span-2"
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="w-full py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm"
                >
                    <option value="no-repeat">no-repeat</option>
                    <option value="repeat">repeat</option>
                    <option value="repeat-x">repeat-x</option>
                    <option value="repeat-y">repeat-y</option>
                    <option value="space">space</option>
                    <option value="round">round</option>
                </select>
            </div>

            <div
                v-else-if="variable === '--engine-background-position'"
                class="col-span-2"
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="w-full py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm"
                >
                    <option value="left">left</option>
                    <option value="center">center</option>
                    <option value="right">right</option>
                    <option value="top">top</option>
                    <option value="bottom">bottom</option>
                    <option value="top left">top left</option>
                    <option value="top right">top right</option>
                    <option value="bottom left">bottom left</option>
                    <option value="bottom right">bottom right</option>
                </select>
            </div> -->

            <div
                class="col-span-2"
                v-else-if="variable === '--target-text-transform'"
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="w-full py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm"
                >
                    <option :selected="modelValue === 'none'">none</option>
                    <option value="lowercase">lowercase</option>
                    <option value="uppercase">uppercase</option>
                </select>
            </div>

            <div
                class="col-span-2"
                v-else-if="variable === '--descriptive-font-weight'"
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm pr-3 w-full"
                >
                    <option :value="''">Default</option>
                    <option
                        v-for="weight in engineFontWeights"
                        :key="weight.value"
                        :value="weight.value"
                    >
                        {{ weight.label }}
                    </option>
                </select>
            </div>

            <div
                class="col-span-2"
                v-else-if="
                    variable === '--descriptive-text-align' ||
                    variable === '--unselect-modal-message-text-align'
                "
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="w-full py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm pr-3"
                >
                    <option :selected="modelValue === ''">Default</option>
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                    <option value="justify">Justify</option>
                </select>
            </div>

            <div class="col-span-1" v-else-if="variable === '--logo-position'">
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-[16vmin]"
                >
                    <option :selected="modelValue === ''">Default</option>
                    <option value="fixed">Fixed</option>
                    <option value="absolute">Absolute</option>
                    <option value="relative">Relative</option>
                    <option value="static">Static</option>
                    <option value="sticky">Sticky</option>
                </select>
            </div>

            <div
                class="col-span-2"
                v-else-if="
                    variable === '--unselect-modal-actions-justify-content'
                "
            >
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-[16vmin]"
                >
                    <option value="center">Center</option>
                    <option value="flex-start">Flex Start</option>
                    <option value="flex-end">Flex End</option>
                </select>
            </div>

            <div v-else-if="variable === '--prime-border'" class="col-span-1">
                <input
                    :id="variable + '-width'"
                    type="text"
                    placeholder="Width (e.g. 0.7vmin)"
                    class="w-20 px-1 py-1 border border-gray-300 rounded-md text-sm"
                    :value="(modelValue && modelValue.split(' ')[0]) || ''"
                    @input="
                        $emit(
                            'update:modelValue',
                            `${$event.target.value} ${
                                (modelValue && modelValue.split(' ')[1]) ||
                                'solid'
                            } ${
                                (modelValue && modelValue.split(' ')[2]) ||
                                '#e8e8e8'
                            }`
                        )
                    "
                />

                <select
                    :id="variable + '-style'"
                    class="px-1 py-1 border border-gray-300 rounded-md text-sm"
                    :value="(modelValue && modelValue.split(' ')[1]) || 'solid'"
                    @change="
                        $emit(
                            'update:modelValue',
                            `${
                                (modelValue && modelValue.split(' ')[0]) ||
                                '0.7vmin'
                            } ${$event.target.value} ${
                                (modelValue && modelValue.split(' ')[2]) ||
                                '#e8e8e8'
                            }`
                        )
                    "
                >
                    <option value="solid">Solid</option>
                    <option value="dashed">Dashed</option>
                    <option value="dotted">Dotted</option>
                    <option value="double">Double</option>
                    <option value="groove">Groove</option>
                    <option value="ridge">Ridge</option>
                    <option value="inset">Inset</option>
                    <option value="outset">Outset</option>
                    <option value="none">None</option>
                </select>
                <ColorPicker
                    :id="variable + '-color'"
                    :pureColor="
                        (modelValue && modelValue.split(' ')[2]) || '#e8e8e8'
                    "
                    @pureColorChange="
                        (color) => {
                            $emit(
                                'update:modelValue',
                                `${
                                    (modelValue && modelValue.split(' ')[0]) ||
                                    '0.7vmin'
                                } ${
                                    (modelValue && modelValue.split(' ')[1]) ||
                                    'solid'
                                } ${color}`
                            );
                        }
                    "
                    style="z-index: 10001"
                />
            </div>

            <div v-else-if="isVminInput" class="col-span-2">
                <div class="lg:tooltip" :data-tip="modelValue">
                    <input
                        :id="variable"
                        type="number"
                        :value="getNumericValue(modelValue)"
                        @input="handleVminInput($event.target.value)"
                        class="px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-full"
                        placeholder=""
                        :min="isNegativeAllowed ? -100 : 0"
                        step="0.1"
                    />
                </div>
            </div>

            <div v-else-if="isRemInput" class="col-span-2">
                <div class="lg:tooltip" :data-tip="modelValue">
                    <input
                        :id="variable"
                        type="number"
                        :value="getNumericValue(modelValue)"
                        @input="handleRemInput($event.target.value)"
                        class="px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-full"
                        placeholder=""
                        :min="0"
                        step="0.05"
                    />
                </div>
            </div>

            <div v-else-if="isIntegerInput" class="col-span-2">
                <div class="lg:tooltip" :data-tip="modelValue">
                    <input
                        :id="variable"
                        type="number"
                        :value="getNumericValue(modelValue)"
                        @input="handleIntegerInput($event.target.value)"
                        class="px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-full"
                        placeholder=""
                        min="1"
                        step="1"
                    />
                </div>
            </div>

            <div v-else-if="isScaleInput" class="col-span-2">
                <div class="lg:tooltip" :data-tip="modelValue">
                    <input
                        :id="variable"
                        type="number"
                        :value="getNumericValue(modelValue)"
                        @input="handleScaleInput($event.target.value)"
                        class="px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-full"
                        placeholder=""
                        min="0"
                        step="0.1"
                    />
                </div>
            </div>

            <div v-else-if="isPxInput" class="col-span-2">
                <div class="lg:tooltip" :data-tip="modelValue">
                    <input
                        :id="variable"
                        type="number"
                        :value="getNumericValue(modelValue)"
                        @input="handlePxInput($event.target.value)"
                        class="px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-full"
                        placeholder=""
                        min="0"
                        step="1"
                    />
                </div>
            </div>

            <div v-else-if="isPercentageInput" class="col-span-2">
                <div class="lg:tooltip" :data-tip="modelValue">
                    <input
                        :id="variable"
                        type="number"
                        :value="getNumericValue(modelValue)"
                        @input="handlePercentageInput($event.target.value)"
                        class="px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-full"
                        placeholder=""
                        step="1"
                    />
                </div>
            </div>

            <div v-else-if="isOpacityInput" class="col-span-2">
                <div class="lg:tooltip" :data-tip="modelValue">
                    <input
                        :id="variable"
                        type="number"
                        :value="getNumericValue(modelValue)"
                        @input="handleOpacityInput($event.target.value)"
                        class="px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-[16vmin]"
                        placeholder=""
                        min="0"
                        max="1"
                        step="0.001"
                    />
                </div>
            </div>

            <div
                v-else-if="isLineHeightInput"
                class="col-span-2 flex items-center gap-1"
            >
                <input
                    :id="variable"
                    type="number"
                    :value="getNumericValue(modelValue)"
                    @input="handleLineHeightInput($event.target.value)"
                    class="px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-full"
                    placeholder=""
                    min="0.5"
                    max="3"
                    step="0.1"
                />
            </div>

            <div v-else-if="isDisplayInput" class="col-span-2">
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="w-full py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm"
                >
                    <option value="block">Block</option>
                    <option value="inline">Inline</option>
                    <option value="inline-block">Inline Block</option>
                    <option value="flex">Flex</option>
                    <option value="inline-flex">Inline Flex</option>
                    <option value="grid">Grid</option>
                    <option value="inline-grid">Inline Grid</option>
                    <option value="none">None</option>
                </select>
            </div>

            <div v-else-if="isFlexDirectionInput" class="col-span-2">
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="w-full py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm"
                >
                    <option value="row">Row</option>
                    <option value="row-reverse">Row Reverse</option>
                </select>
            </div>

            <div v-else-if="isFlipInput" class="col-span-2">
                <select
                    :id="variable"
                    @input="$emit('update:modelValue', $event.target.value)"
                    :value="modelValue"
                    class="w-full py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm"
                >
                    <option value="1">Right</option>
                    <option value="-1">Left</option>
                </select>
            </div>

            <ColorPicker
                class="col-span-1"
                v-else-if="variable === '--target-selected-text-color-theme2'"
                :id="variable"
                @pureColorChange="(color) => $emit('update:modelValue', color)"
                :pureColor="modelValue"
                style="z-index: 10001"
            />

            <ColorPicker
                v-else-if="
                    variable === '--engine-background-color' ||
                    variable === '--descriptive-color' ||
                    variable === '--prime-border-color' ||
                    variable === '--prime-bg-color' ||
                    variable === '--prime-font-color' ||
                    variable === '--target-hover-border-color' ||
                    variable === '--target-selected-bg-color' ||
                    variable === '--target-selected-border-color' ||
                    variable === '--target-selected-text-color' ||
                    variable === '--target-text-color' ||
                    variable === '--target-bg-color' ||
                    variable === '--target-border-color' ||
                    variable === '--bottom-text-color' ||
                    variable === '--button-font-color' ||
                    variable === '--button-bg-color' ||
                    variable === '--timer-container-bg-color' ||
                    variable === '--timer-circle-stroke-color' ||
                    variable === '--timer-circle-track-stroke-color' ||
                    variable === '--timer-text-color' ||
                    variable === '--header-text-color' ||
                    variable === '--redirect-text-color' ||
                    variable === '--loading-infinity-color'
                "
                :id="variable"
                @pureColorChange="(color) => $emit('update:modelValue', color)"
                :pureColor="modelValue"
                style="z-index: 10001"
                shape="square"
            />

            <ColorPicker
                v-else-if="isT2Palette"
                class="col-span-1"
                :id="variable"
                @pureColorChange="(color) => $emit('update:modelValue', color)"
                :pureColor="modelValue"
                style="z-index: 10001"
                shape="square"
            />

            <div v-else class="col-span-2">
                <input
                    :id="variable"
                    type="text"
                    :value="modelValue"
                    @input="$emit('update:modelValue', $event.target.value)"
                    class="px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#257556] focus:border-[#257556] text-sm w-full"
                    placeholder=""
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { ColorPicker } from "vue3-colorpicker";
import {
    ENGINE_FONT_FAMILIES,
    ENGINE_FONT_WEIGHTS,
} from "@/Pages/Engine/utils/fontOptions";

const props = defineProps({
    modelValue: String,
    variable: String,
    label: String,
});

const emit = defineEmits(["update:modelValue", "reset"]);

const engineFontFamilies = ENGINE_FONT_FAMILIES;
const engineFontWeights = ENGINE_FONT_WEIGHTS;

// List of variables that should use integer input with vmin
const vminVariables = [
    // Background Logo
    "--logo-width",
    "--logo-right",
    "--logo-top",
    "--logo-bottom",
    "--logo-left",
    //
    "--unselect-modal-border-radius",
    "--descriptive-font-size",
    "--descriptive-container-width",
    "--descriptive-container-max-width",
    "--descriptive-container-padding-x",
    "--descriptive-container-padding-y",
    "--descriptive-margin-top",
    // Button
    "--button-font-size",
    "--button-margin-top-landscape",
    "--button-margin-bottom",
    "--button-padding-vertical",
    "--button-padding-horizontal",
    "--button-arrow-width",
    "--button-arrow-height",
    "--button-flex-gap",
    // Header
    "--header-font-size",
    "--header-max-width",
    "--header-padding-vertical",
    "--header-padding-horizontal",
    // Prime
    "--prime-font-size",
    "--prime-margin",
    "--prime-width",
    "--prime-height",
    "--prime-border-width",
    // Target Wrapper
    "--wrapper-width",
    "--wrapper-height",
    "--target-grid-gap",
    "--wrapper-margin-top",
    "--wrapper-margin-bottom",
    // Targets
    "--target-font-size",
    "--target-width",
    "--target-height",
    "--target-padding",
    // Footer / Bottom Text
    "--bottom-text-font-size",
    "--bottom-text-width",
    "--descriptive-img-1-width",
    "--descriptive-img-1-margin-top",
    "--descriptive-img-1-margin-bottom",
    "--descriptive-img-1-margin-horizontal",
    "--descriptive-img-2-width",
    "--descriptive-img-2-margin-top",
    "--descriptive-img-2-margin-bottom",
    "--descriptive-img-2-margin-horizontal",
    "--descriptive-img-3-width",
    "--descriptive-img-3-margin-top",
    "--descriptive-img-3-margin-bottom",
    "--descriptive-img-3-margin-horizontal",
    "--descriptive-img-4-width",
    "--descriptive-img-4-margin-top",
    "--descriptive-img-4-margin-bottom",
    "--descriptive-img-4-margin-horizontal",
    "--descriptive-img-5-width",
    "--descriptive-img-5-margin-top",
    "--descriptive-img-5-margin-bottom",
    "--descriptive-img-5-margin-horizontal",
    // Header Images
    "--header-img-width",
    "--header-img-margin-x",
    "--header-img-margin-y",
    // Prime image controls
    "--prime-img-width",
    "--prime-img-height",
    "--prime-img-margin-top",
    "--prime-img-margin-bottom",
    // Target image controls
    "--target-img-width",
    "--target-img-height",
    "--target-img-margin-top",
    "--target-img-margin-bottom",
    // Bottom image controls
    "--bottom-img-width",
    "--bottom-img-margin-top",
    "--bottom-img-margin-bottom",
    "--bottom-img-margin-left",
    "--bottom-img-margin-right",
    "--bottom-text-container-margin-top-landscape-ipad",
    "--bottom-text-container-margin-top-landscape-tablet",
    "--bottom-text-container-margin-top-landscape-android",
    "--bottom-text-container-margin-top-landscape-iphone",
    "--bottom-text-container-margin-top-landscape",
    "--bottom-text-container-margin-top-portrait-iphone-safari",
    "--bottom-text-container-margin-top-portrait-iphone-chrome",
    "--bottom-text-container-margin-top-portrait-iphone-edge",
    "--bottom-text-container-margin-top-portrait-iphone-firefox",
    "--bottom-text-container-margin-top-portrait-android-chrome",
    "--bottom-text-container-margin-top-portrait-android-edge",
    "--bottom-text-container-margin-top-portrait-android-firefox",
    "--bottom-text-container-margin-top-portrait",
    "--bottom-text-container-margin-top-portrait-ipad-safari",
    "--bottom-text-container-margin-top-portrait-ipad-firefox",
    "--bottom-text-container-margin-top-portrait-ipad-edge",
    "--bottom-text-container-margin-top-portrait-ipad-chrome",
    "--bottom-text-container-margin-top-portrait-tablet-firefox",
    "--bottom-text-container-margin-top-portrait-tablet-edge",
    "--bottom-text-container-margin-top-portrait-tablet-chrome",
    // Timer
    "--timer-container-width",
    "--timer-container-height",
    "--timer-circle-stroke-width",
    "--timer-text-font-size",
    "--timer-position-top",
    "--timer-position-right",
    "--timer-position-left",
    "--timer-position-bottom",
    // Warning Text
    "--warning-margin-bottom",
    "--warning-margin-bottom-ipad",
    "--warning-margin-bottom-tablet",
];

// List of variables that should use decimal input for rem
const remVariables = [
    "--unselect-modal-message-font-size",
    "--unselect-modal-cancel-font-size",
    "--unselect-modal-submit-font-size",
];

// Variables that may accept negative values
const negativeAllowedVminVariables = [
    "--logo-top",
    "--logo-bottom",
    "--logo-left",
    "--logo-right",
    "--prime-img-margin-top",
    "--prime-img-margin-bottom",
    "--target-img-margin-top",
    "--target-img-margin-bottom",
    "--bottom-img-margin-top",
    "--bottom-img-margin-bottom",
    "--bottom-img-margin-left",
    "--bottom-img-margin-right",
    "--wrapper-margin-top",
    "--wrapper-margin-bottom",
    // Bottom Text
    "--bottom-text-container-margin-top-landscape-ipad",
    "--bottom-text-container-margin-top-landscape-tablet",
    "--bottom-text-container-margin-top-landscape-android",
    "--bottom-text-container-margin-top-landscape-iphone",
    "--bottom-text-container-margin-top-landscape",
    "--bottom-text-container-margin-top-portrait-iphone-safari",
    "--bottom-text-container-margin-top-portrait-iphone-chrome",
    "--bottom-text-container-margin-top-portrait-iphone-edge",
    "--bottom-text-container-margin-top-portrait-iphone-firefox",
    "--bottom-text-container-margin-top-portrait-android-chrome",
    "--bottom-text-container-margin-top-portrait-android-edge",
    "--bottom-text-container-margin-top-portrait-android-firefox",
    "--bottom-text-container-margin-top-portrait",
    "--bottom-text-container-margin-top-portrait-ipad-safari",
    "--bottom-text-container-margin-top-portrait-ipad-firefox",
    "--bottom-text-container-margin-top-portrait-ipad-edge",
    "--bottom-text-container-margin-top-portrait-ipad-chrome",
    "--bottom-text-container-margin-top-portrait-tablet-firefox",
    "--bottom-text-container-margin-top-portrait-tablet-edge",
    "--bottom-text-container-margin-top-portrait-tablet-chrome",
    // Descriptive
    "--descriptive-margin-top",
];

const isNegativeAllowed = computed(() => {
    return negativeAllowedVminVariables.includes(props.variable);
});

// List of variable that should use integer input
const integerVariables = ["--target-grid-repeat"];

// List of variables that should use decimal input for scale
const scaleVariables = [
    "--prime-target-container-scale",
    "--prime-target-container-scale-ipad",
    "--prime-target-container-scale-tablet",
    "--unselect-modal-scale",
];

// List of variables that should use integer input with px
const pxVariables = ["--logo-blur"];

// List of variables that should use integer input with percentage
const percentageVariables = [
    "--unselect-modal-width",
    "--unselect-modal-height",
];

// List of variables that should use decimal input for opacity
const opacityVariables = ["--logo-opacity"];

// List of variables that should use text wrap style select input
const textWrapStyleVariables = [
    "--prime-text-wrap-style",
    "--target-text-wrap-style",
];

// List of variables that should use decimal input for line height
const lineHeightVariables = [
    "--descriptive-line-height",
    "--header-line-height",
    "--prime-line-height",
    "--target-line-height",
    "--bottom-text-line-height",
];

// List of variables that should use display select input
const displayVariables = [
    "--descriptive-img-1-display",
    "--descriptive-img-2-display",
    "--descriptive-img-3-display",
    "--descriptive-img-4-display",
    "--descriptive-img-5-display",
    "--header-img-display",
    "--bottom-img-display",
    "--button-arrow-display",
    "--prime-img-display",
];

// List of variables that should use text transform select input
const textTransformVariables = ["--target-text-transform"];

// List of variables that should use flex direction select input
const flexDirectionVariables = [
    "--button-flex-direction",
    "--unselect-modal-actions-flex-direction",
];

// List of variables that should use flip select input
const flipVariables = ["--button-arrow-transform-x"];

// Theme 2 palette variables pattern
const isT2Palette = computed(() => {
    return /^--t2-palette-(2|3|4|5|6|7)-([1-7])$/.test(props.variable || "");
});

// Check if this variable should use vmin input
const isVminInput = computed(() => {
    return vminVariables.includes(props.variable);
});

// Check if this variable should use px input
const isPxInput = computed(() => {
    return pxVariables.includes(props.variable);
});

const isIntegerInput = computed(() => {
    return integerVariables.includes(props.variable);
});

// Check if this variable should use scale input
const isScaleInput = computed(() => {
    return scaleVariables.includes(props.variable);
});

// Check if this variable should use percentage input
const isPercentageInput = computed(() => {
    return percentageVariables.includes(props.variable);
});

// Check if this variable should use opacity input
const isOpacityInput = computed(() => {
    return opacityVariables.includes(props.variable);
});

// Check if this variable should use line height input
const isLineHeightInput = computed(() => {
    return lineHeightVariables.includes(props.variable);
});

// Check if this variable should use display select input
const isDisplayInput = computed(() => {
    return displayVariables.includes(props.variable);
});

// Check if this variable should use flex direction select input
const isFlexDirectionInput = computed(() => {
    return flexDirectionVariables.includes(props.variable);
});

// Check if this variable should use flip LEFT or RIGHT select input
const isFlipInput = computed(() => {
    return flipVariables.includes(props.variable);
});

// Check if this variable should use text transform select input
const isTextTransformInput = computed(() => {
    return textTransformVariables.includes(props.variable);
});

// Check if this variable should use text wrap style select input
const isTextWrapStyleInput = computed(() => {
    return textWrapStyleVariables.includes(props.variable);
});

// unselect font size rem
const isRemInput = computed(() => {
    return remVariables.includes(props.variable);
});

// Extract numeric value from various unit strings
const getNumericValue = (value) => {
    if (!value) return "";

    // Handle vmin values
    const vminMatch = value.match(/^(-?[\d.]+)vmin$/);
    if (vminMatch) return parseFloat(vminMatch[1]);

    // Handle px values
    const pxMatch = value.match(/^(-?[\d.]+)px$/);
    if (pxMatch) return parseFloat(pxMatch[1]);

    // Handle percentage values
    const percentMatch = value.match(/^([\d.-]+)%$/);
    if (percentMatch) return parseFloat(percentMatch[1]);

    // Handle opacity values (no unit)
    const opacityMatch = value.match(/^([\d.]+)$/);
    if (opacityMatch) return parseFloat(opacityMatch[1]);

    // Handle rem values
    const remMatch = value.match(/^(-?[\d.]+)rem$/);
    if (remMatch) return parseFloat(remMatch[1]);

    return "";
};

// Handle rem input - convert number to rem string
const handleRemInput = (value) => {
    if (value === "" || value === null || value === undefined) {
        emit("update:modelValue", "");
        return;
    }
    const numericValue = parseFloat(value);
    if (!isNaN(numericValue)) {
        emit("update:modelValue", `${numericValue}rem`);
    }
};

// Handle vmin input - convert number to vmin string
const handleVminInput = (value) => {
    if (value === "" || value === null || value === undefined) {
        emit("update:modelValue", "");
        return;
    }
    const numericValue = parseFloat(value);
    if (!isNaN(numericValue)) {
        emit("update:modelValue", `${numericValue}vmin`);
    }
};

// Handle integer input - convert number to integer string
const handleIntegerInput = (value) => {
    if (value === "" || value === null || value === undefined) {
        emit("update:modelValue", "");
        return;
    }
    const numericValue = parseInt(value);
    if (!isNaN(numericValue)) {
        emit("update:modelValue", `${numericValue}`);
    }
};

// Handle scale input - convert number to scale string (no unit)
const handleScaleInput = (value) => {
    if (value === "" || value === null || value === undefined) {
        emit("update:modelValue", "");
        return;
    }
    const numericValue = parseFloat(value);
    if (!isNaN(numericValue)) {
        emit("update:modelValue", `${numericValue}`);
    }
};

// Handle px input - convert number to px string
const handlePxInput = (value) => {
    if (value === "" || value === null || value === undefined) {
        emit("update:modelValue", "");
        return;
    }
    const numericValue = parseFloat(value);
    if (!isNaN(numericValue)) {
        emit("update:modelValue", `${numericValue}px`);
    }
};

// Handle percentage input - convert number to percentage string
const handlePercentageInput = (value) => {
    if (value === "" || value === null || value === undefined) {
        emit("update:modelValue", "");
        return;
    }
    const numericValue = parseFloat(value);
    if (!isNaN(numericValue)) {
        emit("update:modelValue", `${numericValue}%`);
    }
};

// Handle opacity input - convert number to opacity string (no unit)
const handleOpacityInput = (value) => {
    if (value === "" || value === null || value === undefined) {
        emit("update:modelValue", "");
        return;
    }
    const numericValue = parseFloat(value);
    if (!isNaN(numericValue)) {
        emit("update:modelValue", `${numericValue}`);
    }
};

// Handle line height input - convert number to line height string (no unit)
const handleLineHeightInput = (value) => {
    if (value === "" || value === null || value === undefined) {
        emit("update:modelValue", "");
        return;
    }
    const numericValue = parseFloat(value);
    if (!isNaN(numericValue)) {
        emit("update:modelValue", `${numericValue}`);
    }
};

// Handle text transform input - convert text to text transform string
const handleTextTransformInput = (value) => {
    if (value === "" || value === null || value === undefined) {
        emit("update:modelValue", "");
        return;
    }
    emit("update:modelValue", value);
};
</script>

<style>
.style-control-wrapper {
    color: rgb(75, 74, 74) !important;
}
/* Additional wrapper for complex controls */
.style-control-wrapper {
    position: relative;
    z-index: 1;
}

.style-control-wrapper:has(.vc-color-wrap.active) {
    z-index: 100;
}

.vc-color-wrap {
    /* Add border-radius to the color square */
    border-radius: 7px !important;
    margin-top: 0.4vmin;
}
.vc-color-wrap:hover {
    scale: 1.05;
    transition: scale 0.2s ease-in-out;
}
</style>
