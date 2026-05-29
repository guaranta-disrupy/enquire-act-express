<script setup>  
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import S4Button from '@/S4/Button.vue'
import S4Input from '@/S4/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetLabel from '@/Jetstream/Label.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSpinner, faSave, faTrashAlt,faCopy } from '@fortawesome/free-solid-svg-icons';
import S4Loading from '@/S4/Loading.vue'
import { ref, watch } from 'vue'
import { useForm, usePage } from "@inertiajs/vue3"
import helpers from '@/helpers.js'

const {  toast, VideoWithHeaders } = helpers()

const emit = defineEmits('close')
const props = defineProps(['modal'])
const faIcon = ref({
    save: faSave,
    spinner: faSpinner,
    edit: faEdit,
    trash: faTrashAlt,
    copy:faCopy
})
const form = useForm({
    images:[],
    changeImage:null,
    id:null
}, { 
    resetOnSuccess: true,
})
const colors = ref("blue")
const loading = ref(false)
const messageLoading = ref(null)
const imageStorage = ref([]) 
const displayImageEdit = ref(null)
const updateFile = ref(null)

const close = ()=>{  
    form.images=[];
    form.changeImage=null;
    imageStorage.value = []; 
    updateFile.value = null
    emit('close')
    loading.value = false;
}
const AddEditProject = () =>{  
    loading.value = true;
    //    console.log(this.$page.props.project)
    var params = [usePage().props.questionnaire.code]
    messageLoading.value = props.modal.method == 'add' ? 'Uploading images' : 'Updating image';  
    let web_route = props.modal.method == 'add' ? 'gallery.store': 'gallery.update';
    for(const x in imageStorage.value){ 
        form.images.push(imageStorage.value[x].file)
    }
    form.id = props.modal.method == 'edit' ? props.modal.data.id : null; //used form code to edit
    form.changeImage = updateFile.value || null;
    form.post(route(web_route,params), {
        forceFormData: true,	
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Images successfully ${props.modal.method == 'add' ? 'added': 'updated'}`, {
            timeout: 2000
            }) 
            close(); 
            window.dispatchEvent(
                (new window.Event(`gallery.image.update:${form.id}`))
            )
        },
        onError: () => {
            //code 
            loading.value = false; 
        },
        onFinish: () => {
            //code
        }
    })
    
}
const openFile = ()=> { 
    if(props.modal.method == 'add'){
        const hidden = document.getElementById("hidden-input");
        hidden.click();
        hidden.onchange = (e) => { 
            // console.log([e.target.files[0]])
            for (const file of e.target.files) {
                var objectURL = URL.createObjectURL(file);  
                imageStorage.value.push({fname:file.name, gsrc:objectURL,file:file}) 
            }
        };
    }else{
        const hidden = document.getElementById("hidden-input-edit");
        hidden.click();
        hidden.onchange = (e) => {  
            for (const file of e.target.files) {
                var objectURL = URL.createObjectURL(file);  
                displayImageEdit.value = {fname:file.name, gsrc:objectURL,file:file}
            } 
            updateFile.value = e.target.files[0]; 
            console.log('------------------')
            console.log(displayImageEdit.value)
            console.log('------------------')
            // displayImageEdit.value = URL.createObjectURL(updateFile.value);  
            // var objectURL = URL.createObjectURL(updateFile.value);  
            // displayImageEdit.value = ({fname:updateFile.name, gsrc:objectURL,file:e.target.files}) 
             
        };
    }
}
const drop = (event) =>{ 
    event.preventDefault(); 
    try{
        if(props.modal.method == "add"){
        for (const file of event.dataTransfer.files) {
        var objectURL = URL.createObjectURL(file);  
        imageStorage.value.push({fname:file.name, gsrc:objectURL,file:file}) 
        }
        }else{
            updateFile.value = event.dataTransfer.files[0];
            displayImageEdit.value = URL.createObjectURL(updateFile.value);  
        }
        event.currentTarget.classList.add('bg-gray-100');
        event.currentTarget.classList.remove('bg-indigo-300');
        }catch(error){
            alert(error)
            event.currentTarget.classList.add('bg-gray-100');
            event.currentTarget.classList.remove('bg-indigo-300');
        } 
}
const removeImage = (item) => {
    imageStorage.value.splice(imageStorage.value.indexOf(item),1)
}
const dragleave = (event) =>{
    event.currentTarget.classList.add('bg-gray-100');
    event.currentTarget.classList.remove('bg-indigo-300');
}
const dragover = (event) =>{
    event.preventDefault();
    if (!event.currentTarget.classList.contains('bg-indigo-300')) {
            event.currentTarget.classList.remove('bg-gray-100');
            event.currentTarget.classList.add('bg-indigo-300');
    }
}

const checkFileType = (type=null,mainSrc)=>{ 
    const images = ["jpg", "gif", "png","jpeg"]
    const videos = ["mp4", "3gp", "ogg"];
    let extension;
    console.log("-----------------------")
    console.log(type);
    console.log(mainSrc);
    if(type != null){
         extension = type.split("/")[1];
    }else{ 
        const url = new URL(mainSrc); 
        extension = url.pathname.split(".")[1]
    }
    
    if (images.includes(extension)) { 
        return 'Image';
    } else if (videos.includes(extension)) {   
        return 'Video';
}
}

watch(
    ()=> props.modal,
    (modal) => {
        if (modal.method == 'edit' && modal.add_edit == true) { 
            const filePath = props.modal.data.file_path
                ? '/' + props.modal.data.file_path
                : '/gallery/' + (props.modal.data.filename || props.modal.data.file_name);
            displayImageEdit.value = `${usePage().props.app_url}${filePath}`
        }
    },
    {  deep:true }
)

</script>

<template>
     <jet-dialog-modal :show="modal.add_edit" @close="close()">
        <template #content> 
            <div>
                <div> 
                    <span class="text-lg font-semibold">{{modal.method == 'add' ? "Add Images" : "Edit Image"}}</span>
                     <hr>
                </div> 
                <section class="h-full overflow-auto p-8 w-full h-full flex flex-col" @dragover="dragover" @dragleave="dragleave" @drop="drop">
                    <div v-if="modal.method == 'add'">
                        <header class="border-dashed border-2 border-gray-400 py-12 flex flex-col justify-center items-center">
                            <p class="mb-3 font-semibold text-gray-900 flex flex-wrap justify-center">
                                <span>Drag and drop your</span>&nbsp;<span>images/videos anywhere or</span>
                            </p>
                            <input id="hidden-input" type="file" multiple class="hidden" accept="image/png, image/gif, image/jpeg, video/mp4,video/x-m4v,video/*"/>
                            <button id="button" class="mt-2 rounded-sm px-3 py-1 bg-gray-200 hover:bg-gray-300 focus:shadow-outline focus:outline-none" @click="openFile">
                                Upload image/video
                            </button>
                        </header>
                        <jet-input-error :message="form.errors.images" />
                        <h1 class="pt-8 pb-10 font-semibold sm:text-lg text-gray-900">
                        To Upload
                        </h1>
                        <ul id="gallery" class="flex flex-1 flex-wrap -m-1" v-show="imageStorage.length <= 0">
                            <li id="empty" class="h-full w-full text-center">
                                <span class="text-small text-gray-500">No files selected</span>
                            </li>
                        </ul>
                        <div class="overflow-hidden grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-3">
                            <div v-show="imageStorage.length > 0" v-for="(item,key) in imageStorage" :key="key" 
                            class=" hasImage w-full h-full rounded-md focus:outline-none focus:shadow-outline bg-gray-100 cursor-pointer relative text-transparent hover:text-white shadow-sm  
                            bg-white item border-2 border-grey-800 rounded-lg transform hover:scale-100 transition ease-out duration-300 container">  
                            <img v-if="checkFileType(item.file.type) == 'Image'" alt="upload preview" :src="item.gsrc" class="gal_img sticky object-cover rounded-md bg-fixed" /> 
                            <video class="gal_img" v-else controls autoplay>
                                <source :src="VideoWithHeaders(item.gsrc)" type="video/mp4"/>
                            </video> 
                            <section class="flex flex-col rounded-md text-xs break-words w-full h-full z-20 absolute top-0 py-2 px-3">
                                <h1 class="flex-1">{{item.fname}}</h1>
                                <div class="flex">
                                <p class="p-1 size text-xs"></p>
                                <button class="delete ml-auto focus:outline-none hover:bg-gray-300 p-1 rounded-md" @click="removeImage(item)">
                                    <svg class="pointer-events-none fill-current w-4 h-4 ml-auto" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                    <path class="pointer-events-none" d="M3 6l3 18h12l3-18h-18zm19-4v2h-20v-2h5.711c.9 0 1.631-1.099 1.631-2h5.316c0 .901.73 2 1.631 2h5.711z" />
                                    </svg>
                                </button>
                                </div>
                            </section> 
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        <header class="border-dashed border-2 border-gray-400 py-12 flex flex-col justify-center items-center">
                            <p class="mb-3 font-semibold text-gray-900 flex flex-wrap justify-center">
                                <span>Drag and drop your</span>&nbsp;<span>images/videos anywhere or</span>
                            </p>
                            <input id="hidden-input-edit" type="file" class="hidden" accept="image/png, image/gif, image/jpeg,video/mp4,video/x-m4v,video/*"/>
                            <button id="button" class="mt-2 rounded-sm px-3 py-1 bg-gray-200 hover:bg-gray-300 focus:shadow-outline focus:outline-none" @click="openFile">
                                Upload an image/video
                            </button>
                        </header>
                        <jet-input-error :message="form.errors.changeImage" /> 
                        <div class="overflow-hidden grid grid-cols-1 md:grid-cols-1 lg:grid-cols-1 mt-8">
                            <div class=" hasImage w-full h-full rounded-md focus:outline-none focus:shadow-outline bg-gray-100 cursor-pointer relative text-transparent hover:text-white shadow-sm  
                                bg-white item border-2 border-grey-800 rounded-lg transform hover:scale-100 transition ease-out duration-300 container">  
                            <img v-if="checkFileType(displayImageEdit?.file?.type, displayImageEdit) == 'Image'"  class="gal_img sticky object-cover rounded-md bg-fixed"  
                            alt="upload preview" :src="displayImageEdit.gsrc || displayImageEdit" /> 
                            <video v-else  class="gal_img" controls autoplay>
                                <source :src="VideoWithHeaders(displayImageEdit.gsrc || displayImageEdit)" type="video/mp4"/>
                            </video>  
                            </div>
                        </div> 
                    </div>
            </section>
            </div> 
            <s4-loading v-show="loading" :label="messageLoading" theme="theme1" class="h-screen z-50 overflow-hidden bg-gray-700/50" :color="'text-white'"></s4-loading>
        </template>
        <template #footer>
            <s4-button :theme="'ssrCancel'" class="mr-1" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="AddEditProject()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3">
                        <font-awesome-icon :icon="faIcon.spinner" spin /> 
                        Wait
                    </span>
                </template>
                <template v-else-if="modal.method == 'edit'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.edit" /> -->
                        Update
                    </span>
                </template>
                <template v-else-if="modal.method == 'delete'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.trash" />-->
                        Delete
                    </span>
                </template> 
                <template v-else>
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.save" />-->
                        Upload
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>   
</template>

<style>
.hasImage:hover section {
  background-color: rgba(5, 5, 5, 0.4);
}
.hasImage:hover button:hover {
  background: rgba(5, 5, 5, 0.45);
}
.edit_card{
    position:relative;
}
.gal_img{
    height: auto; 
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    max-width: auto;
    max-height: 100%;
    margin: auto!important;
    position: absolute;
}
button#button {
    background:orange;
    color:white;
    height:5vmin;
    border-radius:12px;
    }

button#button:hover {
    height:5.1vmin;
    transition: all ease 300ms;
    font-size:17px;
}
</style>
