<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4Input from "@/S4/Input.vue";
import S4Button from "@/S4/Button.vue";
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import S4TextArea from "@/S4/TextArea.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import { ref, watch, nextTick, inject, computed, onBeforeMount } from "vue";
import S4InputSlider from '@/S4/InputSlider.vue';
import { usePage } from "@inertiajs/vue3";
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";
import helpers from "@/helpers.js";
const add_edit_form = inject("add_edit_form");

const new_choice1 = ref("");
watch(new_choice1, async (val) => {
    if (val != "") {
        add_edit_form.item1.push({ name: val });
        await nextTick();
        itemRefs1.value[add_edit_form.item1.length - 1].focus();
        new_choice1.value = "";
    }
});
const new_choice2 = ref("");
watch(new_choice2, async (val) => {
    if (val != "") {
        add_edit_form.item2.push({ name: val });
        await nextTick();
        itemRefs2.value[add_edit_form.item2.length - 1].focus();
        new_choice2.value = "";
    }
});
const itemRefs1 = ref([]);
const itemRefs2 = ref([]);
const showRowTextArea = ref(false);
const rowArea = ref(null);
const showRowTextArea2 = ref(false);
const rowArea2 = ref(null);

const action = ref("add");
const selected_item = ref({});
const gallery = ref({
    show: false,
});
const itemGroup = ref("");

const deleteChoice = (num, index) => {
    if (num == "1") add_edit_form.item1.splice(index, 1);
    else add_edit_form.item2.splice(index, 1);
};

const showGallery = (index, act, groupName) => {
    //action interact with add or edit
    if (act == "edit") {
        selected_item.value =
            groupName == "item1"
                ? add_edit_form.item1[index]
                : add_edit_form.item2[index];
    }
    itemGroup.value = groupName;
    action.value = act;
    gallery.value.show = true;
};
const closeGallery = () => {
    gallery.value.show = false;
};
const selectGallery = (gallery) => {
    const { ImageWithHeaders } = helpers();
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    const src_value = gallery.file_path
        ? `${fluentBase}/storage/${gallery.file_path}`
        : ImageWithHeaders(usePage().props.app_url + '/gallery/' + (gallery.filename || gallery.file_name));
    if (action.value == "add") {
        if (itemGroup.value == "item1")
            new_choice1.value = '<img src="' + src_value + '"/>';
        else new_choice2.value = '<img src="' + src_value + '"/>';
    } else selected_item.value.name = '<img src="' + src_value + '"/>';
};
const splitForRow = (item = 1) => {
    if (rowArea.value != null || rowArea2.value != null) {
        let newRow =
            item === 1 ? rowArea.value.split("\n") : rowArea2.value.split("\n");
        _.forEach(newRow, function (row, index) {
            if (row !== "") {
                if (item === 1)
                    add_edit_form.item1.push({
                        name: row,
                        index: add_edit_form.items.length + 1,
                        score: add_edit_form.items.length + 1,
                    });
                else
                    add_edit_form.item2.push({
                        name: row,
                        index: add_edit_form.items.length + 1,
                        score: add_edit_form.items.length + 1,
                    });
            }
        });
    }

    if (item === 1) {
        rowArea.value = null;
        showRowTextArea.value = false;
    } else {
        rowArea2.value = null;
        showRowTextArea2.value = false;
    }
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
        <!-- ITEM 1 -->

        <div class="col-span-6 mt-3">
            <div class="col-span-12">
                <jet-label class="font-bold">Items 1:</jet-label>
                <template v-for="(Item, index) in add_edit_form.item1" :key="index">
                    <div class="flex gap-1">
                        <span class="mt-2 mr-2">{{ index + 1 }}</span>
                        <s4-input color="ssrGray" class="w-full mb-1" placeholder="Image source"
                            v-model="add_edit_form.item1[index].name" :ref="(el) => {
                                itemRefs1[index] = el;
                            }
                                " @click="showGallery(index, 'edit', 'item1')">
                        </s4-input>
                        <ButtonIconsVue @click="deleteChoice('1', index)" icon="delete" />
                    </div>
                </template>
            </div>
            <div class="col-span-12">
                <div class="flex">
                    <s4-input color="ssrGray2" class="w-full mb-10 mr-1" placeholder="Image source"
                        v-model="new_choice1" @click="showGallery(index, 'add', 'item1')"></s4-input>
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

        <!-- ITEM 2 -->

        <div class="col-span-6 mt-3">
            <div class="col-span-12">
                <jet-label class="font-bold">Items 2:</jet-label>
                <template v-for="(Item, index) in add_edit_form.item2" :key="index">
                    <div class="flex gap-1">
                        <span class="mt-2 mr-2">{{ index + 1 }}</span>
                        <s4-input color="ssrGray" class="w-full mb-1" placeholder="Image source"
                            v-model="add_edit_form.item2[index].name" :ref="(el) => {
                                itemRefs2[index] = el;
                            }
                                " @click="showGallery(index, 'edit', 'item2')">
                        </s4-input>

                        <ButtonIconsVue @click="deleteChoice('2', index)" icon="delete" />
                    </div>
                </template>
            </div>
            <div class="col-span-12">
                <div class="flex">
                    <s4-input color="ssrGray2" class="w-full mb-10 mr-1" placeholder="Image source"
                        v-model="new_choice2" @click="showGallery(index, 'add', 'item2')"></s4-input>
                </div>
            </div>
            <s4-button v-if="showRowTextArea2 == false" :theme="'ssrAddColRow'" class="py-1 px-3 mb-10 w-32"
                color="ssrB" @click="showRowTextArea2 = !showRowTextArea2">
                Multiple Inputs
            </s4-button>
            <div class="col-span-12">
                <div>
                    <s4-text-area color="ssrGray" v-show="showRowTextArea2" v-model="rowArea2" class="w-full h-40"
                        placeholder="Enter choices separated by new line..."></s4-text-area>
                </div>
                <div class="flex mb-5" v-show="showRowTextArea2">
                    <s4-button :theme="'ssrAddColRow'" class="py-1 px-2 mt-2" @click="splitForRow(2)">Add</s4-button>
                    <s4-button :theme="'ssrAddColRow'" class="py-1 px-2 ml-1 mt-2"
                        @click="showRowTextArea2 = false">Cancel</s4-button>
                </div>
            </div>
        </div>
    </div>

    <BorderSetup></BorderSetup>

    <div class="mt-4 grid grid-cols-12 gap-1">
        <div class="col-span-6">
            <div class="col-span-6 p-1 rounded-lg">
                <label class="items-center cursor-pointer">
                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.random" />
                    <span class="ml-2 text-sm text-gray-600 select-none">Random</span>
                </label>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-12 gap-1 mt-1">
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
