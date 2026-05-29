<script setup>
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome'
import {faTrashAlt,faEdit,faLink,faPlus} from '@fortawesome/free-solid-svg-icons';
import { onMounted, onUnmounted, ref, computed } from '@vue/runtime-core';
import { usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js';
const { VideoWithHeaders } = helpers();

const props = defineProps([
    "itemData", 
    // "getLink",
    // "showModal",
    "index",
    'functions',
    "copyValue",
    "typeFile"
])

// Computed properties for media sources
const imageSrc = computed(() => {
    return props.itemData.file_path
        ? `${import.meta.env.VITE_APP_FLUENT_URL}/storage/${props.itemData.file_path}`
        : usePage().props.app_url + '/gallery/' + (props.itemData.filename || props.itemData.file_name);
});

const videoSrc = computed(() => {
    return props.itemData.file_path
        ? `${import.meta.env.VITE_APP_FLUENT_URL}/storage/${props.itemData.file_path}`
        : VideoWithHeaders(usePage().props.app_url + '/gallery/' + (props.itemData.filename || props.itemData.file_name));
});

const galleryPath = computed(() => {
    return props.itemData.file_path 
        ? '/' + props.itemData.file_path 
        : '/gallery/' + (props.itemData.filename || props.itemData.file_name);
});

onMounted(()=>{
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    const filePath = props.itemData.file_path
        ? `${fluentBase}/storage/${props.itemData.file_path}`
        : usePage().props.app_url + '/gallery/' + (props.itemData.filename || props.itemData.file_name);
    imageSize(filePath);
    window.addEventListener(`gallery.image.update:${props.itemData.id}`, onUpdate);
})

onUnmounted(()=>{
    window.removeEventListener(`gallery.image.update:${props.itemData.id}`, onUpdate);
})

 
const dimensions = ref("")
const fileSIZE = ref("")
const faIcon = ref({trash: faTrashAlt, edit: faEdit,link: faLink,plus: faPlus})


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
    let  _dimensions = "";
    img.onload = () => {
        _dimensions = Number(img.width).toLocaleString() + 'x' + Number(img.height).toLocaleString();
        dimensions.value = _dimensions;
    };
    // console.log(img)
    let fileSize = null;
    try {
        let blob = await fetch(url).then((res) => res.blob());
        fileSize = (blob.size / 1024).toFixed(2);
    } catch (err) {
        console.log(err);
        fileSize = "unknown";
    }

     fileSIZE.value = Number(fileSize).toLocaleString();
}
 
</script>

<template>
    <div class="bg-white item border-2 border-grey-800 rounded-lg transform hover:scale-100 transition ease-out duration-300 container flex justify-center">
        <img v-if="typeFile == 'Image'" class="gal_img" :src="imageSrc" />
        <video v-else controls autoplay loop>
            <source :src="videoSrc" type="video/mp4"/>
        </video>
        <div class="overlay">
            <div class="btn">
                <h1>{{typeFile}}</h1>
                <button type="button" class="transform hover:scale-110 border-3 p-1 mr-7 galBtn w-12 h-12"
                    @click="functions.getLink(galleryPath)">
                    <span class="toolTipCopy absolute -right-6 px-2 py-1 text-md text-white leading-none transform bg-gray-600 rounded-md h-6 pb-1 pt-1 pl-5 pr-5 -mt-10">
                        Copy
                    </span> 
                    <span
                        class="flex items-center justify-center text-2xl text-grey-300 transform hover:scale-125 transition ease-out duration-300">
                        <font-awesome-icon :icon="faIcon.link" class="fill-current text-grey-300 hover:text-white" />
                    </span>
                </button>
                <button type="button" class="transform hover:scale-110 border-3 p-1 galBtn w-12 h-12"
                    @click="functions.showModal('gallery',  'add_edit', 'edit',index)">
                    <span class="toolTipEdit absolute -right-6 px-2 py-1 text-md text-white leading-none transform bg-gray-600 rounded-md h-6 pb-1 pt-1 pl-5 pr-5 -mt-10">
                        Edit
                    </span> 
                    <span
                        class="flex items-center justify-center text-2xl text-grey-300 transform hover:scale-125 transition ease-out duration-300">
                        <font-awesome-icon :icon="faIcon.edit" class="fill-current text-grey-300 hover:text-white" />
                    </span>
                </button>
                <button type="button" class="transform hover:scale-110 border-3 p-1 ml-7 galBtn w-12 h-12"
                    @click="functions.showModal('gallery',  'delete', 'delete',index)">
                    <span class="toolTipDelete absolute -right-6 px-2 py-1 text-md text-white leading-none transform bg-gray-600 rounded-md h-6 pb-1 pt-1 pl-5 pr-5 -mt-10">
                        Delete
                    </span>
                    <span
                        class="flex items-center justify-center text-2xl text-grey-300 transform hover:scale-125 transition ease-out duration-300">
                        <font-awesome-icon :icon="faIcon.trash" class="fill-current text-grey-300 hover:text-white" />
                    </span>
                </button>
                <h1>Dimensions: {{dimensions}} </h1>
                <h1>Size: {{ fileSIZE }} kB</h1>
            </div>
        </div>
        <input type="hidden" id="testing-code" :value="copyValue">
    </div>
</template>



<style>
/*tooltip copy*/
.toolTipCopy {
    opacity: 0;
    transition: 0.3s;
}
.transform:hover > .toolTipCopy {
    opacity: 1;
}
/*tooltip edit*/
.toolTipEdit {
    opacity: 0;
    transition: 0.3s;
}
.transform:hover > .toolTipEdit {
    opacity: 1;
}
/*tooltip copy*/
.toolTipDelete {
    opacity: 0;
    transition: 0.3s;
}
.transform:hover > .toolTipDelete {
    opacity: 1;
}
</style>
