<template>
    <div 
        ref="containerRef"
        class="relative group z-[10001]" 
        @mouseenter="handleMouseEnter" 
        @mouseleave="handleMouseLeave"
    >
        <img v-if="imgSrc" data-tool-target="tooltip-chat" :src="imgSrc" />
        <slot name="svg"></slot>
        <Teleport to="body">
            <div
            v-if="isHovered"
            ref="tooltipRef"
            :class="[
                'fixed z-[10001] px-2 py-1 text-white font-bold text-[10px] rounded leading-[1.5] pointer-events-none transition-opacity duration-300 ease-in-out shadow-md',
                isHovered ? 'opacity-100' : 'opacity-0'
            ]"
            :style="{ 
                left: tooltipPosition.left + 'px',
                top: tooltipPosition.top + 'px',
                transform: xTransformClass, 
                width: hoverWidth === 'auto' ? 'auto' : hoverWidth, 
                backgroundColor: bgColor 
            }">
                <div
                    class="arrow absolute top-[-6px] left-1/2" :style="{ transform: arrowTransform, rotate: arrowRotate, borderTopColor: bgColor }">
                </div>
                {{ computedLabel }}
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';

const isHovered = ref(false);
const containerRef = ref(null);
const tooltipRef = ref(null);
const tooltipPosition = ref({ left: 0, top: 0 });

const updateTooltipPosition = () => {
    if (!containerRef.value || !isHovered.value) return;
    
    const rect = containerRef.value.getBoundingClientRect();
    
    // Calculate center of the container (fixed positioning doesn't need scroll offset)
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top;
    
    tooltipPosition.value = {
        left: centerX,
        top: centerY
    };
};

const handleMouseEnter = () => {
    isHovered.value = true;
    nextTick(() => {
        updateTooltipPosition();
    });
};

const handleMouseLeave = () => {
    isHovered.value = false;
};

onMounted(() => {
    window.addEventListener('scroll', updateTooltipPosition, true);
    window.addEventListener('resize', updateTooltipPosition);
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateTooltipPosition, true);
    window.removeEventListener('resize', updateTooltipPosition);
});

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
    bgColor: {
        type: String,
        default: 'var(--mcolor, #257556)',
    },
});

const labelPresets = {
    // RTL: 'Switch to Right-to-Left (RTL) layout for languages like Arabic, Hebrew, and Persian.',
    // CustomJSCSS: 'Customise functionality with advanced settings and code editing, intended for experienced users and developers.',
    // EditLayoutWidth: 'Adjust content box width across different screen sizes.',
    // RandomChoices: 'Randomise the order of selected choices.',
    // RandomItems: 'Display selected items in random order.',
    // RandomValues: 'Display selected values in random order.',
    // EmailProviders: 'Allow email input and set a default provider.',
    // RandomiseSelection: 'Check the box and select which items should be shown in random order.',
    // DisplayLogoOnly: 'The logo that appears at the beginning of the survey.',
    // DisplayFooter: 'The bottom section of the webpage where you can display your desired logo.',
    // ShowAsterisk: 'Show or hide the asterisk for required questions.',
    // Piping: 'Piping allows you to insert answers from previous questions into later questions.',
    // ApplyUnselectAll: 'Automatically unselect the other choice when an option is selected.',
    // ApplySelectAll: 'Automatically select the other choice when an option is selected.',
    // Showchart: 'Enable to display the pie chart.',
    // TextEditor: 'When turned off, HTML tags will be visible and editable directly.',
    // HideQuestionFrame: 'Check to hide instruction.',
    // EditTextboxDimension: 'Adjust the textbox size for different screen sizes.',
    // Triggers: 'Triggers are used to perform actions when a question is answered.',
    // Actions: 'Actions are used to perform actions when a question is answered.',
    // LogicType: 'Logic types are used to perform actions when a question is answered.',
    // Slider: 'This sets the starting point for respondents on the slider',
    // EditItemDimension: 'Adjust the item size for different screen sizes.',
    // Required: 'Answer is required to proceed',
    // ManageLanguages: 'Manage languages for your questionnaire.',
    // Gibberish: 'Identifies incoherent or nonsensical text responses to ensure data quality.',
    // DisplayChoices: 'Displays choices based on the number you enter. The number you input determines how many choices are shown per page.'
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
    border-top: 7px solid var(--mcolor, #257556);
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