<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetSectionBorder from '@/Jetstream/SectionBorder.vue';

import S4Button from '@/S4/Button.vue';
import S4InputSelect from '@/S4/InputSelect.vue';
import S4Loading from '@/S4/Loading.vue'
import { initializeUpload, isDone } from "@/chunked-upload.js";
import { usePage } from '@inertiajs/inertia-vue3';

// const $page = {usePage};

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons'

import { provide, reactive, ref, watch, onMounted, inject, onUnmounted } from 'vue'
import helpers from '@/helpers.js';
const { ImageWithHeaders, VideoWithHeaders } = helpers();
const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;

const props = defineProps({
    modal: { type: Object, required: true },
    type: { type: String, required: true },
    zIndex: { type: [Number, String], default: 50 },
})
const emits = defineEmits(['close', 'selectGallery'])

const imageStorage = ref([]);
const tempStorage = ref([]);
const projectCode = ref(inject("projectCode"));

const faIcon = reactive({
    trash: faTrashAlt
})

// Global gallery state management
const GALLERY_EVENT = 'gallery-state-change'

const close = () => {
    emits('close')
    // Emit global event to close other galleries
    window.dispatchEvent(new CustomEvent(GALLERY_EVENT, {
        detail: {
            action: 'close',
            source: 'gallery-modal',
            type: props.type
        }
    }))
}

const cancel = () => {
    imageStorage.value = [];
    tempStorage.value = [];
}

const AddEditProject = () => {
    let web_route = 'gallery.store';
    initializeUpload(tempStorage.value, projectCode.value, web_route, null, "progressContainer2", "statusMessage2", imageStorage, null);
    // Clear tempStorage after initiating upload to prevent duplicate uploads
    tempStorage.value = [];
}

const selectGallery = (gallery) => {
    emits('selectGallery', gallery)
    close()
}

const isVideo = (filename) => {
    const videoExtensions = ['.mp4', '.webm', '.ogg', '.avi', '.mov', '.mkv', '.flv', '.wmv', '.m4v', '.3gp']
    let isVideo = videoExtensions.some(ext => filename.toLowerCase().endsWith(ext));
    if (isVideo == true) {
        return 'video';
    }
    return null;
}

const isImage = (filename) => {
    const videoExtensions = ['.mp4', '.webm', '.ogg', '.avi', '.mov', '.mkv', '.flv', '.wmv', '.m4v', '.3gp']
    let isVideo = videoExtensions.some(ext => filename.toLowerCase().endsWith(ext));
    if (isVideo == false) {
        return 'image';
    }
    return null;
}
const upload = () => {
    const handleFile = document.getElementById('fileSelector');
    // Reset file input value to allow selecting the same file again
    handleFile.value = '';
    handleFile.click();
    handleFile.onchange = async (e) => {
        for (const file of e.target.files) {
            var objectURL = URL.createObjectURL(file);
            tempStorage.value.push(file);
            imageStorage.value.push({ fname: file.name, gsrc: objectURL, file: file });
        }
        // Reset file input value after processing to allow selecting the same files again
        handleFile.value = '';
    }

}

// watch(() => isDone.value, (change) => {
//     if(change){
//         cancel();
//     }
// })

const loading = ref(false);
const messageLoading = ref(null);

// Listen for global gallery events to close this gallery when another opens
const handleGlobalGalleryEvent = (event) => {
    const { action, source, type } = event.detail

    // If another gallery is opening and it's not this one, close this gallery
    if (action === 'open' && source !== 'gallery-modal' && props.modal.show) {
        close()
    }
}

onMounted(() => {
    // Listen for global gallery events
    window.addEventListener(GALLERY_EVENT, handleGlobalGalleryEvent)

    // Emit global event when this gallery opens
    if (props.modal.show) {
        window.dispatchEvent(new CustomEvent(GALLERY_EVENT, {
            detail: {
                action: 'open',
                source: 'gallery-modal',
                type: props.type
            }
        }))
    }
})

onUnmounted(() => {
    // Clean up event listener
    window.removeEventListener(GALLERY_EVENT, handleGlobalGalleryEvent)
})

// Watch for modal show changes to emit open events
watch(() => props.modal.show, (newValue) => {
    if (newValue) {
        // Emit global event when this gallery opens
        window.dispatchEvent(new CustomEvent(GALLERY_EVENT, {
            detail: {
                action: 'open',
                source: 'gallery-modal',
                type: props.type
            }
        }))
    }
})
</script>

<template>
    <jet-dialog-modal :show="modal.show" :zIndex="props.zIndex" @close="close()" maxWidth="7xl">
        <template #title>
            <span>Gallery</span>
        </template>

        <template #content>
            <div v-if="$page.props.gallery.length === 0"
                class="flex flex-col items-center italic text-slate-400 text-md content-center p-[80px_20px]">
                <img src="@/../assets/icons/imageVideo.svg" class="w-[120px] h-auto pointer-events-none" />
                <div class="self-center w-full font-bold pt-[12px] text-center text-balance">
                    No images / videos yet!
                </div>
            </div>

            <div class="mb-5" v-show="imageStorage.length > 0">

                <div
                    class="flex flex-col gap-5 bg-slate-100 rounded-xl h-auto max-h-[40vmin] w-full p-2 overflow-auto border-dashed border-2 border-slate-300 rounded-md">
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-5">
                        <template v-for="(item, index) in imageStorage" :key="index">
                            <div class="relative flex flex-col p-2 border-2 rounded-md transition ease-out duration-300 bg-white border-grey-800 container flex justify-center cursor-pointer hover:border-ssr-blue3"
                                @click="selectGallery(item)">
                                <div class="imgCont">
                                    <video v-if="isVideo(item.fname)" class="relative" :src="item.gsrc" autoplay
                                        muted />
                                    <img v-else class="relative" :src="item.gsrc">
                                </div>
                                <div
                                    class="relative w-[100%] flex text-black text-center font-bold text-xs p-1 rounded-md">
                                    <span class="truncate w-full">
                                        {{ item.fname }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div id="progressContainer2" class="h-4 hidden"></div>
                    <div id="statusMessage2" class="mt-2 hidden"></div>

                    <div class="flex justify-center items-center">
                        <div v-show="imageStorage.length >= 1"
                            class="w-full flex flex-cols-1 place-content-center mb-1">
                            <div class="flex mt-2 gap-2">
                                <button @click="cancel" type='button' class='flex break-inside py-2'>
                                    <div
                                        class='flex items-center justify-between flex-1 text-slate-400 hover:text-slate-600'>
                                        <span class='text-sm font-semibold'>Cancel</span>
                                    </div>
                                </button>
                                <button @click="AddEditProject" type='button'
                                    class='flex break-inside pl-2 py-2 hover:shadow-[0_0_5px_#FCCA27] rounded pr-2 hover:bg-[var(--mcolor)] group'>
                                    <div class='flex items-center justify-between flex-1'>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="2.5" stroke="black"
                                            class="w-4 h-4 mr-1 group-hover:stroke-white transition-colors">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                        </svg>
                                        <span
                                            class='text-sm text-black group-hover:text-white font-semibold transition-colors'>Upload</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <div class="grid gap-1">
                <div class=" overflow-auto h-6/6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-5">

                    <template v-for="(gallery, index) in $page.props.gallery" :key="index">
                        <!-- {{ gallery }} -->

                        <div v-if="isVideo(gallery.file_path || gallery.filename || gallery.file_name) == props.type"
                            class="relative flex flex-col p-2 border-2 rounded-md transition ease-out duration-300 border-grey-800 container justify-center cursor-pointer hover:border-ssr-blue3"
                            @click="selectGallery(gallery)">
                            <div class="imgCont">
                                <video class="relative" :src="gallery.file_path
                                    ? fluentBase + '/storage/' + gallery.file_path
                                    : VideoWithHeaders($page.props.app_url + '/gallery/' + (gallery.filename || gallery.file_name))
                                    " autoplay muted />
                            </div>
                            <div class="relative w-[100%] flex text-black text-center font-bold text-xs p-1 rounded-md">
                                <span class="truncate w-full">
                                    {{ gallery.config && gallery.config.title
                                        ? gallery.config.title
                                        : (gallery.file_name || gallery.originalName)
                                    }}
                                </span>
                            </div>
                        </div>
                        <div v-if="isImage(gallery.file_path || gallery.filename || gallery.file_name) == props.type"
                            class="relative flex flex-col p-2  border-2 rounded-md transition ease-out duration-300 border-grey-800 container flex justify-center cursor-pointer hover:border-ssr-blue3"
                            @click="selectGallery(gallery)">
                            <div class="imgCont">
                                <img class="relative" :src="gallery.file_path
                                    ? fluentBase + '/storage/' + gallery.file_path
                                    : ImageWithHeaders($page.props.app_url + '/gallery/' + (gallery.filename || gallery.file_name))
                                    ">
                            </div>
                            <div class="relative w-[100%] flex text-black text-center font-bold text-xs p-1 rounded-md">
                                <span class="truncate w-full">
                                    {{ gallery.config && gallery.config.title
                                        ? gallery.config.title
                                        : (gallery.file_name || gallery.originalName)
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- <div class="p-2 border-2 rounded-md transition ease-out duration-300 border-grey-800 container flex justify-center cursor-pointer hover:border-ssr-blue3" @click="selectGallery(gallery)">                             
                            <div class="imgCont">
                                
                                <video  v-if="isVideo(gallery.filename)" class="relative" :src="$page.props.app_url + '/gallery/'+gallery.filename" controls />
                                <img v-else class="relative" :src="$page.props.app_url + '/gallery/'+gallery.filename"> 
                            
                            </div>          
                        </div> -->
                    </template>
                </div>
            </div>



        </template>

        <template #footer>
            <input type="file" id="fileSelector" name="file" multiple class="hidden"
                :accept="props.type === 'video' ? 'video/mp4,video/webm,video/ogg,video/avi,video/mov,video/mkv,video/flv,video/wmv,video/x-m4v,video/3gpp,video/*' : props.type === 'image' ? 'image/png,image/gif,image/jpeg,image/webp,image/bmp,image/tiff,image/svg+xml' : 'image/png, image/gif, image/jpeg,video/mp4,video/x-m4v,video/*'" />
            <!-- <s4-button :theme="'ssrOrange'" @click="upload()">
                <div class='flex items-center justify-between flex-1 text-white'>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="white" class="size-6 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <span class='text-sm text-white font-semibold'> Upload {{ props.type === 'video' ? 'Video' :
                        props.type === 'image' ? 'Image' : 'Image/Video' }}</span>
                </div>
            </s4-button> -->
            <div class="w-full flex justify-end">
                <s4-button :theme="'ssrOrange'" @click="close()">
                    <span class="pl-3 pr-3 text-white">Close</span>
                </s4-button>
            </div>

        </template>
    </jet-dialog-modal>
</template>



<style scoped>
.imgCont {
    margin: auto auto;
}

.imgCont img {
    max-height: 10vmin;
    position: relative;
}

.gal_img {
    height: auto;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    max-width: auto;
    max-height: 100%;
    margin: auto !important;
    position: absolute;
}
</style>