<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCogs, faFileWord, faExternalLinkAlt, faChartBar, faImage, faPaste, faEye, faPalette } from '@fortawesome/free-solid-svg-icons'
import { Link } from "@inertiajs/vue3";
import { ref } from 'vue'
import helpers from '@/helpers.js'

const { random_str } = helpers()



const active = ref(true)

const faIcon = {
    cogs: faCogs,
    file_word: faFileWord,
    link: faExternalLinkAlt,
    chart: faChartBar,
    image:faImage,
    paste:faPaste,
    eye: faEye,
    theme: faPalette
}

const activeClass = (current) => {
    return route().current(current) ? "bg-ssr-orange2 wht hover:bg-ssr-orangehover" : "text-gray-600 liBG bg-white hover:bg-ssr-transparent"
}
const activeClassIcon = (current) => {
    return route().current(current) ? "text-white" : "text-ssr-orange2"
}
</script>

<template>
    <aside
        class="sidebar flex-none w-64 transform -translate-x-full md:translate-x-0 transition-transform duration-150 ease-in"
        >
        <div class="sidebar-content">
            <ul class="flex flex-col w-full">
                <li class="my-px mb-1">
                    <Link
                        :href="route('questions.index', {
                            'project_code': $page.props.project.code, 
                            'questionnaire_code':$page.props.questionnaire.code
                        })"
                        :class="activeClass('questions.index')"
                        class="flex flex-row items-center h-10 px-3 rounded-sm hover:text-gray-700 liLink">
                        <span class=" pl-1 pr-1 flex items-center justify-center text-lg spFA" :class="activeClassIcon('questions.index')">
                            <font-awesome-icon :icon="faIcon.file_word" class="fill-current hover:text-gray-600" />
                        </span>
                        <span class="ml-3">Questions</span>
                        <!-- <span class="flex items-center justify-center text-xs text-red-500 font-semibold bg-red-100 h-6 px-2 rounded-full ml-auto">1k</span> -->
                    </Link>
                </li>
                <li class="my-px  mb-1">
                    <Link
                        :href="route('questionnaires.settings', {
                            'project_code': $page.props.project.code, 
                            'questionnaire_code':$page.props.questionnaire.code
                        })"
                        :class="activeClass('questionnaires.settings')"
                        class="flex flex-row items-center h-10 px-3 rounded-sm hover:text-gray-700 liLink">
                        <span class=" pl-1 pr-1 flex items-center justify-center text-lg spFA" :class="activeClassIcon('questionnaires.settings')">
                            <font-awesome-icon :icon="faIcon.cogs" class="fill-current hover:text-gray-600" />
                        </span>
                        <span class="ml-3">Settings</span>
                    </Link>
                </li>
                <li class="my-px mb-1">
                    <Link
                        :href="route('analysis.index', {
                            'project_code': $page.props.project.code, 
                            'questionnaire_code':$page.props.questionnaire.code
                        })"
                        :class="activeClass('analysis.index')"
                        class="flex flex-row items-center h-10 px-3 rounded-sm hover:text-gray-700 liLink">
                        <span class=" pl-1 pr-1 flex items-center justify-center text-lg spFA" :class="activeClassIcon('analysis.index')">
                            <font-awesome-icon :icon="faIcon.chart" class="fill-current hover:text-gray-600" />
                        </span>
                        <span class="ml-3">Analysis</span>
                    </Link>
                </li>
                <li class="my-px mb-1">
                    <Link
                        :href="route('gallery.index', {
                            'project_code': $page.props.project.code, 
                            'questionnaire_code':$page.props.questionnaire.code
                        })"
                        :class="activeClass('gallery.index')"
                        class="flex flex-row items-center h-10 px-3 rounded-sm hover:text-gray-700 liLink">
                        <span class=" pl-1 pr-1 flex items-center justify-center text-lg spFA" :class="activeClassIcon('gallery.index')">
                            <font-awesome-icon :icon="faIcon.image" class="fill-current hover:text-gray-600" />
                        </span>
                        <span class="ml-3">Gallery</span>
                    </Link>
                </li>
                <li class="my-px mb-1">
                    <Link
                        :href="route('templates.index', {
                            'project_code': $page.props.project.code, 
                            'questionnaire_code':$page.props.questionnaire.code
                        })"
                        :class="activeClass('templates.index')"
                        class="flex flex-row items-center h-10 px-3 rounded-sm hover:text-gray-700 liLink">
                        <span class=" pl-1 pr-1 flex items-center justify-center text-lg spFA" :class="activeClassIcon('templates.index')">
                            <font-awesome-icon :icon="faIcon.paste" class="fill-current hover:text-gray-600" />
                        </span>
                        <span class="ml-3">Templates</span>
                    </Link>
                </li>
                <li class="my-px mb-1">
                    <Link
                        :href="route('themes.index', {
                            'project_code': $page.props.project.code, 
                            'questionnaire_code':$page.props.questionnaire.code
                        })"
                        :class="activeClass('themes.index')"
                        class="flex flex-row items-center h-10 px-3 rounded-sm hover:text-gray-700 liLink">
                        <span class=" pl-1 pr-1 flex items-center justify-center text-lg spFA" :class="activeClassIcon('themes.index')">
                            <font-awesome-icon :icon="faIcon.theme" class="fill-current hover:text-gray-600" />
                        </span>
                        <span class="ml-3">Design</span>
                    </Link>
                </li>
                <li class="my-px mb-1">
                    <a :href="$page.props.engine_url+'?code='+$page.props.questionnaire.code+'&preview=1&id=test-'+random_str(5)"
                        target="_blank"
                        class="flex flex-row items-center h-10 px-3 bg-white hover:bg-ssr-transparent liLink">
                        <span class=" pl-1 pr-1 flex items-center justify-center text-lg hover:text-gray-700 spFA">
                            <font-awesome-icon :icon="faIcon.eye" class="fill-current text-ssr-orange2 hover:text-gray-600" />
                        </span>
                        <span class="ml-3 text-gray-600">Preview</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>
</template>

<style scoped>
.spFA {
    width:2rem;
}
.wht {
    color:white!important;
}
.liBG {
    /* background: #E0E0E0; */
}

.liBG:hover {
    /* background: #1e71b890; */ 
}

.liLink {
    border-radius:0.4rem !important;
}
</style>