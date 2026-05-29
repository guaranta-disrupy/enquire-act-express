<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import S4Input from '@/S4/Input.vue';
import S4InputSlider2 from '@/S4/InputSlider2.vue';
import S4GallerySelect from '@/S4/GallerySelect.vue';
import BorderSetup from '@/S4/BorderSetup.vue';
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import { usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js';

import { inject, onBeforeMount, computed, ref, watch, reactive } from 'vue';
import JetInputError from '@/Jetstream/InputError.vue';



const add_edit_form = inject('add_edit_form')
const { VideoWithHeaders } = helpers();
const boxStyleValue = ref('');
const boxStyleUnit = ref('%');
//const for box style max-width
const maxWidthComputed = computed({
    get() {
        return boxStyleUnit.value === "auto"
            ? "auto"
            : `${boxStyleValue.value}${boxStyleUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            boxStyleValue.value = null;
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

// const layout = [
//     { value: 1, text: "Layout 1", image: "/images/layout 1.png" },
//     { value: 2, text: "Layout 2", image: "/images/layout 2.png" },
//     { value: 3, text: "Layout 3", image: "/images/layout 3.png" },
//     { value: 4, text: "Layout 4", image: "/images/layout 4.png" },
// ];

onBeforeMount(() => {
    if (('layout' in add_edit_form.setup) == false) {
        add_edit_form.setup['layout'] = 1
    }
    if (('layout_video' in add_edit_form.setup) == false) {
        add_edit_form.setup['layout_video'] = { src: "", width: "95vw", height: "95vh" }
    }
    if (('btn_delay' in add_edit_form.setup) == false) {
        add_edit_form.setup['btn_delay'] = 0
    }
    if (!('boxStyle' in add_edit_form.setup)) {
        add_edit_form.setup['boxStyle'] = { 'max-width': '100%' };
    }

    // Initialize the boxStyleValue and boxStyleUnit based on existing data
    const maxWidth = add_edit_form.setup.boxStyle['max-width'] || '100%';
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

    // Initialize layout video width
    const width = add_edit_form.setup.layout_video.width || 'auto';
    if (width === "auto") {
        layoutvideoWidthValue.value = null;
        layoutvideoWidthUnit.value = "auto";
    } else {
        const match = width.match(/^(\d+)([a-z%]+)$/);
        if (match) {
            layoutvideoWidthValue.value = parseFloat(match[1]);
            layoutvideoWidthUnit.value = match[2];
        }
    }

    // Initialize layout video height
    const height = add_edit_form.setup.layout_video.height || 'auto';
    if (height === "auto") {
        layoutvideoHeightValue.value = null;
        layoutvideoHeightUnit.value = "auto";
    } else {
        const match = height.match(/^(\d+)([a-z%]+)$/);
        if (match) {
            layoutvideoHeightValue.value = parseFloat(match[1]);
            layoutvideoHeightUnit.value = match[2];
        }
    }
})

//gallery

const gallery = reactive({
    show: false,
    index: -1,
});

const showGallery = () => {
    gallery.show = true;
};

const closeGallery = () => {
    gallery.show = false;
};

const selectGallery = (image) => {
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    add_edit_form.setup.layout_video.src = image.file_path
        ? `${fluentBase}/storage/${image.file_path}`
        : VideoWithHeaders(`${usePage().props.app_url}/gallery/${image.filename || image.file_name}`);
    closeGallery();
};


const layoutvideoWidthValue = ref(null);
const layoutvideoWidthUnit = ref('auto');

const layoutvideoWidth = computed({
    get() {
        return layoutvideoWidthUnit.value === "auto"
            ? "auto"
            : `${layoutvideoWidthValue.value}${layoutvideoWidthUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            layoutvideoWidthValue.value = null;
            layoutvideoWidthUnit.value = "auto";
        } else {
            const match = newValue.match(/^(\d+)([a-z%]+)$/);
            if (match) {
                layoutvideoWidthValue.value = parseFloat(match[1]);
                layoutvideoWidthUnit.value = match[2];
            }
        }
    }
});

const layoutvideoHeightValue = ref(null);
const layoutvideoHeightUnit = ref('auto');

const layoutvideoHeight = computed({
    get() {
        return layoutvideoHeightUnit.value === "auto"
            ? "auto"
            : `${layoutvideoHeightValue.value}${layoutvideoHeightUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            layoutvideoHeightValue.value = null;
            layoutvideoHeightUnit.value = "auto";
        } else {
            const match = newValue.match(/^(\d+)([a-z%]+)$/);
            if (match) {
                layoutvideoHeightValue.value = parseFloat(match[1]);
                layoutvideoHeightUnit.value = match[2];
            }
        }
    }
});

watch(layoutvideoWidth, (newValue) => {
    if (add_edit_form?.setup?.layout_video) {
        add_edit_form.setup.layout_video.width = newValue;
    }
});

watch(layoutvideoHeight, (newValue) => {
    if (add_edit_form?.setup?.layout_video) {
        add_edit_form.setup.layout_video.height = newValue;
    }
});

// Video source validation error
const videoSourceError = ref("");

// Watch video source to clear error when filled
watch(() => add_edit_form?.setup?.layout_video?.src, (newValue) => {
    if (newValue && newValue.trim() !== "" && videoSourceError.value) {
        videoSourceError.value = "";
    }
});

// Expose error state to parent component
defineExpose({
    videoSourceError
});

</script>
<template>

    <div class="pt-5">
        <BorderSetup></BorderSetup>

        <div class="grid grid-cols-12 gap-4">
            <!-- Video source (row 1, left) -->
            <div class="col-span-4">
                <jet-label>Video</jet-label>
                <S4GallerySelect
                    placeholder="Video source"
                    class="w-full"
                    v-model="add_edit_form.setup.layout_video.src"
                    :showGallery="showGallery"
                />
                <JetInputError :message="videoSourceError" class="mt-1" />
            </div>

            <!-- Layout width (row 1, middle) -->
            <div class="col-span-4">
                <div class="flex items-center">
                    <jet-label>Layout Width</jet-label>
                    <Tooltip
                        :imgSrc="question"
                        label="  Sets the maximum width of the content box. Adjust this setting to control how wide the content can appear on the page."
                    >
                    </Tooltip>
                    <!-- <div class="relative group">
                        <svg class="cursor-pointer" width="23" viewBox="0 0 556 556" fill="none"
                            xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                            <rect x="127.488" y="125.609" width="309.025" height="309.025" fill="url(#descrip1)" />
                            <rect x="71.8633" y="69.9854" width="420.273" height="420.273" fill="url(#descrip2)" />
                            <defs>
                                <pattern id="descrip1" patternContentUnits="objectBoundingBox" width="1" height="1">
                                    <use xlink:href="#descriptive2" transform="scale(0.00333333)" />
                                </pattern>
                                <pattern id="descrip2" patternContentUnits="objectBoundingBox" width="1" height="1">
                                    <use xlink:href="#descriptive1" transform="scale(0.00333333)" />
                                </pattern>
                                <image id="descriptive2" width="300" height="300" preserveAspectRatio="none"
                                    xlink:href="data:image/png;base64,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" />
                                <image id="descriptive1" width="300" height="300" preserveAspectRatio="none"
                                    xlink:href="data:image/png;base64,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" />
                            </defs>

                        </svg>
                        <span
                            class="absolute left-1/2 -translate-x-1/8 top-full mt-[-9vmin] z-[200] pl-1 w-[160px] py-1 bg-[#fffde0] text-black text-[9px] leading-[1.5] border border-solid border-black rounded hidden group-hover:block transition-opacity font-[Poppins]">
                            Sets the maximum width of the content box. Adjust this setting to control how wide the
                            content
                            can appear on the page.
                        </span>
                    </div> -->
                </div>
                <S4InputSlider2
                    color="ssrGray"
                    :placeholder="'e.g. 100'"
                    :units="['%', 'vmin', 'vw', 'auto']"
                    v-model="maxWidthComputed"
                    :allowAuto="true"
                    min="0"
                />
            </div>

            <!-- Button delay (row 1, right) -->
            <div class="col-span-4">
                <div class="block whitespace-nowrap">
                    <jet-label>Button Delay (in seconds)</jet-label>
                </div>
                <s4-input
                    color="ssrGray"
                    type="number"
                    class="w-full mb-1"
                    v-model="add_edit_form.setup.btn_delay"
                >
                </s4-input>
            </div>

            <!-- Video Width (row 2, under Video) -->
            <div class="col-span-4">
                <jet-label>Video Width</jet-label>
                <S4InputSlider2
                    color="ssrGray"
                    :units="['%', 'vmin', 'vw', 'auto']"
                    v-model="layoutvideoWidth"
                    :allowAuto="true"
                    min="0"
                />
            </div>

            <!-- Video Height (row 2, under Layout Width) -->
            <div class="col-span-4">
                <jet-label>Video Height</jet-label>
                <S4InputSlider2
                    color="ssrGray"
                    :units="['%', 'vmin', 'vh', 'auto']"
                    v-model="layoutvideoHeight"
                    :allowAuto="true"
                    min="0"
                />
            </div>

            <!-- Spacer (row 2, under Button Delay) -->
            <div class="col-span-4"></div>
        </div>

        <s4-gallery-modal
            :modal="gallery"
            :type="'video'"
            @close="closeGallery()"
            @selectGallery="selectGallery"
        />
    </div>

</template>
