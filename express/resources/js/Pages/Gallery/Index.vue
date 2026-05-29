<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import S4Button from '@/S4/Button.vue';
import ImageItem from './ImageItem.vue'; 

import GalleryAdd from './modal/GalleryModal.vue';
import GalleryDelete from './modal/GalleryDelete.vue';
import { ref } from 'vue';
import { usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const {  assign, toast } = helpers()
 
const copyValue = ref(null)
const modals = ref({
    gallery: {
        delete: false,
        add_edit: false,
        method: 'add',
        data: null
    }
})
const showModal = (table, modal, method, index = -1) => {
    modals.value[table][modal] = true
    modals.value[table].method = method
    if (index >= 0) {
        modals.value[table].data = assign(usePage().props.gallery[index])
    } else {
        modals.value[table].data = null
    }
}
const closeModal = (table, modal) => {
    modals.value[table][modal] = false
    modals.value[table].data = null
}
const getLink = (link) => {
    copyValue.value = `${usePage().props.app_url}${link}`;
    setTimeout(() => copyLink(), 100);
}

const copyLink = () => {
    var testingCodeToCopy = document.querySelector('#testing-code')
    testingCodeToCopy.setAttribute('type', 'text')
    testingCodeToCopy.select();
    try {
        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
        toast.success(`Copied`, {
            timeout: 2000
        })
    } catch (err) {
        alert('Oops, unable to copy');
    }

    /* unselect the range */
    testingCodeToCopy.setAttribute('type', 'hidden')
    window.getSelection().removeAllRanges()
}
const imageSize = async (url) => {
    var img = new Image();
    img.src = url;
    var dimensions = img.width + 'x' + img.height
    console.log(img)
    let fileSize = null;
    try {
        let blob = await fetch(url).then((res) => res.blob());
        fileSize = (blob.size / 1024).toFixed(2);
    } catch (err) {
        console.log(err);
        fileSize = "unknown";
    }

    console.log("fileSize: ", fileSize);
    return {
        dimensions,
        fileSize
    }; 
}

const checkFileType = (filename,urlLoc)=>{ 
    const images = ["jpg", "gif", "png"]
    const videos = ["mp4", "3gp", "ogg"]
    const url = new URL(urlLoc.app_url+'/gallery/'+filename)
    const extension = url.pathname.split(".")[1]

    if (images.includes(extension)) { 
        return 'Image';
    } else if (videos.includes(extension)) {  
        return 'Video';
}
}

</script>

<template>
    <main-layout title="Gallery">
        <template #header> 
            <div class="header-content flex items-center flex-row">
                <s4-button @click="showModal('gallery',  'add_edit', 'add')" :theme="'floatingBTN'">
                    <span class="pl-3 pr-3">Add Image/Video</span>
                </s4-button>
            </div>
        </template>
        <template #content>
            <div class="bg-white rounded-lg p-5">
                <h3 v-if="usePage().props?.gallery.length<=0" class="text-bold"> No Images yet.</h3>
                <div class="border rounded-lg ">
                    <div class=" overflow-hidden h-12 bg-slate-50 rounded-t-lg p-4">
                        <div class="font-bold text-slate-600">Images & Videos</div>
                    </div>
                    <div class="h-[72vmin] overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-4 ">
                            <ImageItem  v-for="(item,index) in usePage().props.gallery" 
                                        :key="index" 
                                        :itemData="item"
                                        :index="index" 
                                        :functions="{ getLink, showModal, imageSize, copyLink }"
                                        :copyValue="copyValue"
                                        :typeFile="checkFileType(item.filename,usePage().props)"
                                        class=""
                                        />
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #modals>
            <gallery-add :modal="modals.gallery" @close="closeModal('gallery', 'add_edit')"></gallery-add>
            <gallery-delete :modal="modals.gallery" @close="closeModal('gallery', 'delete')"></gallery-delete>
        </template>
    </main-layout>
</template>

<style>
    .item {
        height: 239px;
    }

    .overlay {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        height: 100%;
        width: 100%;
        opacity: 0;
        transition: .5s ease;
        /* background-color: #008CBA; */
        background-color: rgba(30, 113, 184, 0.85);
        border-radius: 0.5rem;
    }

    .container:hover .overlay {
        opacity: 1;
    }

    .btn {
        color: white;
        font-size: 20px;
        position: absolute;
        top: 50%;
        left: 50%;
        -webkit-transform: translate(-50%, -50%);
        -ms-transform: translate(-50%, -50%);
        transform: translate(-50%, -50%);
        text-align: center;
        width: 100%;
    }

    .galBtn {
        background-color: rgba(236, 235, 235, 0.2);
        border: 1px solid rgb(230, 230, 230);
        border-radius: 50%;
    }

    .classes1 {
    font-weight: 700;
    color: #1e71b8;
}

.classes2 {
    font-weight: bold;
    color: rgb(136, 135, 135);
}


</style>
