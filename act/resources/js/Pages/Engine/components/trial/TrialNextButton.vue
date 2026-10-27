<template>
    <button
        :class="{
            'opacity-0 pointer-events-none': !showButton,
            'pointer-events-auto': showButton,
        }"
        @touchstart=""
        @touchend=""
        @click="showButton ? $emit('click') : null"
        class="trial-next-button bg-[#257556] active:scale-110 hover:shadow-xl landscape:text-[2.3vmin] text-[5vmin] px-[5vmin] py-[2vmin] landscape:px-[3vmin] landscape:py-[1vmin] portrait:mt-[10vmin] active:bg-#e28733 gap-2 flex items-center justify-center"
        :style="trialNextButtonStyle"
    >
        <span v-html="buttonText"></span>
        <svg
            aria-hidden="true"
            focusable="false"
            data-prefix="far"
            data-icon="arrow-circle-right"
            role="img"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 512 512"
            class="w-[6vmin] h-[6vmin] landscape:w-[3vmin] landscape:h-[3vmin]"
            :style="trialNextButtonArrowStyle"
        >
            <path
                fill="currentColor"
                d="M8 256c0 137 111 248 248 248s248-111 248-248S393 8 256 8 8 119 8 256zm448 0c0 110.5-89.5 200-200 200S56 366.5 56 256 145.5 56 256 56s200 89.5 200 200zM266.9 126.1l121.4 121.4c4.7 4.7 4.7 12.3 0 17L266.9 385.9c-4.7 4.7-12.3 4.7-17 0l-19.6-19.6c-4.8-4.8-4.7-12.5.2-17.2l70.3-67.1H140c-6.6 0-12-5.4-12-12v-28c0-6.6 5.4-12 12-12h160.8l-70.3-67.1c-4.9-4.7-5-12.4-.2-17.2l19.6-19.6c4.7-4.7 12.3-4.7 17 0z"
                class=""
            ></path>
        </svg>
    </button>
</template>

<script setup>
import { computed, ref, onMounted } from "vue";
import {
    trialNextButtonStyle,
    trialNextButtonArrowStyle,
} from "../../utils/trialStyling";
import { surveyTestState } from "../../utils/surveyTestStore";

const props = defineProps({
    isLast: {
        type: Boolean,
        default: false,
    },
});

defineEmits(["click"]);

const buttonText = computed(() => {
    return props.isLast
        ? surveyTestState.buttons.final
        : surveyTestState.buttons.next;
});

const showButton = ref(false);

onMounted(() => {
    setTimeout(() => {
        showButton.value = true;
    }, surveyTestState.buttons.button_delay);
});
</script>
