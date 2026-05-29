<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4Input from "@/S4/Input.vue";
import S4Button from "@/S4/Button.vue";
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import S4TextArea from "@/S4/TextArea.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import { nextTick, ref, watch, inject, onBeforeMount, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";
import helpers from "@/helpers.js";
import S4InputSlider from '@/S4/InputSlider.vue';
const add_edit_form = inject("add_edit_form");

const itemRefs = ref([]);
const new_choice = ref("");
watch(new_choice, async (val) => {
    if (val != "") {
        add_edit_form.items.push({ name: val });
        await nextTick();
        itemRefs.value[add_edit_form.items.length - 1].focus();
        new_choice.value = "";
    }
});

const gallery = ref({
    show: false,
});
const action = ref("add");
const selected_item = ref({});
const showRowTextArea = ref(false);
const rowArea = ref(null);

const deleteChoice = (index) => {
    add_edit_form.items.splice(index, 1);
};
const showGallery = (index, act) => {
    //action interact with add or edit
    if (act == "edit") {
        selected_item.value = add_edit_form.items[index];
    }
    action.value = act;
    gallery.value.show = true;
};
const closeGallery = () => {
    gallery.value.show = false;
};
const selectGallery = (el) => {
    const { ImageWithHeaders } = helpers();
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    const src_value = el.file_path
        ? `${fluentBase}/storage/${el.file_path}`
        : ImageWithHeaders(usePage().props.app_url + '/gallery/' + (el.filename || el.file_name));
    if (action.value == "add")
        new_choice.value = '<img src="' + src_value + '"/>';
    else selected_item.value.name = '<img src="' + src_value + '"/>';
};

const splitForRow = () => {
    if (rowArea.value != null) {
        let newRow = rowArea.value.split("\n");
        _.forEach(newRow, function (row, index) {
            if (row !== "") {
                add_edit_form.items.push({
                    name: row,
                    index: add_edit_form.items.length + 1,
                    score: add_edit_form.items.length + 1,
                });
            }
        });
    }

    rowArea.value = null;
    showRowTextArea.value = false;
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
</script>

<template>
    <div class="grid grid-cols-12 gap-1">
        <div class="col-span-12 mt-3">
            <jet-label class="font-bold">Items:</jet-label>
            <template v-for="(Item, index) in add_edit_form.items" :key="index">
                <div class="flex gap-1">
                    <span class="mt-2 mr-2">{{ index + 1 }}</span>
                    <s4-input color="ssrGray" class="w-full mb-1" placeholder="Image source"
                        v-model="add_edit_form.items[index].name" :ref="(el) => {
                            itemRefs[index] = el;
                        }
                            " @click="showGallery(index, 'edit')">
                    </s4-input>
                    <ButtonIconsVue icon="delete" @click="deleteChoice(index)" />
                </div>
            </template>
        </div>
        <div class="col-span-12">
            <div class="flex">
                <s4-input @click="showGallery(-1, 'add')" color="ssrGray2" class="w-full mb-10 mr-1"
                    placeholder="Image source" v-model="new_choice"></s4-input>
            </div>
        </div>
        <s4-button v-if="showRowTextArea == false" :theme="'ssrAddColRow'" class="py-1 px-3 mb-10 w-32" color="ssrB"
            @click="showRowTextArea = !showRowTextArea">
            Multiple Inputs
        </s4-button>
        <div class="col-span-12">
            <div>
                <s4-text-area color="ssrGray" v-show="showRowTextArea" v-model="rowArea" class="w-full h-40"
                    placeholder="Enter choices separated by new line..."></s4-text-area>
            </div>
            <div class="flex mb-5" v-show="showRowTextArea">
                <s4-button :theme="'ssrAddColRow'" class="py-1 px-2 mt-2" @click="splitForRow()">Add</s4-button>
                <s4-button :theme="'ssrAddColRow'" class="py-1 px-2 ml-1 mt-2"
                    @click="showRowTextArea = false">Cancel</s4-button>
            </div>
        </div>
    </div>

    <BorderSetup></BorderSetup>

    <div class="mt-4 grid grid-cols-12 gap-1">
        <div class="col-span-12 grid grid-cols-12 gap-1">
            <div class="col-span-6 p-1 rounded-lg">
                <label class="items-center cursor-pointer">
                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.random" />
                    <span class="ml-2 text-sm text-gray-600 select-none">Random</span>
                </label>
            </div>
        </div>
        <div class="col-span-12 grid grid-cols-12 gap-1">
            <div class="col-span-6 mt-[0.4vmin]">
                <jet-label>Time Interval:</jet-label>
                <s4-input min="1000" type="number" color="ssrGray" class="w-full mb-1"
                    v-model.number="add_edit_form.setup.interval">
                </s4-input>
            </div>
            <div class="col-span-6">
                <div class="flex items-center">
                    <jet-label>Layout Width</jet-label>
                    <Tooltip :imgSrc="question"
                        label="Sets the maximum width of the content box. Adjust this setting to control how wide the content can appear on the page.">
                    </Tooltip>
                </div>
                <S4InputSlider color="ssrGray" :placeholder="'e.g. 100'" :units="['%', 'vmin', 'vw', 'auto']"
                    v-model.number="boxStyleValue" v-model:unitValue="boxStyleUnit" :allowAuto="true" min="0">
                </S4InputSlider>
            </div>
        </div>
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
