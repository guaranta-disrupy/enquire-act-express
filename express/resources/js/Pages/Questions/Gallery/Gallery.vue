<script setup>
import SliderBar from '../../../S4/SliderBar.vue';
import ImageItem from './ImageItem.vue';
import AddGallery from './AddEdit/AddGallery.vue';
import WarningModal from '../../../S4/Warning.vue';
import { ref, onMounted, watch } from 'vue';
import helpers from '@/helpers.js';
import { useForm, usePage } from "@inertiajs/vue3";

const { assign, toast } = helpers()

const props = defineProps(["showGallery"]);
const emit = defineEmits(["showGalleryHandler"])
const updateImage = ref(false)
const image_id = ref(null);

// Add local reactive state for gallery
const localGallery = ref([]);

// Add selection state management
const selectedItems = ref(new Set());
const isSelectAll = ref(false);

// Gallery UI state
const pageReady = ref(false);
const isExpanded = ref(false);
const delay = () => {
    setTimeout(() => {
        pageReady.value = true;
    }, 1000)
}

onMounted(() => {
    // console.log("picture ni", usePage().props.questions)
    delay()
    // Initialize local gallery with server data
    localGallery.value = [...usePage().props.gallery];
})

// Watch for changes in server gallery data
watch(() => usePage().props.gallery, (newGallery) => {
    localGallery.value = [...newGallery];
}, { deep: true });


const showAddEditBox = ref({
    display: false,
    action: "add",
    data: null
});
const showDeleteModal = ref({
    data: null,
    show: false
});
const copyValue = ref(null);
const form = useForm({
    type: null,
}, {
    resetOnSuccess: true,

})

const showGalleryHandler = () => {
    emit('showGalleryHandler');
}
const checkFileType = (filename, urlLoc) => {
    try {
        const images = ["jpg", "jpeg", "png", "gif", "bmp", "tiff", "svg", "webp", "ico", "heic", "heif", "raw", "psd", "ai", "eps"]
        const videos = ["mp4", "mkv", "mov", "avi", "wmv", "flv", "webm", "3gp", "ogg", "m4v", "mpeg", "mpg", "ts", "vob"]
        const url = new URL(urlLoc.app_url + '/' + filename)
        const extension = url.pathname.split(".")[1].toLocaleLowerCase();

        if (images.includes(extension)) {
            return 'Image';
        } else if (videos.includes(extension)) {
            return 'Video';
        }
    } catch (e) {
        console.log(e)
    }
}
const addAndEditHadler = (value) => {
    showAddEditBox.value.display = value;
    showAddEditBox.value.action = "add";
}
const showModal = (index) => {
    updateImage.value = true
    showAddEditBox.value.display = true;
    showAddEditBox.value.action = "edit";
    showAddEditBox.value.data = assign(usePage().props.gallery[index])
}

const getLink = (link, code) => {
    // If this is a Fluent gallery file path, build a direct Fluent URL; otherwise fall back to cached image URL
    if (link && (link.startsWith('/galleries/') || link.startsWith('galleries/'))) {
        const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
        copyValue.value = `${fluentBase}/storage/${link.replace(/^\//, '')}`;
    } else {
        copyValue.value = helpers().ImageWithHeaders(`${usePage().props.app_url}${link}`);
    }
    setTimeout(() => copyLink(code), 100);
}

const copyLink = (code) => {
    var testingCodeToCopy = document.querySelector(code)
    testingCodeToCopy.setAttribute('type', 'text')
    testingCodeToCopy.select();
    try {
        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
        toast.success(`Copied`, {
            timeout: 2000
        })
    } catch (err) {
        // console.log(err)
        // alert('Oops, unable to copy');
    }

    /* unselect the range */
    testingCodeToCopy.setAttribute('type', 'hidden')
    window.getSelection().removeAllRanges()
}
const imageSize = async (url) => {
    var img = new Image();
    img.src = url;
    var dimensions = img.width + 'x' + img.height
    // console.log(img)
    let fileSize = null;
    try {
        let blob = await fetch(url).then((res) => res.blob());
        fileSize = (blob.size / 1024).toFixed(2);
    } catch (err) {
        console.log(err);
        fileSize = "unknown";
    }

    // console.log("fileSize: ", fileSize);
    return {
        dimensions,
        fileSize
    };
}

const assignID = (item) => {
    image_id.value = item;
}

// Handle individual item selection
const handleItemSelection = (itemId, isSelected) => {
    if (isSelected) {
        selectedItems.value.add(itemId);
    } else {
        selectedItems.value.delete(itemId);
    }
    
    // Update select all state
    updateSelectAllState();
}

// Update select all state based on current selections
const updateSelectAllState = () => {
    if (selectedItems.value.size === 0) {
        isSelectAll.value = false;
    } else if (selectedItems.value.size === localGallery.value.length) {
        isSelectAll.value = true;
    } else {
        isSelectAll.value = false;
    }
}

// Toggle select all / unselect all
const toggleSelectAll = () => {
    if (isSelectAll.value) {
        // Unselect all
        selectedItems.value.clear();
        isSelectAll.value = false;
    } else {
        // Select all
        selectedItems.value = new Set(localGallery.value.map(item => item.id));
        isSelectAll.value = true;
    }
}

// Delete selected items
const deleteSelectedItems = () => {
    if (selectedItems.value.size === 0) {
        toast.error('No items selected', { timeout: 2000 });
        return;
    }
    
    showDeleteModal.value.show = true;
    showDeleteModal.value.data = null;
    form.type = 'deleteSelected';
}

const handleResetUpdate = (data) => {
    // console.log("hhhhhhhhhhhhhhhhhhhaaaaaaaaaaaaaaaaaaaaahhhhhhhhhhhhhhhhhaaaaaaaaaaaaaa", data)
    updateImage.value = data.updateImage
    image_id.value = data.img_id

}

const showDeleteModalHandler = (value, itemData) => {
    // console.log(itemData)
    showDeleteModal.value.show = value;
    showDeleteModal.value.data = itemData;
    if (itemData == null) {
        form.type = 'deleteAll';
    } else {
        form.type = 'delete';
    }
}

const closeDeleteModal = () => {
    showDeleteModal.value.show = false;
    showDeleteModal.value.data = null;
    form.type = null;
}

const image = useForm({
    id: null,
});

const deleteChecker = () => {
    if (form.type == 'deleteAll') {
        DeleteAllImages()
    } else if (form.type == 'deleteSelected') {
        DeleteSelectedImages()
    } else {
        DeleteImage()
    }
};

const DeleteImage = () => {

    form.delete(route('gallery.destroy', [
        usePage().props.questionnaire.code,
        showDeleteModal.value.data.id
    ]), {
        preserveScroll: true,
        onSuccess: () => {
            // Remove the deleted item from local gallery state
            const deletedIndex = localGallery.value.findIndex(item => item.id === showDeleteModal.value.data.id);
            if (deletedIndex !== -1) {
                localGallery.value.splice(deletedIndex, 1);
                // Also remove from selected items if it was selected
                selectedItems.value.delete(showDeleteModal.value.data.id);
            }
            
            toast.success(`Image deleted`, {
                timeout: 2000
            })
            closeDeleteModal();
        },
        onError: () => {
            //code
        },
        onFinish: () => {
            //code
        }
    })
}

const DeleteSelectedImages = () => {
    const selectedIds = Array.from(selectedItems.value);
    
    if (selectedIds.length === 0) {
        toast.error('No items selected', { timeout: 2000 });
        return;
    }
    
    // Try the most efficient bulk delete method first
    try {
        deleteSelectedEfficientBulk(selectedIds);
    } catch (error) {
        console.log('Efficient bulk delete failed, trying alternative methods');
        // Fallback to alternative bulk method
        deleteSelectedBulk(selectedIds);
    }
}

// Alternative bulk delete method using existing destroy route
const deleteSelectedBulk = async (selectedIds) => {
    try {
        // Create a form with all IDs
        const bulkForm = useForm({
            ids: selectedIds,
            type: 'deleteSelected'
        });
        
        // Send as DELETE request to the existing destroy route
        bulkForm.delete(route('gallery.destroy', [
            usePage().props.questionnaire.code,
            'bulk' // Use 'bulk' as the ID parameter
        ]), {
            preserveScroll: true,
            onSuccess: () => {
                handleBulkDeleteSuccess(selectedIds);
            },
            onError: (errors) => {
                console.log('Bulk delete failed, falling back to sequential');
                // Final fallback to sequential deletion
                deleteSelectedSequentially(selectedIds);
            }
        });
    } catch (error) {
        console.log('Alternative bulk method failed, using sequential deletion');
        deleteSelectedSequentially(selectedIds);
    }
}

// Most efficient bulk delete method - sends all IDs in one request
const deleteSelectedEfficientBulk = async (selectedIds) => {
    try {
        // Create a form with all IDs
        const efficientBulkForm = useForm({
            ids: selectedIds,
            type: 'deleteSelected'
        });
        
        // Send as DELETE request to the existing destroy route
        efficientBulkForm.delete(route('gallery.destroy', [
            usePage().props.questionnaire.code,
            'bulk' // Use 'bulk' as the ID parameter
        ]), {
            preserveScroll: true,
            onSuccess: () => {
                handleBulkDeleteSuccess(selectedIds);
            },
            onError: (errors) => {
                console.log('Efficient bulk delete failed, trying alternative method');
                deleteSelectedBulk(selectedIds);
            }
        });
    } catch (error) {
        console.log('Efficient bulk method failed, using alternative method');
        deleteSelectedBulk(selectedIds);
    }
}

// Handle successful bulk deletion
const handleBulkDeleteSuccess = (selectedIds) => {
    // Remove all selected items from local gallery
    selectedIds.forEach(id => {
        const deletedIndex = localGallery.value.findIndex(item => item.id === id);
        if (deletedIndex !== -1) {
            localGallery.value.splice(deletedIndex, 1);
        }
    });
    
    // Clear selection after successful deletion
    selectedItems.value.clear();
    isSelectAll.value = false;
    
    toast.success(`${selectedIds.length} item(s) deleted successfully`, {
        timeout: 2000
    });
    closeDeleteModal();
}

// Fallback sequential deletion method (keep as backup)
const deleteSelectedSequentially = async (selectedIds) => {
    let successCount = 0;
    let failedCount = 0;
    
    for (const id of selectedIds) {
        try {
            const deleteForm = useForm({});
            await new Promise((resolve, reject) => {
                deleteForm.delete(route('gallery.destroy', [
                    usePage().props.questionnaire.code,
                    id
                ]), {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Remove from local gallery
                        const deletedIndex = localGallery.value.findIndex(item => item.id === id);
                        if (deletedIndex !== -1) {
                            localGallery.value.splice(deletedIndex, 1);
                        }
                        successCount++;
                        resolve();
                    },
                    onError: () => {
                        failedCount++;
                        reject(new Error(`Failed to delete item ${id}`));
                    }
                });
            });
        } catch (error) {
            console.error(`Failed to delete item ${id}:`, error);
            failedCount++;
        }
    }
    
    // Clear selection after all deletions
    selectedItems.value.clear();
    isSelectAll.value = false;
    
    // Show results
    if (failedCount === 0) {
        toast.success(`${successCount} items deleted successfully`, {
            timeout: 2000
        });
    } else {
        toast.warning(`${successCount} items deleted, ${failedCount} failed`, {
            timeout: 3000
        });
    }
    
    closeDeleteModal();
}

const DeleteAllImages = () => {
    form.delete(route('gallery.destroy', [
        usePage().props.questionnaire.code,
        1
    ]), {
        preserveScroll: true,
        onSuccess: () => {
            // Clear all items from local gallery state
            localGallery.value = [];
            
            toast.success(`All Images deleted`, {
                timeout: 2000
            })
            closeDeleteModal();
        },
        onError: () => {
            //code
        },
        onFinish: () => {
            //code
        }
    })
}

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
}

</script>
<template>
    <div class="relative" v-if="pageReady">
        <WarningModal :show="showDeleteModal.show" @close="closeDeleteModal()">
            <template #header>
                {{ form.type === 'deleteAll' ? 'Delete All Items?' : 
                   form.type === 'deleteSelected' ? 'Delete Selected Item(s)?' : 'Delete Item?' }}
            </template>
            <template #content>
                {{ form.type === 'deleteAll'
                    ? 'Are you sure you want to delete all items? All items will be permanently removed. This action is irreversible.'
                    : form.type === 'deleteSelected'
                    ? `Are you sure you want to delete ${selectedItems.size} selected item(s)? The selected items will be permanently removed. This action is irreversible.`
                    : 'Are you sure you want to delete this item? The item will be permanently removed. This action is irreversible.'
                }}
            </template>
            <template #footer>
                <button :disabled="form.processing" @click="deleteChecker()"
                    class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                    type="button">
                    <span class="pl-3 pr-3">Confirm</span>
                </button>
                <button @click="closeDeleteModal()"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
                    type="button">
                    Cancel
                </button>
            </template>
        </WarningModal>

        <SliderBar title="Gallery" :showSlideBar="true" :expand="isExpanded" :enableExpand="true">
            <!-- <div class="border flex items-center gap-3 pr-4 rounded-full bg-ssr-blue2 shadow-md cursor-pointer hover:shadow-none" 
                 @click="addAndEditHadler(true)">
                <div class="p-1 border bg-white rounded-full text-ssr-orange2"><Icons icon="photo"/></div>
                <div class="text-white">Add Gallery</div>
            </div> -->
            <template #controller>
                <!-- Expand toggle button -->
                <div class="cursor-pointer ">
                                    <button
                                        type="button"
                                        @click.stop="toggleExpand"
                                        class="h-14 w-6 rounded-full bg-white shadow-md border border-gray-200 flex items-center justify-center cursor-pointer hover:shadow-sm transition-all duration-150 relative z-50"
                                    >
                                        <span
                                            class="text-gray-600 text-sm transition-transform duration-150"
                                            :class="isExpanded ? 'rotate-180' : ''"
                                        >
                                            &gt;
                                        </span>
                                    </button>
                                
                </div>
            </template>
            <template #expand>

                <div class="transition-all duarion-150">
                    <div class="">
                        <!-- <div class=""  @click="addAndEditHadler(false)"></div> -->
                        <AddGallery :actions="showAddEditBox.action" :data="showAddEditBox.data"
                            :updateImage="updateImage" :img_id="image_id" @reset="handleResetUpdate" />
                    </div>
                </div>
                <div class="w-full flex ">
                    <div class="w-full flex justify-start items-center gap-2 mt-2 ml-2">
                        <!-- Select/Unselect All Button -->
                        <!-- <button 
                            v-if="localGallery.length >= 1"
                            @click="toggleSelectAll" 
                            type='button'
                            class="inline-flex items-center px-2 py-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.02]">
                            <svg v-if="!isSelectAll" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>{{ isSelectAll ? 'Unselect All' : 'Select All' }}</span>
                        </button> -->

                        <!-- Delete Selected Button -->
                        <!-- <button 
                            v-if="selectedItems.size > 0"
                            @click="deleteSelectedItems" 
                            type='button'
                            class="inline-flex items-center px-2 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.02]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Delete Selected ({{ selectedItems.size }})</span>
                        </button> -->

                        <!-- Delete All Button -->
                        <!-- <button v-if="localGallery.length >= 1" @click="showDeleteModal.show = true, form.type = 'deleteAll'" type='button'
                            class="inline-flex items-center px-4 py-2 hover:bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.05]">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 mr-1 transition-all duration-300 ease-in-out hover:text-red-600"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>

                            <span class="transition-all duration-300 ease-in-out hover:text-red-600">
                                Delete All
                            </span>
                        </button> -->
                    </div>
                </div>

               
                <div
                    v-if="localGallery.length"
                    class="relative mt-2 font-bold overflow-y-auto rounded-xl max-h-[calc(100vh-175px)] gallery-scroll"
                >
                    

                    <div
                        :class="[
                            'grid gap-3 pb-7 p-2 pl-4',
                            isExpanded
                                ? 'grid-cols-3 md:grid-cols-4 lg:grid-cols-5'
                                : 'grid-cols-2 md:grid-cols-2 lg:grid-cols-2',
                        ]"
                    >
                        <ImageItem v-for="(item, index) in localGallery" :key="item.id" :itemData="item"
                            :index="index"
                            :functions="{ getLink, showModal, imageSize, copyLink, showDeleteModalHandler }"
                            :copyValue="copyValue"
                            :typeFile="checkFileType(item.file_path || ('gallery/' + (item.filename || item.file_name)), usePage().props)"
                            @click="assignID(item)" 
                            :isSelected="selectedItems.has(item.id)"
                            @selectionChange="handleItemSelection" />
                    </div>
                </div>
            </template>

        </SliderBar>

    </div>
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

/* Hide scrollbar by default, only show on hover when content overflows */
.gallery-scroll {
    overflow-y: auto;
    /* Hide scrollbar by default */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

.gallery-scroll::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
    width: 0;
    height: 0;
}

/* Show scrollbar on hover (only appears if content overflows) - matching app default style */
.gallery-scroll:hover {
    scrollbar-width: thin; /* Firefox */
    scrollbar-color: var(--mcolor) white; /* Orange thumb, white track */
}

.gallery-scroll:hover::-webkit-scrollbar {
    display: block;
    width: 5px; /* Match app default */
}

.gallery-scroll:hover::-webkit-scrollbar-track {
    background: white; /* Match app default */
}

.gallery-scroll:hover::-webkit-scrollbar-thumb {
    background: var(--mcolor); /* Match app default orange color */
    border-radius: 5px; /* Match app default */
}

.gallery-scroll:hover::-webkit-scrollbar-thumb:hover {
    background: var(--mcolor2); /* Match app default darker orange on hover */
}
</style>