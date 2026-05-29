<script setup>
// import { faTrashAlt, faEdit, faLink, faPlus } from '@fortawesome/free-solid-svg-icons';
import { onMounted, onUnmounted, ref, } from '@vue/runtime-core';
import { usePage } from "@inertiajs/vue3";
import Tooltip from '@/S4/Tooltip.vue';
import helpers from '@/helpers.js';
const { ImageWithHeaders, VideoWithHeaders } = helpers();
const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;

const props = defineProps([
    "itemData",
    // "getLink",
    // "showModal",
    "index",
    'functions',
    "copyValue",
    "typeFile",
    "isSelected", // Add this prop for checkbox state
])

const emit = defineEmits(['selectionChange']);

const toggleSelection = () => {
    emit('selectionChange', props.itemData.id, !props.isSelected);
}


onMounted(() => {
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    const filePath = props.itemData.file_path
        ? `${fluentBase}/storage/${props.itemData.file_path}`
        : usePage().props.app_url + '/gallery/' + (props.itemData.filename || props.itemData.file_name);
    imageSize(filePath);
    window.addEventListener(`gallery.image.update:${props.itemData.id}`, onUpdate);
})

onUnmounted(() => {
    window.removeEventListener(`gallery.image.update:${props.itemData.id}`, onUpdate);
})

const fileName = ref("")
const dimensions = ref("")
const fileSIZE = ref("")
// const faIcon = ref({ trash: faTrashAlt, edit: faEdit, link: faLink, plus: faPlus })


const onUpdate = () => {
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    const filePath = props.itemData.file_path
        ? `${fluentBase}/storage/${props.itemData.file_path}`
        : usePage().props.app_url + '/gallery/' + (props.itemData.filename || props.itemData.file_name);
    imageSize(filePath);
}

const imageSize = async (url) => {
    var img = new Image();
    img.src = url;
    let _dimensions = "";
    img.onload = () => {
        _dimensions = Number(img.width).toLocaleString() + 'x' + Number(img.height).toLocaleString();
        dimensions.value = _dimensions;
    };
    
    // Use file_size from config if available, otherwise fetch it
    let fileSize = null;
    if (props.itemData.config && props.itemData.config.file_size) {
        // file_size is in bytes, convert to KB
        fileSize = (props.itemData.config.file_size / 1024).toFixed(2);
    } else {
        try {
            let blob = await fetch(url).then((res) => res.blob());
            fileSize = (blob.size / 1024).toFixed(2);
        } catch (err) {
            console.log(err);
            fileSize = "unknown";
        }
    }

    fileSIZE.value = Number(fileSize).toLocaleString();
    fileName.value = props.itemData.config && props.itemData.config.title
        ? props.itemData.config.title
        : (props.itemData.file_name || props.itemData.originalName);
}



</script>

<template>
    <div
        class="hasImage [box-shadow:0_0px_12px_rgba(0,0,0,.1),0_2px_12px_rgba(0,0,0,.1)] grid grid-cols-1 w-full bg-white rounded-xl focus:outline-none focus:shadow-outline hover:bg-gray-100 cursor-pointer relative transition-colors duration-200">
        
        <!-- Checkbox in top left corner -->
        <!-- <div class="absolute left-1.5 z-10">
            <input 
                type="checkbox" 
                :checked="isSelected"
                @change="toggleSelection"
                class="w-4 h-4 text-[var(--mcolor)] bg-gray-100 border-gray-300 rounded focus:ring-[var(--mcolor2)] focus:ring-2 cursor-pointer"
            />
        </div> -->
        
        <img
            v-if="typeFile == 'Image'"
            class="object-contain rounded-[12px] p-1 w-full h-[150px]"
            :src="itemData.file_path
                ? fluentBase + '/storage/' + itemData.file_path
                : ImageWithHeaders(usePage().props.app_url + '/gallery/' + (itemData.filename || itemData.file_name))
            "
        />
        <video class="object-contain rounded-[12px] p-1 w-full h-[150px]" v-else controls muted>
            <source
                :src="itemData.file_path
                    ? fluentBase + '/storage/' + itemData.file_path
                    : VideoWithHeaders(usePage().props.app_url + '/gallery/' + (itemData.filename || itemData.file_name))
                "
                type="video/mp4"
            />
        </video>

        <section class="w-full">
            <div class="w-full">
                <div class="w-full flex justify-center items-center text-center gap-1">
                    <div class="">
                        <button id="button1" class="focus:outline-none p-2 rounded-xl bg-[var(--mcolor)] text-white"
                            @click="functions.getLink(
                                (itemData.file_path
                                    ? '/' + itemData.file_path
                                    : '/gallery/' + (itemData.filename || itemData.file_name)
                                ),
                                '#testing-code'
                            )">

                            <Tooltip label="Copy Link" :imgWidth="'100%'" :hoverWidth="'auto'"
                                :xTransformClass="'translate(-50%,-120%)'">
                                <template #svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                    </svg>
                                </template>
                            </Tooltip>
                        </button>
                    </div>
                    <div class="">
                        <button id="button2" class="focus:outline-none p-2 rounded-xl bg-[var(--mcolor)] text-white"
                            @click="functions.getLink(
                                (itemData.file_path
                                    ? '/' + itemData.file_path
                                    : '/gallery/' + (itemData.filename || itemData.file_name)
                                ),
                                '#tag-code'
                            )">
                            <Tooltip label="Copy Code" :imgWidth="'100%'" :hoverWidth="'auto'"
                                :xTransformClass="'translate(-50%,-130%)'">
                                <template #svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                                    </svg>
                                </template>
                            </Tooltip>

                        </button>
                    </div>
                    <!-- <div class="">
                        <button id="button3" class="focus:outline-none p-2 rounded-xl bg-[var(--mcolor)] text-white"
                            @click="functions.showModal(index)">
                            <Tooltip label="Replace" :imgWidth="'100%'" :hoverWidth="'auto'"
                                :xTransformClass="'translate(-50%,-130%)'">
                                <template #svg>
                                
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </template>
                            </Tooltip>

                        </button>
                    </div> -->
                    <!-- <div class="">
                        <button id="button4" class="focus:outline-none p-2 rounded-xl bg-[var(--mcolor)] text-white"
                            @click="functions.showDeleteModalHandler(true, props.itemData)">
                            <Tooltip label="Delete" :imgWidth="'100%'" :hoverWidth="'auto'"
                                :xTransformClass="'translate(-50%,-130%)'">
                                <template #svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </template>
                            </Tooltip>

                        </button>
                    </div> -->
                </div>
            </div>

            <div class="font-normal description rounded-md">
                <div class="ml-3 text-[11px] ">
                    <!-- <p>File Name: {{ fileName }}</p> -->
                    <p class="font-semibold truncate w-full">{{ fileName }}</p>
                    <p class="text-gray-500">Dimensions: {{ dimensions }} </p>
                    <p class="text-gray-500">Size: {{ fileSIZE }} kB</p>
                </div>
            </div>

        </section>
        <input type="hidden" id="testing-code" :value="copyValue">
        <input type="hidden" id="tag-code" :value="`<img src=&quot;${copyValue}&quot; />`">


        <!-- <div class="border-y p-4 flex gap-3">
            <div  @click="functions.getLink('/gallery/'+itemData.filename,'#testing-code')" class="text-ssr-orange2 cursor-pointer duration-150 transition-all hover:scale-125">
                <IconsVue icon="link"/> 
            </div>
            <div @click="functions.getLink('/gallery/'+itemData.filename,'#tag-code')" class="text-ssr-orange2 cursor-pointer duration-150 transition-all hover:scale-125">
                <IconsVue icon="code"/> 
            </div>
            <div @click="functions.showModal(index)" class="text-ssr-orange2 cursor-pointer duration-150 transition-all hover:scale-125">
                <IconsVue icon="pencil"/> 
            </div>
            <div @click="functions.showDeleteModalHandler(true,props.itemData)" class="text-ssr-orange2 cursor-pointer duration-150 transition-all hover:scale-125">
                <IconsVue icon="delete"/> 
            </div>
        </div> -->
    </div>
</template>



<style>
.hasImage {
    /* height: 160px !important; */
}

/* .hasImage:hover section {
    background-color: #00000095;
    transition: 0.3s;
} 

.hasImage:hover .description {
    background: #00000080;
} */

.hasImage:hover button {
    transition: 0.3s;
}

/* .hasImage:hover button {
    background: #FCCA27;
    color: #292929;
} */

.hasImage:hover button:hover {
    background: #ffedb2;
}

.hasImage:hover #button1 {
    background: #e0f2fe;
    color: #38bdf8;
}

.hasImage:hover #button2 {
    background: #dcfce7;
    color: #4ade80;
}

.hasImage:hover #button3 {
    background: #f3e8ff;
    color: #c084fc;
}

.hasImage:hover #button4 {
    background: #ffe4e6;
    color: #fb7185;
}

/* hover on button */
.hasImage:hover #button1:hover {
    background: #bae6fd;
}

.hasImage:hover #button2:hover {
    background: #bbf7d0;
}

.hasImage:hover #button3:hover {
    background: #e9d5ff;
}

.hasImage:hover #button4:hover {
    background: #fecdd3;
}


/* .gal_img {
    height: auto;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    max-width: auto;
    max-height: 100%;
    margin: auto !important;
    position: absolute;
} */
</style>
