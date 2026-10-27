<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
// import S4Sidebar from '@/S4/Sidebar.vue'
// import S4Topbar from '@/S4/Topbar.vue'; 
import NavBar from '@/S4/NavBar.vue'
// import S4Button from '@/S4/Button.vue'
// import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowLeft, faChartBar, faCopy, faEdit, faExternalLinkAlt, faTrashAlt, faCogs } from '@fortawesome/free-solid-svg-icons'
// import JetDropdown from '@/Jetstream/Dropdown.vue'
// import JetDropdownLink from '@/Jetstream/DropdownLink.vue'
import SideMenuLayout from './SideMenuLayout.vue';
import { ref } from 'vue'
import Chatbot from '@/S4/AI/Chatbot.vue'

// Helpbot escondido até ter backend próprio; VITE_HELPBOT_ENABLED=true religa.
const helpbotEnabled = import.meta.env.VITE_HELPBOT_ENABLED === 'true';

// import { Link } from "@inertiajs/vue3";


const props = defineProps(['layout', 'title']);
const faIcon = ref({
    trash: faTrashAlt,
    copy: faCopy,
    edit: faEdit,
    chart: faChartBar,
    link: faExternalLinkAlt,
    arrow_left: faArrowLeft,
    cogs: faCogs,
});

const isChatOpen = ref(false);

</script>

<template>
    <div class="w-full bg-white p-[0px_24px] h-[70px] z-[200] shadow-md fixed top-[0px]">
        <nav-bar></nav-bar>
    </div>
    <SideMenuLayout></SideMenuLayout>
    <app-layout :title="title">
        <!-- <template #topbar>
            <div class="flex flex-wrap justify-between items-center">
                
                <div class="w-full px-3.5 mb-3">
                    <nav-bar></nav-bar>
                </div>
            </div>
        </template> -->
        <template #content>
            <div class="rounded-sm">
                <div class="flex flex-row">
                    <main class="main flex flex-col flex-grow transition-all duration-150 ease-in px-[24px]">
                        <div>
                            <slot name="content"></slot>
                        </div>
                    </main>
                </div>
            </div>
            <slot name="modals"></slot>
            <Chatbot v-if="helpbotEnabled" v-model:open="isChatOpen" />
        </template>
    </app-layout>
</template>



<style scoped>
.prjText {
    font-weight: 900;
    font-size: 1.5rem;
    justify-content: center;
    align-items: center;
    margin-left: 0.5rem;
    display: flex;
}

.headerBtn {
    width: 3.25rem;
}

.headerBtn:hover {
    border-color: lightgrey;
}

.topBar {
    display: none;
}

.mobilePosition {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
}

.addQr {
    margin-left: auto;
    order: 2;
}

@media (max-width: 800px) {
    /*.prjText {
        font-weight:900;
        font-size:1rem;
        justify-content: center;
        align-items: center;
        margin-left:0.5rem;
        display:flex;
    }*/

    .headerBtn {
        width: 3.25rem;
        font-size: 14px;
    }

    .topBar {
        margin-top: 10px;
        display: flex;
        justify-content: center;
    }

    .mobilePosition {
        display: flex;
        flex-direction: column;
        justify-content: right;
        /* display: flex;
        flex-direction: row;
         */
        justify-content: right;
        flex-wrap: wrap;
    }

    .addQr {
        margin-right: auto;
        order: 1;
    }

    .mobilePD {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-left: 25px;
    }

    
}
</style>