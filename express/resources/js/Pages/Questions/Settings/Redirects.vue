<script setup>
import { inject, reactive, ref } from "vue";
import S4InputSelect from "@/S4/InputSelect.vue";
import { S4Input } from "../../../S4";
import IconsVue from "../../../S4/Icons.vue";
import ListVue from "./List.vue";

const form = inject("form");
const switchValues = inject("switchValues");
const emit = defineEmits(["sendData"]);
const redirects = reactive([
    { label: "Complete", variable: "complete" },
    { label: "Quality Control", variable: "qualityControl" },
    { label: "Screen out", variable: "screenout" },
]);

const data = ref({
    panel: "",
    variable: "",
    complete: {
        url: "",
        duration: "",
        passInternalVars: "",
        passExternalVars: "",
        content: "",
    },
    qualityControl: {
        url: "",
        duration: "",
        passInternalVars: "",
        passExternalVars: "",
        content: "",
    },
    screenout: {
        url: "",
        duration: "",
        passInternalVars: "",
        passExternalVars: "",
        content: "",
    },
});
const editForm = ref({
    edit: false,
    key: -1,
});

const handleGroupData = (obj) => {
    data.value[obj.variable] = clone(data.value);
};

const handleAdd = () => {
    if (!form.settings.setup.hasOwnProperty("redirects2"))
        form.settings.setup.redirects2 = [];
    if (editForm.value.edit) {
        form.settings.setup.redirects2[editForm.value.key] = clone(data.value);
        resetData();
        editForm.value.edit = false;
    } else {
        form.settings.setup.redirects2.push(clone(data.value));
        resetData();
    }
};

const clone = (o) => {
    return JSON.parse(JSON.stringify(o));
};

const handleAction = (key, action) => {
    switch (action) {
        case "edit":
            if (editForm.value.key === key) return;
            editForm.value.edit = true;
            editForm.value.key = key;

            data.value = form.settings.setup.redirects2[key];

            // handleSendData({ ...form.settings.setup.redirects2[key] });
            break;
        case "delete":
            form.settings.setup.redirects2.splice(key, 1);
            break;
    }
    console.log("emit on redirect", key, action);
};

const resetData = () => {
    data.value = {
        panel: "",
        variable: "",
        complete: {
            url: "",
            duration: "",
            passInternalVars: "",
            passExternalVars: "",
            content: "",
        },
        qualityControl: {
            url: "",
            duration: "",
            passInternalVars: "",
            passExternalVars: "",
            content: "",
        },
        screenout: {
            url: "",
            duration: "",
            passInternalVars: "",
            passExternalVars: "",
            content: "",
        },
    };
};
</script>
<template>
    <div class="relative">

        <div class="bg-cyan-800/80 p-1 pl-3 rounded-lg text-white -mt-0.5 mb-3 grid justify-center">Panel</div>

        <div class="mb-7">
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-1">
                    <s4-input theme="theme2" label="Name" v-model="data.panel"></s4-input>
                </div>
                <div class="col-span-1">
                    <s4-input theme="theme2" label="Variable" v-model="data.variable"></s4-input>
                </div>
            </div>
        </div>

        <!-- <div class="flex gap-3">
            <div class="w-[20vw]">
                <s4-input theme="theme2" label="Panel Name" v-model="data.panel"></s4-input>
            </div>
            <div class="w-[20vw]">
                <s4-input theme="theme2" label="Variable" v-model="data.variable"></s4-input>
            </div>
        </div> -->

        <template v-for="(r_item, r_key) in redirects" :key="r_key">

            <div class="mb-10">
                <!-- <div class="text-slate-800 mb-3">{{ r_item.label }}</div> -->
                <div>
                    <div class="grid grid-cols-3 gap-1">
                        <div class="col-span-2">
                            <span class="absolute text-slate-500 font-bold text-xs bg-slate-100 px-3 py-1 rounded-lg">{{
                                r_item.label }}</span>
                            <s4-input theme="theme2" :label="''" v-model="data[r_item.variable].url"></s4-input>
                        </div>
                        <div class="col-span-1">
                            <s4-input theme="theme2" label="Message" v-model="data[r_item.variable].content"></s4-input>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <s4-input theme="theme2" label="Duration" v-model="data[r_item.variable].duration"></s4-input>
                        <s4-input-select theme="theme2" :lists="switchValues" label="Pass Internal"
                            v-model.number="data[r_item.variable].passInternalVars"></s4-input-select>

                        <s4-input-select theme="theme2" :lists="switchValues" label="Pass External"
                            v-model.number="data[r_item.variable].passExternalVars"></s4-input-select>
                    </div>
                </div>
            </div>
            <!-- <div class="flex gap-3">
                <div class="grow w-[20vw]">
                    <s4-input theme="theme2" :label="r_item.label" v-model="data[r_item.variable].url"></s4-input>
                </div>
                <div class="w-[8vw]">
                    <s4-input theme="theme2" label="Duration" v-model="data[r_item.variable].duration"></s4-input>
                </div>
                <div class="w-[8vw]">
                    <s4-input-select theme="theme2" :lists="switchValues" label="Pass Internal"
                        v-model.number="data[r_item.variable].passInternalVars"></s4-input-select>
                </div>
                <div class="w-[8vw]">
                    <s4-input-select theme="theme2" :lists="switchValues" label="Pass External"
                        v-model.number="data[r_item.variable].passExternalVars"></s4-input-select>
                </div>
                <div class="grow w-[20vw]">
                    <s4-input theme="theme2" label="Message" v-model="data[r_item.variable].content"></s4-input>
                </div>
            </div> -->
        </template>

        <div @click="handleAdd" class="-mt-3 text-sky-500 font-semibold flex justify-center cursor-pointer hover:text-sky-600 w-full">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                class="w-4 h-4 self-center mr-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            {{ editForm.edit ? "Update Panel" : "Add Panel" }}
        </div>

        <div class="mt-10">
            <div class="">
                <ListVue :data="{ ...form.settings.setup.redirects2 }" v-on:onAction="handleAction" />
            </div>
        </div>
    </div>
</template>
