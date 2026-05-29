<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import WarningModal from "../../../../S4/Warning.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4Input from "@/S4/Input.vue";
import S4Button from "@/S4/Button.vue";
import S4TextArea from "@/S4/TextArea.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import { usePage } from "@inertiajs/vue3";
import helpers from "@/helpers.js";
import {
    computed,
    nextTick,
    ref,
    watch,
    inject,
    onBeforeMount,
    reactive,
} from "vue";
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";
import TE2 from "@/S4/TE2.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";

const add_edit_form = inject("add_edit_form");
const showItemTextArea = ref(false);
const itemArea = ref(null);
const equivalentArea = ref(null);
const itemAreaElem = ref(null);

const new_item = ref("");
// watch(new_item, async (val) => {
//     if (val != "") {
//         add_edit_form.items.push({ name: val });
//         await nextTick();
//         itemRefs.value[add_edit_form.items.length - 1].focus();
//         new_item.value = "";
//     }
// });

watch(new_item, async (val) => {
    if (val !== "" && val !== "<p><br></p>") {
        // Add the new item to the form's items array
        add_edit_form.items.push({ name: val });
        await nextTick();
        // Focus the new item input
        itemRefs.value[add_edit_form.items.length - 1].focus();
        // Clear the `new_item` value
        new_item.value = "<p><br></p>";
    }
});

const itemRefs = ref([]);

const randomItems = computed(() => {
    let items = [];
    for (const key in add_edit_form.items) {
        const val = parseInt(key) + 1;
        let data = {
            value: val,
            label: add_edit_form.items[key].name,
        };

        items.push({ ...data });
    }
    return items;
});
const deleteChoice = (choice, index) => {
    add_edit_form[choice].splice(index, 1);
};

const splitForItem = (item = 0) => {
    if (itemArea.value != null) {
        const newEq = equivalentArea.value?.split("\n") ?? [];
        const newRow =
            itemArea.value
                .match(/<p>(.*?)<\/p>/g)
                ?.map((p) => p.replace(/<\/?p>/g, "").trim()) || [];
        _.forEach(newRow, (row, index) => {
            if (!row) {
                return;
            }

            const eqRaw = newEq[index]?.trim?.();
            const equivalent = eqRaw && eqRaw !== "" ? eqRaw : row;

            add_edit_form.items.push({
                name: "<p>" + row + "</p>",
                equivalent,
                index: add_edit_form.items.length + 1,
            });
        });
    }

    itemArea.value = "<p><br></p>";
    equivalentArea.value = "";
    showItemTextArea.value = false;
};

const cancelMultipleItems = () => {
    showItemTextArea.value = false;
    // Clear all text areas
    itemArea.value = '<p><br></p>';
    equivalentArea.value = '';
    // Force clear the TE2 component if it has a forceClear method
    if (itemAreaElem.value && itemAreaElem.value.forceClear) {
        itemAreaElem.value.forceClear();
    }
};

//const for box style max-width
const boxStyleValue = ref("");
const boxStyleUnit = ref("%");

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
    add_edit_form.choices = [];
});

const gallery = reactive({
    show: false,
    index: -1,
});
const showGallery = (index) => {
    gallery.index = index; // Set the index, -1 for `new_item`
    gallery.show = true;
};

const closeGallery = () => {
    gallery.show = false;
};

const selectGallery = (el) => {
    const { ImageWithHeaders } = helpers();
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    const imageUrl = el.file_path
        ? `${fluentBase}/storage/${el.file_path}`
        : ImageWithHeaders(`${usePage().props.app_url}/gallery/${el.filename}`);
    const imageTag = `<img src="${imageUrl}" />`;
    if (gallery.index >= 0) {
        // Update existing item
        add_edit_form.items[gallery.index].name = imageTag;
    } else {
        // Populate `new_item`
        new_item.value = imageTag; // Trigger the watcher for `new_item`
    }
    closeGallery();
};
const showDeleteAllModal = ref(false);
const deleteAllItems = () => {
    add_edit_form.items = [];
    showDeleteAllModal.value = false;
};

// Auto-select all items when random items checkbox is checked
watch(
    () => add_edit_form.setup.validate.randomItems,
    async (newValue) => {
        if (newValue) {
            // Wait for DOM update to ensure the multiselect is rendered
            await nextTick()
            // Select all options by extracting the values from randomItems
            add_edit_form.setup.randomItems = randomItems.value.map(item => item.value)
        } else {
            add_edit_form.setup.randomItems = [];
        }
    }
);

// Auto-add new items to random items when checkbox is already checked
watch(
    () => add_edit_form.items.length,
    async (newLength, oldLength) => {
        if (add_edit_form.setup.validate.randomItems && newLength > oldLength) {
            // Wait for DOM update to ensure the multiselect is rendered
            await nextTick()
            // Update random items to include all current items
            add_edit_form.setup.randomItems = randomItems.value.map(item => item.value)
        }
    }
);

// Watch for changes in randomItems array to disable checkbox when empty
watch(
    () => add_edit_form.setup.randomItems,
    (newValue) => {
        if (Array.isArray(newValue) && newValue.length === 0) {
            add_edit_form.setup.validate.randomItems = false;
        }
    },
    { deep: true }
);

// Handle X SVG click in Random Items multiselect
const handleRandomItemsTagRemoved = () => {
    // This function is kept for potential future use
    // The actual logic is now handled by the watcher above
};
</script>

<template>
    <div class="flex justify-end">
        <button v-if="add_edit_form.items.length > 0" @click="showDeleteAllModal = true"
            class="inline-flex items-center hover:bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2 transition-all duration-300 ease-in-out hover:text-red-600" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>

            <span class="transition-all duration-300 ease-in-out hover:text-red-600">
                Delete All Items
            </span>
        </button>
    </div>
    <div class="grid grid-cols-12 gap-1">
        <div class="col-span-12 gap-1">
            <div class="col-span-6">
                <div v-if="add_edit_form.items.length > 0" class="grid grid-cols-[50px_1fr_1fr_40px] gap-1">
                    <jet-label class="font-bold">Index</jet-label>
                    <jet-label class="font-bold">Items</jet-label>
                    <jet-label class="font-bold">Equivalent</jet-label>
                </div>
            </div>
            <div class="grid grid-cols-[1fr] gap-1 pb-2">
                <template v-for="(Item, index) in add_edit_form.items" :key="index">
                    <div class="grid grid-cols-[50px_1fr_1fr_40px] gap-1">
                        <span class="pl-3 pt-2">{{ index + 1 }}</span>
                        <!-- <div class="flex mb-2 items-center border border-gray-300 rounded-md overflow-hidden w-full">
                        <s4-input color="ssrGray"
                            class="w-full py-2 px-3 text-sm text-gray-700 focus:ring-1 focus:ring-blue-500 focus:outline-none border-none"
                            placeholder="Item name" v-model="add_edit_form.items[index].name" :ref="(el) => {
                                itemRefs[index] = el;
                            }
                                ">
                        </s4-input>
                        <div
                            class="w-22 py-2 px-3 text-sm text-gray-700 bg-gray-50 border-l border-gray-300 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                            <ButtonIconsVue
                                class="h-4 w-4 group-hover:scale-125 hover:ease-in duration-300 hover:scale-125 cursor-pointer"
                                icon="gallery" @click="showGallery(index)" />
                        </div>

                    </div> -->
                        <TE2 placeholder="Enter items" :modelValue="add_edit_form.items[index].name" @update:modelValue="
                            (val) => {
                                add_edit_form.items[index].name = val;
                            }
                        " :ref="(el) => {
                            itemRefs[index] = el;
                        }
                            " @mousedown.stop></TE2>

                        <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                            v-model="add_edit_form.items[index].equivalent">
                        </s4-input>

                        <ButtonIconsVue icon="delete" @click="deleteChoice('items', index)" />
                        <!-- <s4-button color="ssrRedIcon" class="mb-1 ml-1" @click="deleteChoice(index)">
                        <font-awesome-icon class="fa-w-20 fa-h-20" :icon="faIcon.trash" />
                    </s4-button> -->
                    </div>
                </template>
            </div>
            <div class="col-span-12">
                <!-- <div class="flex mb-2 items-center border border-gray-300 rounded-md overflow-hidden w-full"> -->
                <!-- <s4-input color="ssrGray"
                        class="w-full py-2 px-3 text-sm text-gray-700 focus:ring-1 focus:ring-blue-500 focus:outline-none border-none"
                        placeholder="Input new item" v-model="new_item"></s4-input>



                    <div
                        class="w-22 py-2 px-3 text-sm text-gray-700 bg-gray-50 border-l border-gray-300 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <ButtonIconsVue
                            class="h-4 w-4 group-hover:scale-125 hover:ease-in duration-300 hover:scale-125 cursor-pointer"
                            icon="gallery" @click="showGallery(-1)" /> -->
                <TE2 v-if="!showItemTextArea" placeholder="Input new item" :modelValue="new_item" @update:modelValue="
                    (val) => {
                        new_item = val;
                    }
                "></TE2>

                <!-- </div> -->
                <!-- </div> -->
                <!-- <s4-button
                    v-if="showItemTextArea == false"
                    :theme="'ssrAddColRow'"
                    class="py-1 px-3 mb-10 w-32"
                    color="ssrB"
                    @click="showItemTextArea = !showItemTextArea"
                >
                    Multiple Items
                </s4-button> -->
                <button v-show="showItemTextArea == false" :theme="'ssrAddColRow'"
                    @click="showItemTextArea = !showItemTextArea"
                    class="inline-flex w-[160px] items-center px-2 py-1 mb-2 mt-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
                    <svg width="32" height="32" viewBox="0 0 980 980" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z"
                            fill="white" />
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z"
                            fill="white" />
                        <path
                            d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z"
                            stroke="white" />
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z"
                            stroke="white" />
                        <path d="M551 270V558M695 414H407" stroke="white" stroke-width="32" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>

                    <span class="transition-all duration-300 ease-in-out hover:text-white">
                        Add Multiple Items
                    </span>
                </button>
                <div class="w-full">
                    <!-- <s4-multiple-inputs :setup="setupMI"></s4-multiple-inputs> -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-1 col-span-12 w-full mt-2" v-show="showItemTextArea">
                        <div class="flex flex-col gap-1">
                            <jet-label class="text-xs font-semibold text-gray-600">Enter Items</jet-label>
                            <TE2 ref="itemAreaElem" placeholder="Enter items separated by new line..." :modelValue="itemArea"
                                height="158px" @update:modelValue="
                                    (val) => {
                                        itemArea = val;
                                    }
                                " v-show="showItemTextArea"></TE2>
                        </div>

                        <div class="flex flex-col gap-1">
                            <jet-label class="text-xs font-semibold text-gray-600">Enter Equivalent</jet-label>
                            <s4-text-area color="ssrGray" v-model="equivalentArea" :filter="true" class="w-full h-40"
                                placeholder="Enter equivalent separated by new line..." v-show="showItemTextArea"></s4-text-area>
                        </div>
                    </div>

                    <div class="flex mb-5" v-show="showItemTextArea">
                        <s4-button :theme="'ssrAddColRow'"
                            class="py-1 px-2 mt-2 !bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                            @click="splitForItem()">Add</s4-button>
                        <s4-button :theme="'ssrAddColRow'"
                            class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                            @click="cancelMultipleItems()">Cancel</s4-button>
                    </div>
                </div>
                <!-- <div class="col-span-12">
                    <div class="pt-1">

                        <TE2
                            v-show="showItemTextArea"
                            placeholder="Enter items separated by new line..."
                            :modelValue="itemArea"
                            height="150px"
                            @update:modelValue="
                                (val) => {
                                    itemArea = val;
                                }
                            "
                        ></TE2>
                    </div>
                    <div class="flex mb-10" v-show="showItemTextArea">
                        <s4-button
                            :theme="'ssrAddColRow'"
                            class="py-1 px-2 mt-2"
                            @click="splitForItem()"
                            >Add</s4-button
                        >
                        <s4-button
                            :theme="'ssrAddColRow'"
                            class="py-1 px-2 ml-1 mt-2"
                            @click="showItemTextArea = false"
                            >Cancel</s4-button
                        >
                    </div>
                </div> -->
            </div>
        </div>
    </div>

    <BorderSetup></BorderSetup>

    <div class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-1 options-layout-wrapper">
        <div class="col-span-12 md:col-span-6 grid grid-cols-1 md:grid-cols-6 gap-1">
            <div class="col-span-6">
                <label class="flex items-center cursor-pointer w-[110px]">
                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required" />
                    <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                    <Tooltip :imgSrc="question" preset="Required">
                    </Tooltip>
                </label>
                <!-- <div class="col-span-6 pl-6" v-if="add_edit_form.setup.validate.required">
                    <jet-label class="mt-2">Error Message:</jet-label>
                    <s4-input color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.validate.required == 0"
                        v-model="add_edit_form.setup.validate.errorMsg">
                    </s4-input>
                    <TE2 class="mt-2" placeholder="Error message" :modelValue="add_edit_form.setup.validate.errorMsg"
                        @update:modelValue="
                            (val) => {
                                add_edit_form.setup.validate.errorMsg = val;
                            }
                        "></TE2>
                </div> -->
            </div>

            <!-- <div class="col-span-3">
                <jet-label>Box style size:</jet-label>
                <s4-input
                    color="ssrGray"
                    class="w-full mb-1"
                    placeholder="e.g. md"
                    v-model="add_edit_form.setup.boxStyle.size"
                >
                </s4-input>
            </div> -->
            <!-- <div class="col-span-6">
                <div class="flex items-center">
                    <jet-label>Layout Width</jet-label>
                    <Tooltip :imgSrc="question"
                        label="Sets the maximum width of the content box. Adjust this setting to control how wide the content can appear on the page.">
                    </Tooltip>
                </div>
                <S4InputSlider color="ssrGray" :placeholder="'e.g. 100'" :units="['%', 'vmin', 'vw', 'auto']"
                    v-model.number="boxStyleValue" v-model:unitValue="boxStyleUnit" :allowAuto="true" min="0">
                </S4InputSlider>
            </div> -->
            <div class="col-span-6">
                    <label class="flex items-center cursor-pointer w-[170px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.randomItems
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Random Items</span>
                        <Tooltip :imgSrc="question" preset="RandomItems">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6 pl-6" v-if="add_edit_form.setup.validate.randomItems">
                    <vue-form-multiselect placeholder="Click to select items" :options="randomItems"
                        v-model="add_edit_form.setup.randomItems" @tag-removed="handleRandomItemsTagRemoved">
                    </vue-form-multiselect>
                </div>
            <div class="col-span-2 pl-6 w-[109%]">
                <jet-label>Number of Rating Stars</jet-label>
                <s4-input color="ssrGray" type="number" min="1" class="w-full mb-1"
                    v-model.number="add_edit_form.setup.size">
                </s4-input>
            </div>
            
            

        </div>

        <div class="col-span-12 md:col-span-6 mt-1">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-1">
                <!-- <div class="col-span-6">
                    <jet-label>Random Items:</jet-label>
                    <vue-form-multiselect
                        style="min-height: 8.27rem"
                        placeholder="Click to select items"
                        :options="randomItems"
                        v-model="add_edit_form.setup.randomItems"
                    />
                </div> -->
                <div class="col-span-6">
                <label class="items-center cursor-pointer flex items-center gap-0.5 w-[170px]">
                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.boxStyle['max-width_enabler']
                        " />
                    <span class="ml-2 text-sm text-gray-600 select-none">Edit Layout Width</span>
                    <Tooltip :imgSrc="question" preset="EditLayoutWidth">
                    </Tooltip>
                </label>
            </div>
            <div class="col-span-6 w-full grid grid-cols-1 md:grid-cols-3 gap-1 pl-0 md:pl-6">
                <div class="" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
                    <jet-label>Desktop</jet-label>
                    <!-- <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="e.g. 100%"
                                v-model="add_edit_form.setup.boxStyle['max-width']"
                            >
                            </s4-input> -->
                    <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                        :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width']"
                        :allowAuto="true" @update:modelValue="
                            (val) => {
                                add_edit_form.setup.boxStyle['max-width'] = val;
                            }
                        ">
                    </S4InputSlider2>
                </div>

                <div class="" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
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
                        :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width-tab'] ??
                            '100%'
                            " :allowAuto="true" @update:modelValue="
                                (val) => {
                                    add_edit_form.setup.boxStyle['max-width-tab'] =
                                        val;
                                }
                            ">
                    </S4InputSlider2>
                </div>
                <div class="" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
                    <jet-label>Mobile</jet-label>
                    <!-- <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="e.g. 100%"
                                v-model="add_edit_form.setup.boxStyle['max-width']"
                            >
                            </s4-input> -->

                    <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                        :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width-mobile'] ??
                            '100%'
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
        <teleport to="body">
            <WarningModal :show="showDeleteAllModal" @close="showDeleteAllModal = false">
                <template #header>
                    Confirm Deletion
                </template>
                <template #content>
                    Are you sure you want to delete <span class="font-semibold">all items</span>?
                    This action is irreversible.
                </template>
                <template #footer>
                    <button @click="deleteAllItems"
                        class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                        type="button">
                        Delete
                    </button>
                    <button @click="showDeleteAllModal = false"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
                        type="button">
                        Cancel
                    </button>
                </template>
            </WarningModal>
        </teleport>
        <s4-gallery-modal :type="'image'" :modal="gallery" @close="closeGallery()" @selectGallery="selectGallery" />
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

@media (max-width: 1090px) {
    .options-layout-wrapper {
        grid-template-columns: 1fr !important;
    }
}
</style>
