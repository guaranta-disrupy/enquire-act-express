<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { Head, Link, router } from "@inertiajs/vue3";
import JetApplicationMark from '@/Jetstream/ApplicationMark.vue';
import JetBanner from '@/Jetstream/Banner.vue';
import JetDropdown from '@/Jetstream/Dropdown.vue';
import JetDropdownLink from '@/Jetstream/DropdownLink.vue';
import JetNavLink from '@/Jetstream/NavLink.vue';
import JetResponsiveNavLink from '@/Jetstream/ResponsiveNavLink.vue';

const offsetScroll = ref(0)
defineProps({
    title: String,
});

const mainElem = ref(null)

function handleScroll(event) {
    offsetScroll.value = parseInt(event.currentTarget.scrollTop)
}

function scrollToTop() {
    mainElem.value.scrollTo(0, 0);
}

function scrollToBottom() {
    mainElem.value.scrollTo(0, mainElem.value.scrollHeight);
}

const scrollTo = (elem)=>{
    mainElem.value.scrollTo(0, elem.scrollHeight);
}

onMounted(async()=>{
    await nextTick()
    mainElem.value.addEventListener("PageScrollTo",(d)=>{
        scrollTo(d.detail.target)
    })
})

</script>

<template>
    <div>
        <Head :title="title" />

        <JetBanner />
        <div @click="scrollToTop()"
                class="transition ease-in-out absolute bottom-[50%] right-0 rounded-full bg-[var(--txtcolor)] text-white p-3 drop-shadow-2xl m-2 cursor-pointer hover:bg-black z-50"
                :class="{ 'opacity-0': offsetScroll <= 300, 'opacity-1': offsetScroll > 300 }">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                </svg>
        </div>
        <div @click="scrollToBottom()"
                class="transition ease-in-out absolute  top-[50%] right-0 rounded-full bg-[var(--txtcolor)] text-white p-3 drop-shadow-2xl m-2 cursor-pointer hover:bg-black z-50"
                :class="{ 'opacity-0': offsetScroll <= 300, 'opacity-1': offsetScroll > 300 }">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>

        </div>
        <div ref="mainElem" @scroll="handleScroll($event)" id="mainLayout" class="pl-[144px] h-[calc(100vh)] overflow-auto pt-[90px]">
            <main class="mx-auto h-full" >
                <div class="">
                    <slot name="topbar" />
                </div>
                <div class="overflow-x-hidden">
                    <slot name="content"/>
                </div>                
            </main>
        </div>
    </div>
</template>
<style lang="css" scoped>
#mainLayout {
    scroll-behavior: smooth;
}

</style>