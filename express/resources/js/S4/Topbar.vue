<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCogs, faFileWord, faExternalLinkAlt, faChartBar, faImage } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import helpers from '../helpers';

const { random_str }  = helpers()

const active = ref(true)
const faIcon = ref({
    cogs: faCogs,
    file_word: faFileWord,
    link: faExternalLinkAlt,
    chart: faChartBar,
    image:faImage
})

const activeClass = (current) => {
            return route().current(current) ? "bg-ssr-orange2 wht hover:bg-ssr-orangehover" : "text-gray-600 liBG bg-white hover:bg-ssr-transparent"
        }
const activeClassIcon = (current) => {
    return route().current(current) ? "text-white" : "text-ssr-orange2"
}


</script>

<template> 
    <div>
        <ul class="flex flex-row w-full">
            <li class="my-px mb-1 relative topBarPrev">
                <inertia-link
                    :href="route('questions.index', {
                        'project_code': $page.props.project.code, 
                        'questionnaire_code':$page.props.questionnaire.code
                    })"
                    :class="activeClass('questions.index')"
                    class="flex flex-row items-center h-10 px-3 rounded-sm hover:text-gray-700 liLink">
                    <span class=" pl-1 pr-1 flex items-center justify-center text-lg spFA" :class="activeClassIcon('questions.index')">
                        <font-awesome-icon :icon="faIcon.file_word" class="fill-current hover:text-gray-600" />
                    </span>
                    <span class="toolTipPrev absolute -right-8 px-2 py-1 text-md text-white leading-none transform bg-gray-600 rounded-md h-6 pb-1 pt-1 pl-5 pr-5 -mt-7">
                        Questions                          
                    </span> 
                </inertia-link>
            </li>  
            <li class="my-px  mb-1 relative topBarPrev">
            <inertia-link
                :href="route('questionnaires.settings', {
                    'project_code': $page.props.project.code, 
                    'questionnaire_code':$page.props.questionnaire.code
                })"
                :class="activeClass('questionnaires.settings')"
                class="flex flex-row items-center h-10 px-3 rounded-sm hover:text-gray-700 liLink">
                <span class=" pl-1 pr-1 flex items-center justify-center text-lg spFA" :class="activeClassIcon('questionnaires.settings')">
                    <font-awesome-icon :icon="faIcon.cogs" class="fill-current hover:text-gray-600" />
                </span>
                <span class="toolTipPrev absolute -right-8 px-2 py-1 text-md text-white leading-none transform bg-gray-600 rounded-md h-6 pb-1 pt-1 pl-5 pr-5 -mt-7">
                    Settings                          
                </span>
            </inertia-link>
            </li> 
            <li class="my-px mb-1 relative topBarPrev">
                <inertia-link
                    :href="route('analysis.index', {
                        'project_code': $page.props.project.code, 
                        'questionnaire_code':$page.props.questionnaire.code
                    })"
                    :class="activeClass('analysis.index')"
                    class="flex flex-row items-center h-10 px-3 rounded-sm hover:text-gray-700 liLink">
                    <span class=" pl-1 pr-1 flex items-center justify-center text-lg spFA" :class="activeClassIcon('analysis.index')">
                        <font-awesome-icon :icon="faIcon.chart" class="fill-current hover:text-gray-600" />
                    </span>
                    <span class="toolTipPrev absolute -right-8 px-2 py-1 text-md text-white leading-none transform bg-gray-600 rounded-md h-6 pb-1 pt-1 pl-5 pr-5 -mt-7">
                        Analysis                          
                    </span> 
                </inertia-link>
            </li> 
            <li class="my-px mb-1 relative topBarPrev">
                <inertia-link
                    :href="route('gallery.index', {
                        'project_code': $page.props.project.code, 
                        'questionnaire_code':$page.props.questionnaire.code
                    })"
                    :class="activeClass('gallery.index')"
                    class="flex flex-row items-center h-10 px-3 rounded-sm hover:text-gray-700 liLink">
                    <span class=" pl-1 pr-1 flex items-center justify-center text-lg spFA" :class="activeClassIcon('gallery.index')">
                        <font-awesome-icon :icon="faIcon.image" class="fill-current hover:text-gray-600" />
                    </span> 
                    
                    <span class="toolTipPrev absolute -right-8 px-2 py-1 text-md text-white leading-none transform bg-gray-600 rounded-md h-6 pb-1 pt-1 pl-5 pr-5 -mt-7">
                        Gallery                          
                    </span>
                </inertia-link>
            </li>  
             <li class="my-px mb-1 relative topBarPrev">
                <a :href="$page.props.engine_url+'?code='+$page.props.questionnaire.code+'&preview=1&id=test-'+random_str(5)"
                    target="_blank"
                    class="flex flex-row items-center h-10 px-3 bg-white hover:bg-ssr-transparent liLink">
                    <span class=" pl-1 pr-1 flex items-center justify-center text-lg hover:text-gray-700 spFA">
                        <font-awesome-icon :icon="faIcon.link" class="fill-current text-ssr-orange2 hover:text-gray-600" />
                    </span>
                   <span class="toolTipPrev absolute -right-8 px-2 py-1 text-md text-white leading-none transform bg-gray-600 rounded-md h-6 pb-1 pt-1 pl-5 pr-5 -mt-7">
                        Preview                          
                    </span>
                </a>
            </li>   
        </ul>
    </div> 
</template>


<style scoped>
.spFA {
    width:2rem;
} 
.wht {
    color:white!important;
}
.liLink {
    border-radius:0.4rem !important;
}
.toolTipPrev {    
    opacity: 0;
    transition: 0.3s;
}
.topBarPrev:hover .toolTipPrev {
    opacity: 1;
}
</style>