<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import S4Button from '@/S4/Button.vue' 
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faChevronRight, faCopy, faEdit, faSync, faTrashAlt, faSearch, faEllipsisH, faEye, faCheckCircle, faArrowRight, faHandPointer } from '@fortawesome/free-solid-svg-icons'
import JetDropdown from '@/Jetstream/Dropdown.vue'
import JetDropdownLink from '@/Jetstream/DropdownLink.vue'
import JetLabel from '@/Jetstream/Label.vue'
import AddEditThemeModal from './modals/AddEditTheme.vue'
import DeleteThemeModal from './modals/DeleteTheme.vue'
import ApplyThemeModal from './modals/ApplyTheme.vue'
import CopyThemeModal from './modals/CopyTheme.vue'
import { ref, onMounted, provide } from 'vue';
import { usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const { assign, ImageWithHeaders } = helpers()

// Provide questionnaire code for child components (like Gallery modal)
const projectCode = ref(usePage().props.questionnaire.code);
provide('projectCode', projectCode.value);
// const pageReady = ref(false);
//const page = usePage();

const faIcon = ref({
    cheveron_right: faChevronRight,
    trash: faTrashAlt,
    copy: faCopy,
    edit: faEdit,
    sync: faSync,
    search: faSearch,
    apply: faCheckCircle,
    cont: faArrowRight,
    pointer: faHandPointer
})

const modals = ref({
    theme: {
        add_edit: false,
        delete: false,
        apply_theme: false,
        copy: false,
        method: 'add',
        data: null
    }
})

// onMounted(() => {
//     //console.log("Themes",page)
//     setTimeout(() => {
//         pageReady.value = true;
//     }, 3000);
// });

// const custom = ref(null)
const showModal = (table, modal, method, index = -1) => { 
    // console.log("custom:", custom.value);
    modals.value[table].method = method
    if (index >= 0) {
        modals.value[table].data = assign( usePage().props.themes[index])
    }
    else {
        modals.value[table].data = null
    }  
    
    modals.value[table][modal] = true      
}
const closeModal = (table, modal) => {
    modals.value[table][modal] = false
    modals.value[table].data = null
}
const styleImgBrightness = (brightness) => {
    return {
        '--background-img-brightness': brightness,
        'filter': `brightness(${brightness})`,
        'position': 'relative' // Add this to ensure proper stacking context
    };
};

const getBackgroundImageUrl = (imagePath) => {
    if (!imagePath || imagePath.trim() === '') {
        return '';
    }
    const cachedUrl = ImageWithHeaders(imagePath);
    return cachedUrl ? `url(${cachedUrl})` : '';
};


// window.addEventListener("scroll", function(event) {  
//     const el = event.target;
//     clearTimeout(el._scrolling);

//     el.classList.add("is-scrolling"); 

//     el._scrolling = setTimeout(() => {
//         el.classList.remove("is-scrolling");
//     }, 2000);
// }, true);

</script>
<template>
    <main-layout title="Design">
         <!-- <template #header>
            <div class="header-content flex items-center flex-row">
                <s4-button @click="showModal('theme', 'add_edit', 'add')"  :theme="'floatingBTN'"> 
                    <span class="pl-3 pr-3">Add Theme</span>
                </s4-button>                
            </div>
        </template> -->
        <template #content>
            <div class="flex flex-cols-1 place-content-between absolute top-[70px] left-[0px] z-[30] w-full pr-[24px] pt-[24px] bg-[var(--bgcolor)]">
                    <div class="group flex flex-col justify-start items-start cursor-pointer max-w-4xl pl-[170px] ">
                        <div class="text-2xl text-[var(--txtcolor)] font-bold max-w-lg truncate">Themes</div>
                        <div class="text-sm text-[var(--txtcolor)] opacity-[50%] transition pr-4 place-self-center font-bold truncate">the overall appearance of the survey.
                        </div>
                    </div> 
                    <div class="h-9 flex flex-cols-1 gap-4">
                        <!-- <div class="my-1 border border-l-slate-300"></div> -->
                        <div @click="showModal('theme', 'add_edit', 'add')" class="cursor-pointer transition flex justify-center items-center text-sm font-semibold px-3 py-2 rounded-lg bg-[var(--mcolor)] border-2 border-transparent text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="self-center w-4 h-4 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add Theme
                        </div>
                </div>
            </div>

            <div class="w-full pt-[80px]">

                
                <div id="myDIVxxx" class="rounded-lg main-content overflow-y-auto">   
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-[24px]">
                        <div class="relative" v-for="(theme,index) in $page.props.themes" :key="index">
                            <div class="themeEdit"> 
                                <div class="">
                                    <JetDropdown width="100">
                                        <template #trigger>
                                            <div type="button" id="menu-button" aria-expanded="true" aria-haspopup="true" class="theme-action cursor-pointer text-slate-700 absolute p-1 m-2 right-0 bg-white rounded-full">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                                </svg>
                                            </div>
                                        </template>
                                        <template #content>
                                            <div class="w-32">
                                                <div class="px-4 pt-2 text-xs text-gray-400 dark:text-gray-100">
                                                    Actions
                                                </div>
                                                <div class="overflow-auto max-h-80vh min-w-xl">
                                                    <JetDropdownLink as="button" v-if="$page.props.questionnaire.setup.design_id != theme.setup.design_id" @click="showModal('theme', 'apply_theme', 'apply',index)">
                                                        <span class="text-slate-500">Apply</span>
                                                    </JetDropdownLink>
                                                    <JetDropdownLink as="button" @click="showModal('theme', 'copy', 'copy',index)">
                                                        <span class="text-slate-500">Duplicate</span>
                                                    </JetDropdownLink>
                                                    <JetDropdownLink as="button" v-if="theme.id > 3" @click="showModal('theme', 'add_edit', 'update',index)">
                                                        <span class="text-slate-500">Edit</span>
                                                    </JetDropdownLink>
                                                    <JetDropdownLink as="button" v-if="theme.id > 3 && !($page.props.questionnaire.setup.design_id == theme.setup.design_id)" @click="showModal('theme', 'delete', 'delete', index)">
                                                        <span class="text-slate-500">Delete</span>
                                                    </JetDropdownLink>
                                                </div>
                                            </div>
                                        </template>
                                    </JetDropdown>                                    
                                </div>                                

                                <div class="h-48 addBG shadow rounded-xl cursor-pointer" v-bind:style="{backgroundImage: getBackgroundImageUrl(theme.setup.design['background-img']), backgroundColor: theme.setup.design['background-color'],}" :style="styleImgBrightness(theme.setup.design['background-img-brightness'])"></div>


                                <div class="text-slate-700 text-sm py-2 ml-1 font-semibold grid">
                                    <div>
                                        <span v-if="$page.props.questionnaire.setup.design_id == theme.id" class="float-left rounded-full text-md h-6 mt-1">{{theme.text}}                                            
                                        </span>
                                        <span v-else class="float-left rounded-md text-md h-6 mt-1">{{theme.text}}</span>
                                        <div class="pt-1">
                                            <div class="absolute p-0.5 right-0 px-3 border border-green-300 bg-green-100 rounded-full" v-if="$page.props.questionnaire.setup.design_id == theme.id">
                                                <span class="text-xs text-green-500">Applied</span>
                                            </div>
                                        </div>
                                    </div> 
                                    <div>
                                        <div v-if="theme.id > 3 && !($page.props.questionnaire.setup.design_id == theme.setup.design_id)" class="text-sm text-slate-400 -ml-0 -mt-1">Customed</div>
                                        <div v-else class="text-sm text-slate-400 -ml-0 -mt-1">Pre-defined</div>
                                    </div>
                                </div>
                                
                            </div>

                            <!-- <div id="imgBr" class="addBG h-full w-full"
                            v-bind:style="{ 
                                backgroundImage: 'url(' + theme.setup.design['background-img'] + ')',
                                backgroundColor:  theme.setup.design['background-color'],
                            }"  :style="styleImgBrightness(theme.setup.design['background-img-brightness'])"
                            >
                            </div> -->
                            <!-- <div id="infoDiv" class="h-full w-full themeEdit relative -top-80 left-0" style="backgroundColor:transparent;">
                                <div class="p-4 pt-2 mt-6">
                                    <h3 class="text-2xl font-semibold mb-3"
                                    v-bind:style="{ 
                                        color: theme.setup.design['question-txt-color']
                                    }"
                                    >
                                        Lorem ipsum dolor sit amet. Est voluptas architecto
                                    </h3>   
                                    <div class="border border-gray-100 rounded-md px-3 py-1"
                                        v-bind:style="{
                                            backgroundColor: theme.setup.design['choice-box-color'],
                                            borderColor: theme.setup.design['choice-box-border-color'],
                                        }"
                                    >
                                        <p style="display: inline-block;" class="rounded-full w-5 h-5 
                                        mt-1"
                                            v-bind:style="{ 
                                                backgroundColor: theme.setup.design['radio-selected-color'],
                                                borderColor: theme.setup.design['radio-border-color'],
                                            }"
                                        >
                                            <span class="ml-7"
                                                v-bind:style="{ 
                                                    color: theme.setup.design['radio-lbl-color'],
                                                }"
                                            >Yes</span>
                                        </p>                                
                                    </div>
                                    <div class="mt-1 border border-gray-100 rounded-md px-3 py-1"
                                        v-bind:style="{
                                            backgroundColor: theme.setup.design['choice-box-color'],
                                            borderColor: theme.setup.design['choice-box-border-color'],
                                        }"
                                    >
                                        <p style="display: inline-block;" class="rounded-full w-5 h-5 
                                        mt-1"
                                            v-bind:style="{ 
                                                backgroundColor: theme.setup.design['radio-color'],
                                                borderColor: theme.setup.design['radio-border-color'],
                                            }"
                                        >
                                            <span class="ml-7"
                                                v-bind:style="{ 
                                                    color: theme.setup.design['radio-lbl-color'],
                                                }"
                                            >No</span>
                                        </p>                                
                                    </div>
                                    <div class="mt-8" style="text-align: center;">
                                        <button
                                        v-bind:style="{ 
                                            backgroundColor: theme.setup.design['button-color'], 
                                            color: theme.setup.design['button-txt-color'],
                                        }"
                                        class="rounded-md h-8 px-6">
                                            <span class=" text-sm ">Continue</span>
                                            <font-awesome-icon :icon="faIcon.cont" class="relative -right-3 w-4.5 h-3.5 fill-current"
                                                v-bind:style="{ 
                                                    color: theme.setup.design['button-txt-color'],
                                                }"
                                            />

                                        </button>
                                    </div> 
                                </div> 
                                <div class="mt-2 flow-root bg-gray-50 pl-4 pr-4 pt-2 pb-2">

                                    <div class="">
                                        <span v-if="$page.props.questionnaire.setup.design_id == theme.id" class="float-left rounded-full text-md h-6 mt-1">{{theme.text}}
                                            <svg class="float-left mt-0.5 mr-2 h-5 w-5 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </span>                            

                                        <span v-else class="float-left rounded-md text-md h-6 mt-1">{{theme.text}}</span>                                
                                    </div>                            


                                    <div>
                                        <div class="float-right mt-1 toolTipEdit flex gap-3 flex-row-reverse">
                                            <ButtonIconsVue v-if="theme.id > 3 && !($page.props.questionnaire.setup.design_id == theme.setup.design_id)" style="box-shadow: 0px 0px; background-color: rgba(255, 255, 255, 0); width:15px; height: auto; border:none;" icon="delete" @click="showModal('theme', 'delete', 'delete', index)"/>

                                            <ButtonIconsVue v-if="theme.id > 3" style="box-shadow: 0px 0px; background-color: rgba(255, 255, 255, 0); width:15px; height: auto; border:none;" icon="edit" @click="showModal('theme', 'add_edit', 'update',index)"/>

                                            <ButtonIconsVue style="box-shadow: 0px 0px; background-color: rgba(255, 255, 255, 0); width:15px; height: auto; border:none;" icon="copy" @click="showModal('theme', 'add_edit', 'copy',index)"/>

                                            <ButtonIconsVue v-if="$page.props.questionnaire.setup.design_id != theme.setup.design_id" style="box-shadow: 0px 0px; background-color: rgba(255, 255, 255, 0); width:15px; height: auto; border:none;" icon="apply" @click="showModal('theme', 'apply_theme', 'apply',index)"/>
                                        </div>
                                    </div>                           
                                    
                                    
                                </div>
                            </div>  -->

                        </div>                     

                    </div>
                </div>                
            </div>           

        </template>

        <template #modals>
            <add-edit-theme-modal 
            :modal="modals.theme" 
            @showModal="showModal"
            @close="closeModal('theme', 'add_edit')" />            

            <copy-theme-modal
            :modal="modals.theme"
            @close="closeModal('theme', 'copy')" />

            <delete-theme-modal 
            :modal="modals.theme"
            @close="closeModal('theme', 'delete')" />

            <apply-theme-modal
            :modal="modals.theme"
            @close="closeModal('theme', 'apply_theme')" />            
        </template>
        
    </main-layout>
</template>




<style scoped>
#myDIVxxx {
    scroll-behavior: smooth;
}

#myDIVxxx::-webkit-scrollbar {
    width: 5px;
}

#myDIVxxx::-webkit-scrollbar-track {
    background-color: transparent;
}

#myDIVxxx::-webkit-scrollbar-thumb {
    background-color: transparent;
    border-radius: 10px;
}

#myDIVxxx.is-scrolling::-webkit-scrollbar-track {
    background-color: transparent;
}

#myDIVxxx.is-scrolling::-webkit-scrollbar-thumb {
    background-color: #B6CDDC;
}

#myDIVxxx.is-scrolling::-webkit-scrollbar-thumb:hover {
    background: #829AAB;
}

#imgBr {
    filter: brightness( var(--background-img-brightness) );
}
/*tooltip edit*/
.toolTipEdit, .theme-action {    
    opacity: 0;
    transition: 0.3s;    
}
/* .themeEdit:hover .toolTipEdit {    
    opacity: 1;
} */

.theme-action {    
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 20; /* Increase z-index to ensure it's above other elements */
}

.themeEdit:hover .theme-action {    
    opacity: 1;
}

.addBG {
    background-repeat: no-repeat;
    background-size: cover;
    transition: all 0.3s ease;
    position: relative;
}
.QQ {
    width:6rem;
    border-bottom-right-radius: 1rem;
    font-size:0.9rem;
}
</style>