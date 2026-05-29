<script setup>
import { faCogs, faFileWord, faExternalLinkAlt, faChartBar, faImage, faPaste, faEye, faPalette } from '@fortawesome/free-solid-svg-icons'
import { Link } from "@inertiajs/vue3";
import { ref } from 'vue'
import helpers from '@/helpers.js'
import QuestionnairePresence from '@/components/QuestionnairePresence.vue';
import { usePage } from '@inertiajs/vue3'





const { random_str } = helpers()



const active = ref(true)

const faIcon = {
    cogs: faCogs,
    file_word: faFileWord,
    link: faExternalLinkAlt,
    chart: faChartBar,
    image: faImage,
    paste: faPaste,
    eye: faEye,
    theme: faPalette
}

const links = [
    {
        routeLinks: 'questions.index',
        name: 'Questions',
    },
    // {
    //     routeLinks:`analysis.index`,
    //     name:'Analysis'
    // },
    // {
    //     routeLinks:'gallery.index',
    //     name:'Gallery'
    // },
    {
        routeLinks: `templates.index`,
        name: 'Templates',
    },
    {
        routeLinks: `themes.index`,
        name: 'Design',
    }
]

const activeClass = (current) => {
    return route().current(current) ? "text-white bg-ssr-yellow1 rounded-md" : "liLink hover:text-slate-400"
}
const activeClassIcon = (current) => {
    return route().current(current) ? "text-white" : "text-ssr-orange2"
}
const role = (role) => {
    if (role === 'super') {
        return 'Super Admin';
    } else {
        return 'Admin';
    }
};

</script>

<template>
    <nav class="py-2.5 rounded">
        <div class="w-full">
            <div class="w-full pt-2 h-12 flex gap-[12px] h-full">
                <div class="text-[var(--txtcolor)] mt-[-14px]">
                    
                    <a :href="route('redirect.fluent', $page.props.questionnaire.setup.survey_id)">
                        <!-- <button type='button'
                            class=' flex break-inside bg-ssr-blue2 rounded-lg px-4 py-2.5 w-full hover:bg-ssr-blue1'>
                            <div class='flex items-center justify-between flex-1'>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                    stroke="white" class="w-4 h-4 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                                </svg>
                                <span class='text-sm font-semibold text-white'>Back to Fluent</span>
                            </div>
                        </button> -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-[60px]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 9-3 3m0 0 3 3m-3-3h7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </a>
                </div>
                <!-- <div class="float-left h-10 grid grid-cols-1 content-center ml-4">
                    <div class="text-lg text-slate-800 font-semibold">{{$page.props.questionnaire.name}}</div>
                </div> -->

                <div class="w-full relative text-[24px] font-bold justify-center items-center grow-[1] truncate"> {{ $page.props.survey_name ?? $page.props.questionnaire?.name ?? '' }} </div>

                <!-- Active Users Presence -->
                <div class="flex items-center">
                    <QuestionnairePresence 
                        :questionnaire="$page.props.questionnaire" 
                        :compact="true"
                        :show-join-notifications="true"
                        :show-leave-notifications="false"
                    />
                </div>

                <!-- <div class="relative w-[50px] h-[50px] !rounded-[50%] mt-[-8px] aspect-square">
                        <img :src="$page.props.auth.user.profile_photo_url" class="!rounded-[50%] w-full h-full relative aspect-square"/>
                </div> -->

            </div>
        </div>
    </nav>
</template>

<style scoped>
.spFA {
    width: 2rem;
}

.wht {
    color: white !important;
}

.liLink {
    /* border-radius:0.4rem !important; */
}
</style>

