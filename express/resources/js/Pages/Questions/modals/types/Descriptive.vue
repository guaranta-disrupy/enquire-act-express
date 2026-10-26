<script setup>
import { withBase } from '@/utils/basePath';
import JetLabel from "@/Jetstream/Label.vue";
import S4Input from "@/S4/Input.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";
import S4ImageSelect from "@/S4/ImageSelect.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import { usePage } from "@inertiajs/vue3";
import ButtonIconsVue from "@/S4/ButtonIcons.vue"; // Import ButtonIconsVue
import S4Checkbox from "@/S4/Checkbox.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";

import { inject, onBeforeMount, computed, ref, watch, reactive } from "vue";
import helpers from "@/helpers.js";
const { ImageWithHeaders } = helpers();
const add_edit_form = inject("add_edit_form");
const boxStyleValue = ref("");
const boxStyleUnit = ref("%");
//const for box style max-width
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

// layout width and height
const layoutImgWidthValue = ref(
    parseFloat(add_edit_form.setup.layout_img.width)
);
const layoutImgWidthUnit = ref(
    add_edit_form.setup.layout_img.width.replace(/[0-9.]/g, "") || "vmin"
);

const layoutImgHeightValue = ref(
    parseFloat(add_edit_form.setup.layout_img.height)
);
const layoutImgHeightUnit = ref(
    add_edit_form.setup.layout_img.height.replace(/[0-9.]/g, "") || "vmin"
);

const layoutImgWidth = computed({
    get() {
        return layoutImgWidthUnit.value === "auto"
            ? "auto"
            : `${layoutImgWidthValue.value}${layoutImgWidthUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            layoutImgWidthValue.value = null; // Clear numeric value
            layoutImgWidthUnit.value = "auto";
        } else {
            const match = newValue.match(/^(\d+)([a-z%]+)$/);
            if (match) {
                layoutImgWidthValue.value = parseFloat(match[1]);
                layoutImgWidthUnit.value = match[2];
            }
        }
    },
});

// Layout Image Height Computed Property
const layoutImgHeight = computed({
    get() {
        return layoutImgHeightUnit.value === "auto"
            ? "auto"
            : `${layoutImgHeightValue.value}${layoutImgHeightUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            layoutImgHeightValue.value = null; // Clear numeric value
            layoutImgHeightUnit.value = "auto";
        } else {
            const match = newValue.match(/^(\d+)([a-z%]+)$/);
            if (match) {
                layoutImgHeightValue.value = parseFloat(match[1]);
                layoutImgHeightUnit.value = match[2];
            }
        }
    },
});

watch(layoutImgWidth, (newValue) => {
    add_edit_form.setup.layout_img.width = newValue;
});

watch(layoutImgHeight, (newValue) => {
    add_edit_form.setup.layout_img.height = newValue;
});

const layout = [
    { value: 1, text: "Layout 1", image: withBase("/images/layout1.png") },
    { value: 2, text: "Layout 2", image: withBase("/images/layout2.png") },
    { value: 3, text: "Layout 3", image: withBase("/images/layout3.png") },
    { value: 4, text: "Layout 4", image: withBase("/images/layout4.png") },
];

onBeforeMount(() => {
    if ("layout" in add_edit_form.setup == false) {
        add_edit_form.setup["layout"] = 1;
    }
    if ("layout_img" in add_edit_form.setup == false) {
        add_edit_form.setup["layout_img"] = {
            src: "",
            width: "40vmin",
            height: "40vmin",
        };
    }
    if ("btn_delay" in add_edit_form.setup == false) {
        add_edit_form.setup["btn_delay"] = 0;
    }
    if ("btn_delay_enabled" in add_edit_form.setup == false) {
        add_edit_form.setup["btn_delay_enabled"] = false;
    }
    if ("btn_autoNext" in add_edit_form.setup == false) {
        add_edit_form.setup["btn_autoNext"] = 0;
    }
    if ("btn_autoNext_enabled" in add_edit_form.setup == false) {
        add_edit_form.setup["btn_autoNext_enabled"] = false;
    }
    if (!("boxStyle" in add_edit_form.setup)) {
        add_edit_form.setup["boxStyle"] = { "max-width": "60vmin" };
    }

    // Ensure Edit Layout Width is enabled by default (consistent with other components)
    if (!add_edit_form.setup.boxStyle["max-width_enabler"]) {
        add_edit_form.setup.boxStyle["max-width_enabler"] = true;
    }

    // Initialize the boxStyleValue and boxStyleUnit based on existing data
    const maxWidth = add_edit_form.setup.boxStyle["max-width"] || "60vmin";
    if (maxWidth === "auto") {
        boxStyleValue.value = null; // No numeric value for "auto"
        boxStyleUnit.value = "auto";
    } else {
        const match = maxWidth.match(/^(\d+)([a-z%]+)$/);
        if (match) {
            boxStyleValue.value = parseFloat(match[1]);
            boxStyleUnit.value = match[2];
        }
    }
});

//gallery

const gallery = reactive({
    show: false,
    index: -1,
});

const showGallery = (index) => {
    gallery.index = index;
    gallery.show = true;
};

const closeGallery = () => {
    gallery.show = false;
};

const selectGallery = (image) => {
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    add_edit_form.setup.layout_img.src = image.file_path
        ? `${fluentBase}/storage/${image.file_path}`
        : ImageWithHeaders(
            `${usePage().props.app_url}/gallery/${image.filename || image.file_name
            }`
        );
    closeGallery();
};
const showImage = computed(() => {
    return [2, 3, 4].includes(add_edit_form.setup.layout);
});

watch(maxWidthComputed, (newValue) => {
    add_edit_form.setup.boxStyle["max-width"] = newValue;
});
watch(
    () => add_edit_form.setup.layout,
    (newValue) => {
        add_edit_form.setup.layout = newValue;
    }
);

// Watch for Button Delay checkbox changes
watch(
    () => add_edit_form.setup.btn_delay_enabled,
    (newValue) => {
        if (newValue) {
            // If Button Delay is enabled, disable Button Auto Next
            add_edit_form.setup.btn_autoNext_enabled = false;
            // Clear auto next value
            add_edit_form.setup.btn_autoNext = 0;
        } else {
            // If Button Delay is disabled, clear its value to prevent conflicts
            add_edit_form.setup.btn_delay = 0;
        }
    }
);

// Watch for Button Auto Next checkbox changes
watch(
    () => add_edit_form.setup.btn_autoNext_enabled,
    (newValue) => {
        if (newValue) {
            // If Button Auto Next is enabled, disable Button Delay
            add_edit_form.setup.btn_delay_enabled = false;
            // Clear delay value
            add_edit_form.setup.btn_delay = 0;
        } else {
            // If Button Auto Next is disabled, clear its value to prevent conflicts
            add_edit_form.setup.btn_autoNext = 0;
        }
    }
);

// Function to safely apply button delay functionality
const applyButtonDelay = () => {
    // Only apply if this functionality is enabled and the other is not
    if (
        add_edit_form.setup.btn_delay_enabled &&
        !add_edit_form.setup.btn_autoNext_enabled
    ) {
        const delay = add_edit_form.setup.btn_delay;
        if (delay && delay > 0) {
            // Apply button delay logic here
            console.log("Button Delay applied:", delay);
        }
    }
};

// Function to safely apply button auto-next functionality
const applyButtonAutoNext = () => {
    // Only apply if this functionality is enabled and the other is not
    if (
        add_edit_form.setup.btn_autoNext_enabled &&
        !add_edit_form.setup.btn_delay_enabled
    ) {
        const delay = add_edit_form.setup.btn_autoNext;
        if (delay && delay > 0) {
            // Apply button auto-next logic here
            console.log("Button Auto Next applied:", delay);
        }
    }
};

// Watch for changes in btn_delay to apply functionality safely
watch(
    () => add_edit_form.setup.btn_delay,
    (newValue) => {
        if (newValue && newValue > 0) {
            applyButtonDelay();
        }
    }
);

// Watch for changes in btn_autoNext to apply functionality safely
watch(
    () => add_edit_form.setup.btn_autoNext,
    (newValue) => {
        if (newValue && newValue > 0) {
            applyButtonAutoNext();
        }
    }
);
</script>
<template>
    <div class="pt-5">
        <BorderSetup></BorderSetup>

        <div class="mt-4">
            <!-- Options & Layout: stack as list view below xl, two columns only on xl+ -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-1">
                <!-- Column 1: Change Layout + ImageSelect + Button Delay/Auto-next -->
                <div class="mt-1 space-y-2 xl:col-span-6">
                    <div class="w-[30vmin]">
                        <label class="flex items-center cursor-pointer">
                            <s4-checkbox
                                class="cursor-pointer"
                                v-model:checked="add_edit_form.setup.layout"
                            />
                            <span class="ml-2 text-sm text-gray-600 select-none">Change Layout</span>
                        </label>
                    </div>

                    <S4ImageSelect
                        v-if="add_edit_form.setup.layout"
                        :lists="layout"
                        v-model.number="add_edit_form.setup.layout"
                        class="w-[30vmin] ml-6"
                    />

                    <!-- Button Delay + Auto-next (stacked vertically) -->
                    <div class="mt-2 grid grid-cols-1 gap-2">
                        <!-- Button Delay -->
                        <div class="w-[30vmin]">
                            <label class="flex items-center cursor-pointer overflow-hidden">
                                <s4-checkbox
                                    class="cursor-pointer"
                                    v-model:checked="add_edit_form.setup.btn_delay_enabled"
                                    :disabled="add_edit_form.setup.btn_autoNext_enabled"
                                />
                                <span
                                    class="ml-2 text-sm text-gray-600 select-none whitespace-nowrap"
                                >
                                    Button Delay (in seconds)
                                </span>
                            </label>
                            <s4-input
                                v-if="add_edit_form.setup.btn_delay_enabled"
                                color="ssrGray"
                                type="number"
                                class="w-full mt-2 ml-6"
                                v-model="add_edit_form.setup.btn_delay"
                            >
                            </s4-input>
                        </div>

                        <!-- Auto-next -->
                        <div class="w-[30vmin]">
                            <label class="flex items-center cursor-pointer overflow-hidden">
                                <s4-checkbox
                                    class="cursor-pointer"
                                    v-model:checked="add_edit_form.setup.btn_autoNext_enabled"
                                    :disabled="add_edit_form.setup.btn_delay_enabled"
                                />
                                <span
                                    class="ml-2 text-sm text-gray-600 select-none whitespace-nowrap"
                                >
                                    Auto-next (in seconds)
                                </span>
                            </label>
                            <s4-input
                                v-if="add_edit_form.setup.btn_autoNext_enabled"
                                color="ssrGray"
                                type="number"
                                class="w-full mt-2 ml-6"
                                v-model="add_edit_form.setup.btn_autoNext"
                            >
                            </s4-input>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Edit Layout Width + Image Source / Edit Image Dimension -->
                <div class="mt-1 space-y-2 xl:col-span-6">
                    <!-- Apply Layout width -->
                    <div class="grid grid-cols-6 gap-1 mt-2">
                        <div class="grid grid-cols-6 gap-1 col-span-12">
                            <div class="col-span-6">
                                <label class="cursor-pointer flex items-center gap-0.5 w-[170px]">
                                    <s4-checkbox
                                        class="cursor-pointer"
                                        v-model:checked="add_edit_form.setup.boxStyle['max-width_enabler']"
                                    />
                                    <span class="ml-2 text-sm text-gray-600 select-none">
                                        Edit Layout Width
                                    </span>
                                    <Tooltip :imgSrc="question" preset="EditLayoutWidth"></Tooltip>
                                </label>
                            </div>

                            <div
                                class="col-span-2 ml-6"
                                v-if="add_edit_form.setup.boxStyle['max-width_enabler']"
                            >
                                <jet-label>Desktop</jet-label>
                                <S4InputSlider2
                                    color="ssrGray"
                                    class="w-full mt-[3px]"
                                    :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']"
                                    :modelValue="add_edit_form.setup.boxStyle['max-width']"
                                    :allowAuto="true"
                                    @update:modelValue="val => { add_edit_form.setup.boxStyle['max-width'] = val; }"
                                >
                                </S4InputSlider2>
                            </div>

                            <div
                                class="col-span-2"
                                v-if="add_edit_form.setup.boxStyle['max-width_enabler']"
                            >
                                <jet-label>Tablet</jet-label>
                                <S4InputSlider2
                                    color="ssrGray"
                                    class="w-full mt-[3px]"
                                    :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']"
                                    :modelValue="add_edit_form.setup.boxStyle['max-width-tab'] ?? '100%'"
                                    :allowAuto="true"
                                    @update:modelValue="val => { add_edit_form.setup.boxStyle['max-width-tab'] = val; }"
                                >
                                </S4InputSlider2>
                            </div>

                            <div
                                class="col-span-2"
                                v-if="add_edit_form.setup.boxStyle['max-width_enabler']"
                            >
                                <jet-label>Mobile</jet-label>
                                <S4InputSlider2
                                    color="ssrGray"
                                    class="w-full mt-[3px]"
                                    :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']"
                                    :modelValue="add_edit_form.setup.boxStyle['max-width-mobile'] ?? '100%'"
                                    :allowAuto="true"
                                    @update:modelValue="val => { add_edit_form.setup.boxStyle['max-width-mobile'] = val; }"
                                >
                                </S4InputSlider2>
                            </div>
                        </div>
                    </div>

                    <!-- Image Source block -->
                    <div v-if="showImage">
                        <div class="grid grid-cols-6 gap-1">
                            <div class="grid grid-cols-6 gap-1 col-span-12">
                                <div class="col-span-6">
                                    <jet-label>Image</jet-label>
                                </div>
                                <div
                                    class="col-span-6 flex items-center border border-gray-300 rounded-md overflow-hidden w-full"
                                >
                                    <S4Input
                                        color="ssrGray"
                                        class="w-full py-2 px-3 text-sm text-gray-700 focus:ring-1 focus:ring-blue-500 focus:outline-none border-none"
                                        placeholder="Image source"
                                        v-model="add_edit_form.setup.layout_img.src"
                                    >
                                    </S4Input>
                                    <div
                                        class="w-22 py-2 px-3 text-sm text-gray-700 bg-gray-50 border-l border-gray-300 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    >
                                        <div
                                            v-if="add_edit_form.setup.layout_img.src"
                                            :style="{
                                                backgroundImage: `url(${add_edit_form.setup.layout_img.src})`,
                                                backgroundSize: 'contain',
                                                backgroundPosition: 'center',
                                                backgroundRepeat: 'no-repeat',
                                                width: '70px',
                                                height: '30px',
                                                padding: '5px',
                                                boxSizing: 'border-box',
                                            }"
                                            class="cursor-pointer group-hover:scale-125 hover:ease-in duration-300 hover:scale-125"
                                            @click="showGallery()"
                                        ></div>
                                        <ButtonIconsVue
                                            v-else
                                            class="h-4 w-4 group-hover:scale-125 hover:ease-in duration-300 hover:scale-125 cursor-pointer"
                                            icon="gallery"
                                            @click="showGallery()"
                                        />
                                    </div>
                                </div>

                                <div class="col-span-6">
                                    <label class="flex items-center cursor-pointer">
                                        <s4-checkbox
                                            class="cursor-pointer"
                                            v-model:checked="add_edit_form.setup.layout_img['image_dimension_enabler']"
                                        />
                                        <span class="ml-2 text-sm text-gray-600 select-none">
                                            Edit Image Dimension
                                        </span>
                                    </label>
                                </div>

                                <div
                                    class="col-span-2"
                                    v-if="add_edit_form.setup.layout_img['image_dimension_enabler']"
                                >
                                    <jet-label>Desktop</jet-label>
                                    <S4InputSlider2
                                        color="ssrGray"
                                        class="w-full mt-[3px]"
                                        :placeholder="'e.g. 100'"
                                        :units="['%', 'vmin', 'vw', 'auto']"
                                        :modelValue="add_edit_form.setup.layout_img['image_dimension'] ?? '50vmin'"
                                        :allowAuto="true"
                                        @update:modelValue="val => { add_edit_form.setup.layout_img['image_dimension'] = val; }"
                                    >
                                    </S4InputSlider2>
                                </div>

                                <div
                                    class="col-span-2"
                                    v-if="add_edit_form.setup.layout_img['image_dimension_enabler']"
                                >
                                    <jet-label>Tablet</jet-label>
                                    <S4InputSlider2
                                        color="ssrGray"
                                        class="w-full mt-[3px]"
                                        :placeholder="'e.g. 100'"
                                        :units="['%', 'vmin', 'vw', 'auto']"
                                        :modelValue="add_edit_form.setup.layout_img['image_dimension_tab'] ?? '50vmin'"
                                        :allowAuto="true"
                                        @update:modelValue="val => { add_edit_form.setup.layout_img['image_dimension_tab'] = val; }"
                                    >
                                    </S4InputSlider2>
                                </div>

                                <div
                                    class="col-span-2"
                                    v-if="add_edit_form.setup.layout_img['image_dimension_enabler']"
                                >
                                    <jet-label>Mobile</jet-label>
                                    <S4InputSlider2
                                        color="ssrGray"
                                        class="w-full mt-[3px]"
                                        :placeholder="'e.g. 100'"
                                        :units="['%', 'vmin', 'vw', 'auto']"
                                        :modelValue="add_edit_form.setup.layout_img['image_dimension_mobile'] ?? '50vmin'"
                                        :allowAuto="true"
                                        @update:modelValue="val => { add_edit_form.setup.layout_img['image_dimension_mobile'] = val; }"
                                    >
                                    </S4InputSlider2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- for testing only -->
        <s4-gallery-modal
            :modal="gallery"
            :type="'image'"
            @close="closeGallery()"
            @selectGallery="selectGallery"
        ></s4-gallery-modal>
    </div>
</template>
