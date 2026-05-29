<template>
    <div class="relative group">
        <img data-tool-target="tooltip" :src="imgSrc" :width="imgWidth" />
        <slot name="svg"></slot>
        <div
        class="absolute left-1/2 top-[-10px] translate-y-[-100%] opacity-0 mt-2 z-[999] w-[130px] px-2 py-1 bg-[var(--mcolor)] text-white font-bold text-[10px] rounded leading-[1.5] pointer-events-none group-hover:opacity-100 group-hover:translate-y-[-115%] transition-all duration-300 ease-in-out shadow-md" :style="{ transform: xTransformClass, width: hoverWidth }">
            <div
                class="arrow absolute top-[-6px] left-1/2" :style="{ transform: arrowTransform, rotate: arrowRotate}">
            </div>
            {{ computedLabel }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    imgSrc: {
        type: String,
        required: false,
        default: null,
    },
    label: {
        type: String,
        default: '',
    },
    imgWidth: {
        type: [String, Number],
        default: 23,
    },
    arrowTransform: {
        type: String,
        default: 'translateX(-50%)',
    },
    arrowRotate: {
        type: String,
        default: '0deg',
    },
    xTransformClass: {
        type: String,
        default: 'translate(-50%,-115%)',
    },
    hoverWidth: {
        type: String,
        default: 'w-[130px]',
    },
    preset: {
        type: String,
        default: '',
    },
});

const labelPresets = {
    RTL: 'Switch to Right-to-Left (RTL) layout for languages like Arabic, Hebrew, and Persian.',
    CustomJSCSS: 'Customise functionality with advanced settings and code editing, intended for experienced users and developers.',
    EditLayoutWidth: 'Adjust content box width across different screen sizes.',
    RandomChoices: 'Randomise the order of selected choices.',
    RandomItems: 'Display selected items in random order.',
    RandomValues: 'Display selected values in random order.',
    EmailProviders: 'Allow email input and set a default provider.',
    RandomiseSelection: 'Check the box and select which items should be shown in random order.',
    DisplayLogoOnly: 'The logo that appears at the beginning of the survey.',
    DisplayFooter: 'The bottom section of the webpage where you can display your desired logo.',
    ShowAsterisk: 'Show or hide the asterisk for required questions.',
    Piping: 'Piping allows you to insert answers from previous questions into later questions.',
    ApplyUnselectAll: 'Automatically unselect the other choice when an option is selected.',
    ApplySelectAll: 'Automatically select the other choice when an option is selected.',
    Showchart: 'Enable to display the pie chart.',
    TextEditor: 'When turned off, HTML tags will be visible and editable directly.',
    HideQuestionFrame: 'Check to hide instruction.',
    EditTextboxDimension: 'Adjust the textbox size for different screen sizes.',
    Triggers: 'Triggers are used to perform actions when a question is answered.',
    Actions: 'Actions are used to perform actions when a question is answered.',
    LogicType: 'Logic types are used to perform actions when a question is answered.',
    Slider: 'This sets the starting point for respondents on the slider',
    EditItemDimension: 'Adjust the item size for different screen sizes.',
    Required: 'Answer is required to proceed',
    ManageLanguages: 'Manage languages for your questionnaire.',
    Gibberish: 'Identifies incoherent or nonsensical text responses to ensure data quality.',
    DisplayChoices: 'Displays choices based on the number you enter. The number you input determines how many choices are shown per page.'
};

const computedLabel = computed(() => {
    return labelPresets[props.preset] || props.label;
});
</script>

<style scoped>
.arrow {
    border-top: unset;
    width: 0;
    height: 0;
    border-left: 7px solid transparent;
    border-right: 7px solid transparent;
    border-top: 7px solid var(--mcolor);
    bottom: -7px;
    top: unset;
}

/* --- Jello Animation --- */
@keyframes jello-vertical {
  0% {
    transform: scale3d(1, 1, 1);
  }
  30% {
    transform: scale3d(0.75, 1.25, 1);
  }
  40% {
    transform: scale3d(1.25, 0.75, 1);
  }
  50% {
    transform: scale3d(0.85, 1.15, 1);
  }
  65% {
    transform: scale3d(1.05, 0.95, 1);
  }
  75% {
    transform: scale3d(0.95, 1.05, 1);
  }
  100% {
    transform: scale3d(1, 1, 1);
  }
}

/* --- Image Animation on Hover --- */
.group:hover img {
  animation: jello-vertical 0.7s both;
}
</style>