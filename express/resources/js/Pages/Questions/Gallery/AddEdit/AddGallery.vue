<script setup>
import { ref, watch } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'
import { initializeUpload, cancelUploads } from "@/chunked-upload.js";

const { toast, VideoWithHeaders } = helpers();
const props = defineProps(["actions", "data", "updateImage", "img_id"]);
const emit = defineEmits(['resetUpdateData']);

// console.log(props.data)
const form = useForm({
    images: [],
    changeImage: null,
    id: null
}, {
    resetOnSuccess: true,
});
const loading = ref(false);
const imageStorage = ref([]);
const displayImageEdit = ref(null);
const updateFile = ref(null);

const close = () => {
    try { cancelUploads(); } catch (e) {}
    form.images = [];
    form.changeImage = null;
    imageStorage.value = [];
    tempStorage.value = [];
    updateFile.value = null;
    displayImageEdit.value = null;
    loading.value = false;
    props.actions = 'add'
}

// const AddEditProject = () =>{  
//     loading.value = true;
//     //    console.log(this.$page.props.project)
//     var params = [usePage().props.questionnaire.code]
//     messageLoading.value = props.actions == 'add' ? 'Uploading images' : 'Updating image';  
//     let web_route = props.actions == 'add' || displayImageEdit.value == null ? 'gallery.store': 'gallery.update';
//     for(const x in imageStorage.value){ 
//         form.images.push(imageStorage.value[x].file)
//     }
//     form.id = props.actions == 'edit' ? props.data.id : null; //used form code to edit
//     form.changeImage = updateFile.value || null;
//     form.post(route(web_route,params), {
//         forceFormData: true,	
//         preserveScroll: true,
//         onSuccess: () => {
//             toast.success(`Images successfully ${props.actions == 'add' ? 'added': 'updated'}`, {
//             timeout: 2000
//             }) 
//             close(); 
//             window.dispatchEvent(
//                 (new window.Event(`gallery.image.update:${form.id}`))
//             )
//         },
//         onError: () => {
//             //code 
//             loading.value = false; 
//         },
//         onFinish: () => {
//             //code
//         }
//     })

// }
const updateImage = ref(false);

const AddEditProject = () => {
    updateImage.value = props.updateImage
    var params = [usePage().props.questionnaire.code]
    let web_route = updateImage.value == false ? 'gallery.store' : 'gallery.update';
    initializeUpload(tempStorage.value, params, web_route, props.img_id, "progressContainer", "statusMessage", imageStorage, displayImageEdit,
        (uploadedFile) => {
            // Find the item in imageStorage and set uploaded = true
            const item = imageStorage.value.find(i => i.file === uploadedFile);
            if (item) item.uploaded = true;
            toast.success("Image uploaded successfully!", { timeout: 2000 });
        }
    );
    updateImage.value = false;
    emit("reset", {
        updateImage: false,
        img_id: null
    })

    tempStorage.value = [];
    // imageStorage.value = []

}

const tempStorage = ref([]);
const openFile = () => {
    if (props.actions == 'add' || displayImageEdit.value == null) {
        const hidden = document.getElementById("hidden-input");
        hidden.value = "";
        hidden.click();
        hidden.onchange = (e) => {
            // console.log("=====>>>", [e.target.files])
            for (const file of e.target.files) {
                var objectURL = URL.createObjectURL(file);
                tempStorage.value.push(file)
                imageStorage.value.push({ fname: file.name, gsrc: objectURL, file: file })
            }
            hidden.value = "";

        };
    } else {
        const hidden = document.getElementById("hidden-input-edit");
        hidden.value = "";
        hidden.click();
        hidden.onchange = (e) => {
            for (const file of e.target.files) {
                var objectURL = URL.createObjectURL(file);
                tempStorage.value.push(file)
                displayImageEdit.value = { fname: file.name, gsrc: objectURL, file: file }
            }
            // console.log("yvonne lang skalam: ", tempStorage.value)
            updateFile.value = e.target.files[0];
            hidden.value = "";
            // console.log("====>>>", [e.target])
            // console.log('------------------')
            // console.log(displayImageEdit.value)
            // console.log('------------------')
        };
    }
}

const checkFileType = (type = null, mainSrc) => {
    try {
        const images = ["jpg", "jpeg", "png", "gif", "bmp", "tiff", "svg", "webp", "ico", "heic", "heif", "raw", "psd", "ai", "eps"]
        const videos = ["mp4", "mkv", "mov", "avi", "wmv", "flv", "webm", "3gp", "ogg", "m4v", "mpeg", "mpg", "ts", "vob"];
        let extension;
        // console.log("-----------------------")
        // console.log(type);
        // console.log(mainSrc);
        if (type != null) {
            extension = type.split("/")[1];
        } else {
            const url = new URL(mainSrc);
            extension = url.pathname.split(".")[1]
        }

        if (images.includes(extension)) {
            return 'Image';
        } else if (videos.includes(extension)) {
            return 'Video';
        }
    } catch (e) {
       
    }
}
const removeImage = (item) => {
    imageStorage.value.splice(imageStorage.value.indexOf(item), 1)
    tempStorage.value.splice(tempStorage.value.indexOf(item), 1)
    if (imageStorage.value.length === 0) {
        tempStorage.value = [];
    }
}

watch(() => props.data, (data) => {
    if (props.actions == 'edit') {
        displayImageEdit.value = `${usePage().props.app_url}/gallery/${data.filename}`
    }
},
    { deep: true }
)

</script>
<template>

    <div>
        <div v-if="props.actions == 'add' || displayImageEdit == null">
            <div class="flex">
                <div class="flex flex-col bg-[var(--mcolor)] h-full w-full p-4">
                    <div class="text-[20px] text-slate-800 font-semibold pl-1.5 text-white">Gallery</div>
                    <div class="text-[10px] text-slate-500 px-2 font-normal text-white">Upload images or videos
                        <span class="italic">(PNG, JPEG, GIF, MP4, etc.)</span>
                    </div>
                </div>
                <!-- <button @click="openFile" type='button' class='mt-0.5 flex break-inside bg-sky-100 rounded-lg px-4 py-2 hover:bg-sky-200'>
                        <div class='flex items-center justify-between flex-1 text-sky-400'>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentCOlor" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                            </svg>
  
                            <span class='text-sm font-semibold'>Video</span>
                        </div>
                    </button>  -->

                <input id="hidden-input" type="file" multiple class="hidden"
                    accept="image/png, image/gif, image/jpeg, video/mp4,video/x-m4v,video/*" />
            </div>
            <!-- <div class="flex justify-center pt-2">
                <button @click="openFile" type='button'
                    class='mt-0.5 flex break-inside bg-[var(--mcolor)] rounded-lg px-1.5 py-1.5 hover:bg-[var(--mcolor2)]'>
                    <div class='flex items-center justify-between flex-1 text-white'>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="white" class="size-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        <span class='text-sm font-semibold text-white'>Upload Image/Video</span>
                    </div>
                </button>
            </div> -->

            <div v-show="imageStorage.length >= 1" class="w-full flex flex-cols-1 place-content-center mb-1">
                <div class="flex mt-2 gap-2">
                    <button @click="close" type='button' class='flex break-inside py-2'>
                        <div class='flex items-center justify-between flex-1 text-slate-400 hover:text-slate-600'>
                            <span class='text-sm font-semibold'>Cancel</span>
                        </div>
                    </button>
                    <button @click="AddEditProject" type='button'
                        class='flex break-inside pl-2 py-2 hover:shadow-[0_0_5px_#FCCA27] rounded pr-2 hover:bg-[var(--mcolor)] group'>
                        <div class='flex items-center justify-between flex-1'>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                stroke="black" class="w-4 h-4 mr-1 group-hover:stroke-white transition-colors">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                            <span class='text-sm text-black group-hover:text-white font-semibold transition-colors'>Upload</span>
                        </div>
                    </button>
                </div>
            </div>
            <div v-show="imageStorage.length > 0">
                <div
                    class="h-full font-bold border-dashed border-2 border-slate-300 overflow-y-auto grid grid-cols-1 gap-3 bg-slate-100 rounded-xl p-5">
                    <div id="progressContainer" class="h-4 hidden"></div>
                    <div id="statusMessage" class="mb-1 hidden"></div>
                    <div class="grid grid-cols-1 gap-3">
                        <div v-for="(item, key) in imageStorage" :key="key"
                            class="hasImage w-full bg-white rounded-xl focus:outline-none focus:shadow-outline bg-gray-100 cursor-pointer relative text-transparent hover:text-white shadow-sm item rounded-xl transform hover:scale-100 transition ease-out duration-300 container">
                            <img v-if="checkFileType(item.file.type) == 'Image'" alt="upload preview" :src="item.gsrc"
                                class="gal_img sticky object-cover rounded-md bg-fixed" />
                            <video class="gal_img" v-else controls muted>Video
                                <source :src="VideoWithHeaders(item.gsrc)" type="video/mp4" />
                            </video>
                            <span v-if="item.uploaded" class="uploaded-indicator">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="12" fill="#22c55e" />
                                    <path d="M7 13l3 3 7-7" stroke="#fff" stroke-width="2.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            <section
                                class="flex flex-col rounded-md text-xs break-words w-full h-full z-20 absolute top-0 py-2 px-3">
                                <h1 class="flex-1 font-normal">{{ item.fname }}</h1>
                                <div class="flex">
                                    <p class="p-1 size text-xs"></p>
                                    <button class="ml-auto focus:outline-none hover:bg-gray-300 p-1 rounded-full"
                                        @click="removeImage(item)">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </section>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <div v-else>

            <div v-if="displayImageEdit != null">

                <!-- <div class="my-3 flex justify-end">
                    <div class="border flex items-center gap-3 pr-4 rounded-full bg-ssr-orange2 shadow-md cursor-pointer hover:bg-orange-400 hover:shadow-none" 
                        @click="AddEditProject">
                        <div class="p-1 border bg-white rounded-full text-ssr-orange2"><Icons icon="upload"/></div>
                        <div class="text-white">Upload</div>
                    </div>
                </div> -->

                <div class="flex">
                    <div class="flex flex-col bg-[var(--mcolor)] h-full w-full p-4">
                        <div class="text-[20px] text-slate-800 font-semibold pl-1.5 text-white">Gallery</div>
                        <div class="text-[10px] text-slate-500 px-2 font-normal text-white">Upload images or videos
                            <span class="italic">(PNG, JPEG, GIF, MP4, etc.)</span>
                        </div>
                    </div>
                    <!-- <button @click="openFile" type='button' class='mt-0.5 flex break-inside bg-sky-100 rounded-lg px-4 py-2 hover:bg-sky-200'>
                            <div class='flex items-center justify-between flex-1 text-sky-400'>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentCOlor" class="w-5 h-5 mr-2">
                                    <path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                                </svg>
    
                                <span class='text-sm font-semibold'>Video</span>
                            </div>
                        </button>  -->

                    <input id="hidden-input-edit" type="file" class="hidden"
                        accept="image/png, image/gif, image/jpeg,video/mp4,video/x-m4v,video/*" />
                </div>
                <div class="flex justify-center pt-2">
                    <button @click="openFile" type='button'
                        class='mt-0.5 flex break-inside bg-[var(--mcolor)] rounded-lg px-4 py-2 hover:bg-[var(--mcolor2)]'>
                        <div class='flex items-center justify-between gap-2 text-white'>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                            <span class='text-sm font-semibold'>Upload Image/Video</span>
                        </div>
                    </button>
                </div>

                <!-- <div class="flex flex-col justify-center items-center h-[15vh] border rounded-lg shadow-md gap-2">
                    Drag and drop your images/videos anywhere or
                    <div class="flex gap-2">
                        <ButtonIcons icon="photo" @click="openFile"/>
                        <ButtonIcons icon="video" @click="openFile"/>
                    </div>
                    <input id="hidden-input-edit" type="file" class="hidden" accept="image/png, image/gif, image/jpeg,video/mp4,video/x-m4v,video/*"/>
                </div> -->

                <!-- <div class="mt-5 font-bold border rounded-md px-4 h-[57vh] pt-2 overflow-y-auto">
                    To Update
                    <div class="overflow-hidden grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-3">
                        <div class=" hasImage w-full h-full rounded-md focus:outline-none focus:shadow-outline bg-gray-100 cursor-pointer relative text-transparent hover:text-white shadow-sm  
                                    bg-white item border-2 border-grey-800 rounded-lg transform hover:scale-100 transition ease-out duration-300 container">  
                            <img v-if="checkFileType(displayImageEdit?.file?.type, displayImageEdit) == 'Image'"  class="gal_img sticky object-cover rounded-md bg-fixed" alt="upload preview" :src="displayImageEdit.gsrc || displayImageEdit" /> 
                            <video class="gal_img" v-if="checkFileType(displayImageEdit?.file?.type, displayImageEdit) == 'Video'" controls autoplay>
                                <source :src="displayImageEdit.gsrc || displayImageEdit" type="video/mp4"/>
                            </video>  
                        </div>
                    </div>
                </div>   -->

                <div>
                    <div
                        class="mt-8 font-bold border rounded-xl border border-dashed border-2 border-slate-300 overflow-y-auto grid grid-cols-1 gap-8 bg-slate-100 p-5">

                        <div class="overflow-hidden grid grid-cols-1 gap-3">
                            <div
                                class=" hasImage w-full h-full rounded-md focus:outline-none focus:shadow-outline bg-gray-100 cursor-pointer relative text-transparent hover:text-white shadow-sm bg-white item border-2 border-grey-800 rounded-lg transform hover:scale-100 transition ease-out duration-300 container">
                                <img v-if="checkFileType(displayImageEdit?.file?.type, displayImageEdit) == 'Image'"
                                    class="gal_img sticky object-cover rounded-md bg-fixed" alt="upload preview"
                                    :src="displayImageEdit.gsrc || displayImageEdit" />
                                <video class="gal_img"
                                    v-if="checkFileType(displayImageEdit?.file?.type, displayImageEdit) == 'Video'"
                                    controls muted>
                                    <source :src="VideoWithHeaders(displayImageEdit.gsrc || displayImageEdit)" type="video/mp4" />
                                </video>
                            </div>
                        </div>

                        <div id="progressContainer" class="h-4 mt-2 hidden"></div>
                        <div id="statusMessage" class="mt-2 hidden"></div>
                    </div>
                    <div class="w-full flex flex-cols-1 place-content-center">
                        <div class="flex mt-2 gap-2">
                            <button @click="close" type='button' class='flex break-inside py-2'>
                                <div
                                    class='flex items-center justify-between flex-1 text-slate-400 hover:text-slate-600'>
                                    <span class='text-sm font-semibold'>Cancel</span>
                                </div>
                            </button>
                            <button @click="AddEditProject" type='button' class='flex break-inside pl-2 py-2'>
                                <div class='flex items-center justify-between flex-1 text-[var(--mcolor)] hover:text-[var(--mcolor2)]'>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="currentColor" class="w-4 h-4 mr-1">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                    </svg>
                                    <span class='text-sm font-semibold'>Update</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</template>
<style scoped>
.hasImage {
    height: 180px !important;
}

.hasImage:hover section {
    background-color: rgba(5, 5, 5, 0.4);
}

.hasImage:hover button:hover {
    background: rgba(5, 5, 5, 0.45);
}

.edit_card {
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

button#button {
    background: orange;
    color: white;
    height: 5vmin;
    border-radius: 12px;
}

button#button:hover {
    height: 5.1vmin;
    transition: all ease 300ms;
    font-size: 17px;
}

.uploaded-indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 30;
    background: transparent;
    border-radius: 50%;
    box-shadow: 0 0 4px #22c55e55;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>