<script setup>
import { inject, ref, watch } from "vue";

import { S4Input } from "../../../S4";
import S4InputSelect from "@/S4/InputSelect.vue";

const props = defineProps({
    label: String,
    variable: String,
    data: Object,
});
const emit = defineEmits(["change"]);
const switchValues = inject("switchValues");
const data = ref({
    url: "",
    duration: "",
    passInternalVars: "",
    passExternalVars: "",
    content: "",
});

watch(
    () => props.data,
    () => {
        // console.log(props.data);
        // data.value = { ...props.data };
    },
    { deep: true }
);

// onUpdated(() => {
//     console.log(data.value);
//     data.value = { ...props.data };
// });
watch(
    () => data,
    async () => {
        emit("change", { data: { ...data.value }, variable: props.variable });
    },
    { deep: true }
);
</script>
<template>
    <div class="flex gap-3">
        <div class="grow w-[20vw]">
            <s4-input
                theme="theme2"
                :label="label"
                v-model="data.url"
            ></s4-input>
        </div>
        <div class="w-[8vw]">
            <s4-input
                theme="theme2"
                label="Duration"
                v-model="data.duration"
            ></s4-input>
        </div>
        <div class="w-[8vw]">
            <s4-input-select
                theme="theme2"
                :lists="switchValues"
                label="Pass Internal"
                v-model.number="data.passInternalVars"
            ></s4-input-select>
        </div>
        <div class="w-[8vw]">
            <s4-input-select
                theme="theme2"
                :lists="switchValues"
                label="Pass External"
                v-model.number="data.passExternalVars"
            ></s4-input-select>
        </div>
        <div class="grow w-[20vw]">
            <s4-input
                theme="theme2"
                label="Message"
                v-model="data.content"
            ></s4-input>
        </div>
    </div>
</template>
