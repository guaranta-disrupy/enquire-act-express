<template>
    <div class="w-full px-5 h-[50vmin] flex flex-col gap-4">
        <div
            @click="setType(2)"
            class="flex flex-row items-center gap-10 cursor-pointer border-2 rounded-lg p-5 px-10 flex-1 transition-colors duration-200"
            :class="
                selectedType === 2
                    ? 'border-[#15919A] bg-[#15919A20]'
                    : 'border-gray-200 hover:border-[#15919a70]'
            "
        >
            <div class="w-20 flex items-center justify-center ml-2">
                <SingleAnswerIcon />
            </div>
            <div class="flex-1">
                <h1 class="text-md font-bold">Likert Scale (Single Answer)</h1>
                <p class="text-gray-600 mt-2 text-sm">
                    <strong>Multiple primes: </strong> This question type asks participants to pick one option on a scale that best shows how they feel — like how much they agree, how often something happens, or how important something is to them.
                </p>

                <p class="text-gray-600 mt-2 text-sm">
                    The options are in order and can’t overlap (you can only pick one).
                </p>

                <p class="text-gray-600 mt-2 text-sm">
                    It’s great for measuring opinions or attitudes.
                </p>

                <p class="text-gray-600 mt-2 text-sm">
                    <strong>Example: </strong>
                    “How much do you agree with this statement?”
                    (Strongly Disagree – Disagree – Neutral – Agree – Strongly Agree)
                </p>

            </div>
        </div>
        <div
            @click="setType(1)"
            class="flex flex-row items-center gap-10 cursor-pointer border-2 rounded-lg p-5 px-10 flex-1 transition-colors duration-200"
            :class="
                selectedType === 1
                    ? 'border-[#15919A] bg-[#15919A20]'
                    : 'border-gray-200 hover:border-[#15919a70]'
            "
        >
            <div class="w-20 flex items-center justify-center ml-2">
                <MultipleAnswerIcon />
            </div>
            <div class="flex-1">
                <h1 class="text-md font-bold text-gray-800">Attributes Choice (Multiple Answer / Single Answer)</h1>
                <p class="text-gray-600 mt-2 text-sm">
                    <strong>Single prime: </strong> This question type lets participants choose more than one option when more than one answer might apply.
                </p>

                <p class="text-gray-600 mt-2 text-sm">
                    It helps paint a fuller picture of someone’s experiences or preferences.
                </p>

                <p class="text-gray-600 mt-2 text-sm">
                    Use this when answers aren’t mutually exclusive, so participants can check all that fit them.
                </p>

                <p class="text-gray-600 mt-2 text-sm">
                    <strong>Example: </strong>
                    “What languages do you speak?”
                    “Which social media platforms do you use for work?”
                </p>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import MultipleAnswerIcon from "./assets/MultipleAnswerIcon.vue";
import SingleAnswerIcon from "./assets/SingleAnswerIcon.vue";

const props = defineProps(["type"]);

const selectedType = ref(props.type);

const emit = defineEmits(["set-type"]);

const setType = (val) => {
    selectedType.value = val;
    emit("set-type", val);
};
</script>

<style scoped></style>
