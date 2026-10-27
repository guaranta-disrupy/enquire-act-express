<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetLabel from '@/Jetstream/Label.vue';
import S4Button from '@/S4/Button.vue';
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSpinner, faSave, faCode, faArrowRight, faMousePointer, faCheck, faChevronRight, faChevronLeft } from '@fortawesome/free-solid-svg-icons'
import S4GallerySelect from '@/S4/GallerySelect.vue';
import S4Input from '@/S4/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import { computed, ref, reactive, defineExpose } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const { toast } = helpers()

const props = defineProps({
    modal: {
        default: {
            add_edit: false,
            method: 'add',
            data: null,
        }
    },
})

// const custom = ref({
// customCSS: "/* WILD NUTRITION THEME */\n\n@import url('https://fonts.googleapis.com/css2?family=Frank+Ruhl+Libre:wght@300;400&display=swap');\n\n.dQuestion>.body>.question {\n    font-family: 'Frank Ruhl Libre', serif;\n}\n\n.dRadio>label {\n    border-radius: 0px;\n}\n.dCheckbox>label {\n    border-radius: 0px;\n}\n\n.custom_wn_logo {\n    width: 50vmin;\n    left: 50%;\n    transform: translateX(-50%);\n    position: relative;\n    padding: 3vmin 2vmin;\n}\n\n.dQuestion>.body>.choices {\n    font-family: 'Frank Ruhl Libre', serif;\n}\n\n.dWarn {\n    font-family: 'Frank Ruhl Libre', serif;\n}\n\n.dBtn {\n    border-radius: 0vmin;\n    font-family: 'Frank Ruhl Libre', serif;\n}\n\n.dInput input, .dInput select {\n    border-radius: 0vmin;\n}\n\n.dSelect .vue-select {\n    border-radius: 0vmin;\n}\n\nbutton.dBtn.next::after {\n    content: url(\"data:image/svg+xml;charset=utf-8,%3Csvg viewBox='0 0 7 12' fill='%23fff' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M.813.14l5.961 5.468c.024.017.058.01.08.033.1.099.142.229.136.358a.468.468 0 01-.137.354c-.02.022-.054.017-.078.033l-5.96 5.466a.477.477 0 01-.674-.674l5.65-5.182L.141.812A.476.476 0 11.813.14z'/%3E%3C/svg%3E\");\n    position: absolute;\n    top: 50%;\n    display: block;\n    width: 1.5vmin;\n    height: 1.5vmin;\n    -webkit-transform: translateY(-50%);\n    transform: translateY(-50%);\n    margin-top: -1.1vmin;\n    right:2vmin;\n    transition:all .3s;\n}\n\nbutton.dBtn:hover.next::after {\n    animation: custom_btn_move .6s;\n}\n\n@keyframes custom_btn_move{\n      0%{\n         right:2vmin;\n         opacity:1;\n      }\n      25%{\n          right:1vmin;\n          opacity:0;     \n      }\n      50%{\n          right:3vmin;\n          opacity:0;         \n      }\n      100%{\n          right:2vmin;\n          opacity:1;     \n      }\n}\n\n.dWarn {\n    border: 0vmin solid var(--warning-msg-border-color);\n    border-radius: 0px;\n}\n\n/* WILD NUTRITION THEME END */",
// customJS: "var link = document.createElement('link');\n        link.rel = 'stylesheet';\n        link.href = 'https://fonts.googleapis.com/css2?family=Frank+Ruhl+Libre:wght@300;400&display=swap';\n\n        document.head.appendChild(link);\n        const style = document.createElement('style');\n       const style2 = document.createElement('style');\n\n        style.textContent = `\n            .dSpinerImg {\n              display: none !important;\n            }\n        `;\n           style2.textContent = `\n               .dSpinerCont .content {\n            font-family: 'Frank Ruhl Libre', serif;\n    }\n        `;\n        document.head.appendChild(style);\n          document.head.appendChild(style2);"
// })

const gallery = reactive({
    show: false,
    index: -1,
});

const showGallery = () => {
    gallery.show = true;
};

const closeGallery = () => {
    gallery.show = false;
};

const selectGallery = (image) => {
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    form.setup.design['background-img'] = image.file_path
        ? `${fluentBase}/storage/${image.file_path}`
        : `${usePage().props.app_url}/gallery/${image.filename || image.file_name}`;
    closeGallery();
};

const styleButton = computed(() => {
    return {
        '--button-color': form.setup.design['button-color'],
        '--button-hover-color': form.setup.design['button-hover-color'],
        '--button-text-color': form.setup.design['button-txt-color'],
    };
})
const styleTableList = computed(() => {
    return {
        '--list-bg-color1': form.setup.design['tbl-block-color1'],
        '--list-bg-color2': form.setup.design['tbl-block-color2'],
        '--list-lbl-color': form.setup.design['tbl-lbl-color'],
    };
})
const styleImageSelection = computed(() => {
    return {
        '--isb-color': form.setup.design['isb-color'],
        '--isb-txt-color': form.setup.design['isb-txt-color'],
        '--isb-selected-color': form.setup.design['isb-selected-color'],
        '--isb-selected-txt-color': form.setup.design['isb-selected-txt-color'],
        '--isb-hover-color': form.setup.design['isb-hover-color'],
        '--isb-hover-txt-color': form.setup.design['isb-hover-txt-color'],
    };
})
const styleCalendarSelected = computed(() => {
    return {
        '--cal-today-color': form.setup.design['cal-today-color'],
        '--cal-hover-color': form.setup.design['cal-hover-color'],
        '--cal-selected-color': form.setup.design['cal-selected-color'],
        '--cal-lbl-color': form.setup.design['cal-lbl-color'],
        '--cal-disable-lbl-color': form.setup.design['cal-disable-lbl-color'],
    };
})
const styleImgBrightness = computed(() => {
    return {
        '--background-img-brightness': form.setup.design['background-img-brightness'],
    };
})
const tableList = computed(() => {
    return [
        'Old people are funny',
        'It is ok to lie',
        'Waiting is boring',
        'Pizza is the best',
        'English is fun!',
    ]
})
const imageSelection = computed(() => {
    return {
        'emoticons': [
        ],
        'labels': [
            'Very easy to complete',
            'Quite easy to complete',
            'Neither easy nor difficult to complete',
            'Quite difficult to complete',
            'Very difficult to complete'
        ]
    }
})

const emit = defineEmits(["showModal"])

const imageSelected = ref(1)
const calSelected = ref(9)
const faIcon = ref({
    save: faSave,
    spinner: faSpinner,
    edit: faEdit,
    code: faCode,
    cont: faArrowRight,
    hand: faMousePointer,
    tick: faCheck,
    aLeft: faChevronLeft,
    aRight: faChevronRight
})
const form = useForm({
    name: '',
    setup: {
        design: {
            /* BACKGROUND */
            'background-color': '#FFFFFF',
            'background-img': '',
            'background-img-brightness': '100%',

            /* INSTRUCTION */
            'question-txt-color': '#2c3e50',
            'question-margin-top': '14vmin',
            'question-margin-bottom': '14vmin',
            'question-margin-left': '8vmin',
            'question-margin-right': '8vmin',

            /* BUTTONS */
            'button-color': '#257556',
            'button-hover-color': '#e17126',
            'button-txt-color': '#ffffff',


            /* WARNING */
            'warning-msg-color': '#f15a2a',
            'warning-msg-txt-color': '#ffffff',
            'warning-msg-border-color': '#b12b01',

            /* CHOICE BOX */
            'choice-box-color': '#ffffff',
            'choice-box-border-color': '#ededed',


            /* TABLE */
            'tbl-header-color': '#1276be',
            'tbl-header-lbl-color': '#ffffff',
            'tbl-block-color1': '#F6F6F6',
            'tbl-block-color2': '#ffffff',
            'tbl-lbl-color': '#2c3e50',
            'tbl-hint-color': '#d3e7ff',
            'tbl-hint-lbl-color': '#1276be',
            'tbl-border-color': '#ededed',

            /* RADIO */
            'radio-color': '#ededed',
            'radio-border-color': '#dbd7d7',
            'radio-selected-color': '#1276be',
            'radio-lbl-color': '#2c3e50',

            /* CHECKBOX */
            'checkbox-color': '#ededed',
            'checkbox-border-color': '#dbd7d7',
            'checkbox-selected-color': '#1276be',
            'checkbox-lbl-color': '#2c3e50',

            /* TEXTBOX */
            'tb-color': '#ffffff',
            'tb-border-color': '#dbd7d7',
            'tb-txt-color': '#2c3e50',
            'tb-lbl-color': '#2c3e50',
            'tb-error-color': '#faddd5',
            'tb-error-txt-color': '#2c3e50',

            /* CALENDAR */
            'cal-color': '#ffffff',
            'cal-border-color': '#dbd7d7',
            'cal-lbl-color': '#2c3e50',
            'cal-disable-lbl-color': '#808080',
            'cal-today-color': '#ededed',
            'cal-hover-color': '#ededed',
            'cal-selected-color': '#1276be',

            /* SELECTBOX */
            'sb-color': '#ededed',
            'sb-border-color': '#dbd7d7',
            'sb-txt-color': '#2c3e50',
            'sb-selected-color': '#0c5ba8',
            'sb-selected-txt-color': '#ffffff',
            'sb-hover-color': '#ededed',
            'sb-hover-txt-color': '#2c3e50',


            /* IMAGE SELECTION */
            'isb-color': '#ffffff',
            'isb-txt-color': '#2c3e50',
            'isb-selected-color': '#0c5ba8',
            'isb-selected-txt-color': '#ffffff',
            'isb-hover-color': '#ededed',
            'isb-hover-txt-color': '#2c3e50',

            /* SLIDER */
            'slider-line-color': '#dbd7d7',
            'slider-default-color': '#dbd7d7',
            'slider-default-selected-color': '#ffffff',
            'slider-lbl-color': '#2c3e50',
            'slider-option-color': '#dbd7d7'
        },
    },
}, {
    resetOnSuccess: true,
})

const showModal = (table, modal, method, index = -1) => {
    emit('showModal', table, modal, method, index)
}
const close = () => {
    form.reset()
    emit('close')
}
const AddEditTheme = () => {
    console.log("themeFORM", form)
    if (props.modal.method == 'add') {
        var web_route = 'themes.store'
        var params = [
            usePage().props.questionnaire.code
        ]
    }
    else if (props.modal.method == 'copy') {
        var params = [

            usePage().props.questionnaire.code,
            props.modal.data.id
        ]
        form.post(route('themes.copy', params), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Theme Copied', {
                    timeout: 2000
                });
                close()
            },
        })
    }
    else {
        var web_route = 'themes.update'
        var params = [
            usePage().props.questionnaire.code,
            props.modal.data.id
        ]
    }

    form.post(route(web_route, params), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Theme ${props.modal.method == 'add' ? 'added' : 'updated'}`, {
                timeout: 2000
            })
            close()
        },
        onError: () => {
            //code
        },
        onFinish: () => {
            //code
        }
    })
}


</script>
<template>
    <jet-dialog-modal :show="modal.add_edit" :closeable="false" maxWidth="7xl" @close="close()">
        <template #title>
            <span v-if="modal.method == 'add'">Add Theme</span>
            <span v-if="modal.method == 'update'">Update Theme</span>
            <span v-else-if="modal.method == 'copy'">Copy Theme</span>
        </template>

        <template #content v-if="modal.method == 'copy'">
            <div v-if="1 ? form.setup = modal.data.setup : 1">
                <span v-if="1 ? form.name = modal.data.text : 1">Are you sure you want to copy this theme?</span>
            </div>
        </template>

        <template #content v-else>
            <div id="themeCont" style="height: 77vmin; overflow:scroll;overflow-x: hidden;"
                v-if="modal.method == 'update' ? form.setup = modal.data.setup : 1">
                <div v-if="modal.method == 'update'" class="grid grid-cols-1 mb-5">
                    <jet-label class="mt-2">Name</jet-label>
                    <div v-if="1 ? form.name = modal.data.text : 1">
                        <s4-input v-model="modal.data.text" color="ssrGray" type="text" class="block w-full" />
                        <jet-input-error :message="form.errors.name" />
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 mb-5">
                    <jet-label class="mt-2">Name</jet-label>
                    <div>
                        <s4-input v-model="form.name" color="ssrGray" type="text" class="block w-full" />
                        <jet-input-error :message="form.errors.name" />
                    </div>
                </div>

                <!-- SET 1 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="h-60 bg-gray-50 border-2 border-dashed border-right border-gray-300">
                        <div class="grid grid-cols-1 md:grid-cols-2 p-5 gap-4">
                            <div class="">
                                <jet-label class="font-bold text-ssr-blue2" style="font-size: 1rem!important;"
                                    for="duration">Survey Background</jet-label>
                                <div class="h-40 border mt-2 border-gray-200 rounded-md w-full max-w-sm grid grid-cols-1 place-items-center"
                                    v-bind:style="{
                                        backgroundColor: form.setup.design['background-color'],
                                    }">
                                    <div>
                                        <img id="imgBr" class="h-36 w-auto" :src="form.setup.design['background-img']"
                                            alt="" v-bind:style="styleImgBrightness">
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <jet-label class="mt-1">Background image</jet-label>
                                <div class="border rounded-md">
                                    <S4GallerySelect placeholder="Image source" class="block w-full h-8 bg-white-100"
                                        v-model="form.setup.design['background-img']" :showGallery="showGallery">
                                    </S4GallerySelect>
                                </div>
                                <jet-label class="mt-2">Background image brightness</jet-label>
                                <div class="border rounded-md p-3 bg-gray-50">
                                    <div class="flex items-center space-x-3">
                                        <input 
                                            type="range" 
                                            :value="parseInt((form.setup.design['background-img-brightness'] || '100').toString().replace('%', ''))"
                                            @input="form.setup.design['background-img-brightness'] = $event.target.value + '%'"
                                            min="0" 
                                            max="100" 
                                            step="1"
                                            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                                        />
                                        <span class="text-sm text-gray-600 min-w-[3rem] text-center">
                                            {{ (form.setup.design['background-img-brightness'] || 100).toString().replace('%', '') }}%
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Background colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['background-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-15 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['background-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />


                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="h-60">
                        <div class="grid grid-cols-1 md:grid-cols-2 p-5 gap-4">
                            <div class="">
                                <jet-label class="font-bold text-ssr-blue2" style="font-size: 1rem!important;"
                                    for="duration">Instruction and Question</jet-label>
                                <div
                                    class="h-40 border mt-2 border-gray-200 bg-white rounded-md w-full max-w-sm grid grid-cols-1 place-items-center">
                                    <div class="">
                                        <p class="font-bold" v-bind:style="{
                                            color: form.setup.design['question-txt-color'],
                                        }">
                                            TEXT INSTRUCTION
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <!-- <div class="grid grid-cols-2 gap-1">
                                    <div>
                                        <jet-label class="mt-1">Left</jet-label>
                                        <div class="border rounded-md">
                                            <s4-input v-model="form.setup.design['question-margin-left']"
                                                color="themeInput" type="text" class="block w-full h-8" />
                                        </div>
                                    </div>
                                    <div>
                                        <jet-label class="mt-1">Top</jet-label>
                                        <div class="border rounded-md">
                                            <s4-input v-model="form.setup.design['question-margin-top']"
                                                color="themeInput" type="text" class="block w-full h-8" />
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-1">
                                    <div>
                                        <jet-label class="mt-2">Right</jet-label>
                                        <div class="border rounded-md">
                                            <s4-input v-model="form.setup.design['question-margin-right']"
                                                color="themeInput" type="text" class="block w-full h-8" />
                                        </div>
                                    </div>
                                    <div>
                                        <jet-label class="mt-2">Bottom</jet-label>
                                        <div class="border rounded-md">
                                            <s4-input v-model="form.setup.design['question-margin-bottom']"
                                                color="themeInput" type="text" class="block w-full h-8" />
                                        </div>
                                    </div>
                                </div> -->
                                <div>
                                    <jet-label class="mt-2">Text colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['question-txt-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['question-txt-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SET 2 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="h-60">
                        <div class="grid grid-cols-1 md:grid-cols-2 p-5 gap-4">
                            <div class="">
                                <jet-label class="font-bold text-ssr-blue2" style="font-size: 1rem!important;"
                                    for="duration">Button</jet-label>
                                <div
                                    class="h-40 border mt-2 border-gray-200 bg-white rounded-md w-full max-w-sm grid grid-cols-1 place-items-center">
                                    <div class="">
                                        <button id="btn" v-bind:style="styleButton" class="rounded-md h-8 px-6">
                                            <span class="text-sm">NEXT</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <jet-label class="mt-1">Button colour</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['button-color']" color="themeInput" type="text"
                                        class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['button-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <jet-label class="mt-2">Button hover colour</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['button-hover-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['button-hover-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Button text colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['button-txt-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['button-txt-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="h-60 bg-gray-50 border-2 border-dashed border-right border-gray-300">
                        <div class="grid grid-cols-1 md:grid-cols-2 p-5 gap-4">
                            <div class="">
                                <jet-label class="font-bold text-ssr-blue2" style="font-size: 1rem!important;"
                                    for="duration">Warning Messages</jet-label>
                                <div
                                    class="h-40 border mt-2 border-gray-200 bg-white rounded-md w-full max-w-sm grid grid-cols-1 place-items-center">
                                    <div class="border rounded-md h-8 pt-1.5 text-sm text-center px-2" v-bind:style="{
                                        color: form.setup.design['warning-msg-txt-color'],
                                        backgroundColor: form.setup.design['warning-msg-color'],
                                        borderColor: form.setup.design['warning-msg-border-color'],
                                    }">
                                        This question requires an answer!
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <jet-label class="mt-1">Text colour</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['warning-msg-txt-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['warning-msg-txt-color']"
                                            color="themeInput" type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <jet-label class="mt-2">Background colour</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['warning-msg-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['warning-msg-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Border colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['warning-msg-border-color']"
                                            color="themeInput" type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['warning-msg-border-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SET 3 -->
                <div class="grid grid-cols-2">
                    <div class="h-72 bg-white-50 border-t-2 border-b-2 border-dashed border-gray-300">
                        <div class="grid grid-cols-1 pt-2 px-5 pb-5">
                            <div class="">
                                <jet-label class="font-bold text-ssr-blue2" style="font-size: 1rem!important;"
                                    for="duration">Table</jet-label>
                                <div class="h-56 w-full flex flex-col justify-center items-center pt-3 border mt-2 border-gray-200 bg-white rounded-md pr-3">
                                    <table class="table-auto ml-3">
                                        <thead>
                                            <tr v-bind:style="{
                                                color: form.setup.design['tbl-header-lbl-color'],
                                                backgroundColor: form.setup.design['tbl-header-color'],
                                            }">
                                                <th class="w-64 h-8"></th>
                                                <th class="w-24">Disagree</th>
                                                <th class="w-24">Agree</th>
                                                <th class="w-24">Neutral</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr id="tblList" v-for="(el, index) in tableList" :key="index"
                                                style="text-align: center;" v-bind:style="styleTableList">
                                                <td style="text-align: left;" class="pl-5">
                                                    {{ el }}
                                                </td>
                                                <td v-for="(el2, index2) in 3" :key="index2" class="py-1">
                                                    <p style="display: inline-block;"
                                                        class="rounded-full w-4 h-4 mt-1 -ml-1 border" v-bind:style="{
                                                            backgroundColor: form.setup.design['radio-color'],
                                                            borderColor: form.setup.design['radio-border-color'],
                                                        }"></p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <!-- <td>
                                                    <font-awesome-icon :icon="faIcon.hand"
                                                        class="w-5 h-4 fill-current text-gray-800 relative ml-1 left-10 -top-6" />
                                                    <p style="display: inline-block; width:7vmin; text-align:center;"
                                                        class="rounded-lg h-5 relative left-10 ml-2 -top-8 text-sm"
                                                        v-bind:style="{
                                                            color: form.setup.design['tbl-hint-lbl-color'],
                                                            backgroundColor: form.setup.design['tbl-hint-color'],
                                                        }">Agree</p>
                                                </td> -->
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="h-72 bg-white-50">
                        <div class="grid grid-cols-2 pl-5 pr-5 pb-5 pt-1 gap-1">
                            <div class="">
                                <jet-label class="mt-2">Table header colour</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['tbl-header-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['tbl-header-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <!-- <jet-label class="mt-2">Header text color</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['tbl-header-lbl-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['tbl-header-lbl-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div> -->
                                <div>
                                    <jet-label class="mt-2">Block colour 1</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['tbl-block-color2']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['tbl-block-color2']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Block colour 2</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['tbl-block-color1']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['tbl-block-color1']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <!-- <jet-label class="mt-2">List text color</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['tbl-lbl-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['tbl-lbl-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div> -->
                                <!-- <jet-label class="mt-2">Hint color</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['tbl-hint-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['tbl-hint-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Hint text color</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['tbl-hint-lbl-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['tbl-hint-lbl-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div> -->
                                <!-- <div>
                                    <jet-label class="mt-2">Table Border color</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['tbl-border-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['tbl-border-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SET 4 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="h-72">
                        <div class="grid grid-cols-1 md:grid-cols-2 p-5 gap-4">
                            <div class="">
                                <jet-label class="font-bold text-ssr-blue2" style="font-size: 1rem!important;"
                                    for="duration">Radio</jet-label>
                                <div
                                    class="h-40 py-4 border mt-2 border-gray-200 bg-white rounded-md w-full max-w-sm grid grid-cols-1 place-items-center">
                                    <div class="">
                                        <p style="display: inline-block;" class="rounded-full w-5 h-5 border" v-bind:style="{
                                            backgroundColor: form.setup.design['radio-selected-color'],
                                            borderColor: form.setup.design['radio-border-color'],
                                        }"></p>
                                        <span class="ml-2 text-md relative -top-1" v-bind:style="{
                                            color: form.setup.design['radio-lbl-color'],
                                        }">Male</span> <br>
                                        <p style="display: inline-block;" class="rounded-full w-5 h-5 border" v-bind:style="{
                                            backgroundColor: form.setup.design['radio-color'],
                                            borderColor: form.setup.design['radio-border-color'],
                                        }"></p>
                                        <span class="ml-2 text-md relative -top-1" v-bind:style="{
                                            color: form.setup.design['radio-lbl-color'],
                                        }">Female</span> <br>
                                        <p style="display: inline-block;" class="rounded-full w-5 h-5 border" v-bind:style="{
                                            backgroundColor: form.setup.design['radio-color'],
                                            borderColor: form.setup.design['radio-border-color'],
                                        }"></p>
                                        <span class="ml-2 text-md relative -top-1" v-bind:style="{
                                            color: form.setup.design['radio-lbl-color'],
                                        }">Other</span>
                                    </div>
                                </div>
                            </div>
                            <div class="-mt-4">
                                <jet-label class="mt-2">Radio colour</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['radio-color']" color="themeInput" type="text"
                                        class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['radio-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <jet-label class="mt-2">Radio border colour</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['radio-border-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['radio-border-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Selected colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['radio-selected-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['radio-selected-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Text colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['radio-lbl-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['radio-lbl-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="h-72 bg-gray-50 border-2 border-dashed border-gray-300">
                        <div class="grid grid-cols-1 md:grid-cols-2 p-5 gap-4">
                            <div class="">
                                <jet-label class="font-bold text-ssr-blue2" style="font-size: 1rem!important;"
                                    for="duration">Checkbox</jet-label>
                                <div
                                    class="h-40 py-4 border mt-2 border-gray-200 bg-white rounded-md w-full max-w-sm grid grid-cols-1 place-items-center">
                                    <div class="">
                                        <p style="display: inline-block;" class="rounded-xs w-5 h-5 border" v-bind:style="{
                                            backgroundColor: form.setup.design['checkbox-selected-color'],
                                            borderColor: form.setup.design['checkbox-border-color'],
                                        }">
                                            <font-awesome-icon :icon="faIcon.tick"
                                                class="w-5 h-4 text-white font-normal mb-0.5" />
                                        </p>
                                        <span class="ml-2 text-md relative -top-1" v-bind:style="{
                                            color: form.setup.design['checkbox-lbl-color'],
                                        }">Banking / Insurance</span> <br>
                                        <p style="display: inline-block;" class="rounded-xs w-5 h-5 border" v-bind:style="{
                                            backgroundColor: form.setup.design['checkbox-color'],
                                            borderColor: form.setup.design['checkbox-border-color'],
                                        }"></p>
                                        <span class="ml-2 text-md relative -top-1" v-bind:style="{
                                            color: form.setup.design['checkbox-lbl-color'],
                                        }">Market Research</span> <br>
                                        <p style="display: inline-block;" class="rounded-xs w-5 h-5 border" v-bind:style="{
                                            backgroundColor: form.setup.design['checkbox-selected-color'],
                                            borderColor: form.setup.design['checkbox-border-color'],
                                        }">
                                            <font-awesome-icon :icon="faIcon.tick"
                                                class="w-5 h-4 text-white font-normal mb-0.5" />
                                        </p>
                                        <span class="ml-2 text-md relative -top-1" v-bind:style="{
                                            color: form.setup.design['checkbox-lbl-color'],
                                        }">Steam Supply</span> <br>
                                        <p style="display: inline-block;" class="rounded-xs w-5 h-5 border" v-bind:style="{
                                            backgroundColor: form.setup.design['checkbox-color'],
                                            borderColor: form.setup.design['checkbox-border-color'],
                                        }"></p>
                                        <span class="ml-2 text-md relative -top-1" v-bind:style="{
                                            color: form.setup.design['checkbox-lbl-color'],
                                        }">Health care Sector</span>
                                    </div>
                                </div>
                            </div>
                            <div class="-mt-4">
                                <jet-label class="mt-2">Checkbox colour</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['checkbox-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['checkbox-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <jet-label class="mt-2">Checkbox border colour</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['checkbox-border-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['checkbox-border-color']"
                                            color="themeInput" type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Selected colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['checkbox-selected-color']"
                                            color="themeInput" type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['checkbox-selected-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Text colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['checkbox-lbl-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['checkbox-lbl-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SET 5 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="h-90 bg-gray-50 border-2 border-dashed border-gray-300">
                        <div class="mb-3">
                            <jet-label class="font-bold text-ssr-blue2 pl-5 pt-3" style="font-size: 1rem!important;"
                                for="duration">Textbox</jet-label>
                            <div class="grid grid-cols-2 gap-2 mt-3 pr-5 border border-gray-200 bg-white rounded-md m-5 pb-4 pt-3">
                                <div class="ml-5">
                                    <p v-bind:style="{
                                        color: form.setup.design['tb-lbl-color'],
                                    }">Label here</p>
                                    <div class="block w-full border rounded-md p-2" v-bind:style="{
                                        backgroundColor: form.setup.design['tb-color'],
                                        borderColor: form.setup.design['tb-border-color'],
                                        color: form.setup.design['tb-txt-color'],
                                    }">Text sample</div>
                                </div>
                                <div>
                                    <p v-bind:style="{
                                        color: form.setup.design['tb-lbl-color'],
                                    }">Label here</p>
                                    <div class="block w-full border rounded-md p-2" v-bind:style="{
                                        backgroundColor: form.setup.design['tb-error-color'],
                                        color: form.setup.design['tb-error-txt-color'],
                                    }">Error text</div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1">
                            <div class="grid grid-cols-3 px-5 pt-2 gap-1">
                                <div>
                                    <jet-label class="mt-2">Textbox colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['tb-color']" color="themeInput" type="text"
                                            class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['tb-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Textbox border colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['tb-border-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['tb-border-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Text colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['tb-txt-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['tb-txt-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 px-5 pt-2 gap-1 pb-4">
                                <div>
                                    <jet-label class="mt-2">Label colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['tb-lbl-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['tb-lbl-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Textbox error colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['tb-error-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['tb-error-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Error text colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['tb-error-txt-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['tb-error-txt-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="h-76 border-b-2 border-dashed border-gray-300">
                        <jet-label class="font-bold text-ssr-blue2 pl-5 pt-3" style="font-size: 1rem!important;"
                            for="duration">Slider</jet-label>
                        <div class="h-auto bg-white mx-5 mt-3 pb-4 pt-3 rounded-md flex justify-center">
                            <div class="w-full max-w-xl border border-gray-200 bg-white rounded-md p-4">
                                <div class="text-center mb-3">
                                    <span class="text-lg" v-bind:style="{
                                        color: form.setup.design['slider-lbl-color'],
                                    }">Sample Text</span>
                                </div>
                                <div class="flex flex-col items-center w-full px-4">
                                    <!-- Slider line -->
                                    <div class="h-1 w-full" v-bind:style="{
                                        backgroundColor: form.setup.design['slider-line-color'],
                                    }"></div>
                                    <!-- Dots -->
                                    <div class="flex justify-between items-center w-full -mt-5">
                                        <div v-for="(el, ind) in 5" :key="ind" class="flex justify-center">
                                            <div v-if="ind === 2"
                                                class="w-8 h-8 border-2 rounded-full bg-white"
                                                v-bind:style="{
                                                    backgroundColor: form.setup.design['slider-default-selected-color'],
                                                    borderColor: form.setup.design['slider-line-color'],
                                                }">
                                            </div>
                                            <div v-else
                                                class="w-6 h-6 rounded-full"
                                                v-bind:style="{
                                                    backgroundColor: form.setup.design['slider-default-color'],
                                                }">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Labels -->
                                    <div class="flex justify-between w-full text-xs">
                                        <span v-for="(el, index) in 5" :key="index"
                                            v-bind:style="{ color: form.setup.design['slider-option-color'] }">
                                            Option {{ index + 1 }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 px-5 pt-5">
                            <div class="grid grid-cols-2 gap-1">
                                <div>
                                    <jet-label class="mt-2">Inactive dots colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['slider-default-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['slider-default-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Selected dots colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['slider-default-selected-color']"
                                            color="themeInput" type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['slider-default-selected-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-1">
                                <div>
                                    <jet-label class="mt-2">Slider line colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['slider-line-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['slider-line-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Slider label colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['slider-lbl-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['slider-lbl-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Text option colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['slider-option-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['slider-option-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SET 6 -->
                <div class="grid grid-cols-2">
                    <div class="h-64 bg-white-50 border-b-2 border-dashed border-gray-300">
                        <div class="grid grid-cols-1 pt-5 px-5 pb-5">
                            <div class="">
                                <jet-label class="font-bold text-ssr-blue2" style="font-size: 1rem!important;"
                                    for="duration">Image Selection</jet-label>
                                <div class="h-40 w-full pt-3 mt-2 grid grid-cols-5 place-items-center p-5">
                                    <div class="inline-block mx-1" v-for="(el, index) in 5" :key="index">
                                        <div id="emote"
                                            class="w-24 h-36 border border-gray-300 rounded-lg grid grid-cols-1 place-items-center"
                                            v-bind:style="styleImageSelection" @click="imageSelected = index"
                                            :class="{ selectedEmote: imageSelected == index }">
                                            <div class="">
                                                <p class="h-16 grid grid-cols-1 place-items-center">
                                                    <img class="w-16" :src="imageSelection.emoticons[index]">
                                                </p>
                                                <p class="text-center" style="font-size: x-small;">
                                                    {{ imageSelection.labels[index] }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="h-64 bg-white-50 border-b-2 border-dashed border-gray-300">
                        <div class="grid grid-cols-2 pl-5 pr-5 pb-5 pt-6 gap-1">
                            <div class="">
                                <div>
                                    <jet-label class="mt-2">Image box colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['isb-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['isb-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Text colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['isb-txt-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['isb-txt-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Image box selected colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['isb-selected-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['isb-selected-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <div>
                                    <jet-label class="mt-2">Text selected colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['isb-selected-txt-color']"
                                            color="themeInput" type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['isb-selected-txt-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Box hover colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['isb-hover-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['isb-hover-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Text hover colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['isb-hover-txt-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['isb-hover-txt-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SET 7 -->
                <div class="grid grid-cols-3">
                    <div class="h-80 col-span-2">
                        <jet-label class="pl-5 pt-3 font-bold text-ssr-blue2" style="font-size: 1rem!important;"
                            for="duration">Calendar</jet-label>
                        <div class="grid grid-cols-3 pl-5 pr-5 pb-5 pt-1 gap-1">
                            <div>
                                <div class="border h-full w-full rounded-md" v-bind:style="{
                                    backgroundColor: form.setup.design['cal-color'],
                                    borderColor: form.setup.design['cal-border-color'],
                                }">
                                    <div class="w-full grid grid-cols-2 my-1">
                                        <div class="flex justify-between">
                                            <font-awesome-icon :icon="faIcon.aLeft"
                                                class="w-5 h-4 fill-current text-gray-400 mt-2 ml-2" />
                                            <p class="text-lg mt-0.5">May</p>
                                            <p></p>
                                        </div>
                                        <div class="h-8 flex justify-between">
                                            <p></p>
                                            <p class="text-lg mt-0.5">2022</p>
                                            <font-awesome-icon :icon="faIcon.aRight"
                                                class="w-5 h-4 fill-current text-gray-400 mt-2 mr-2" />
                                        </div>
                                    </div>
                                    <div class="px-1 grid grid-cols-7 place-items-center text-xs h-5 -mt-1">
                                        <div v-for="(el, day) in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day">
                                            <div class="grid grid-rows-5 gap-2">
                                                <div class="h-7 grid grid-rows-1 place-items-center"
                                                    v-for="(el2, num) in 6" :key="num">
                                                    <p v-if="num == 0" class="font-bold">{{ el }}</p>
                                                    <p class="calDay" v-else-if="num == 1"
                                                        v-bind:style="styleCalendarSelected"
                                                        @click="calSelected = num + day"
                                                        :class="{ selectedDay: calSelected == num + day }">{{ num + day }}
                                                    </p>
                                                    <p class="calDay" v-else v-bind:style="styleCalendarSelected"
                                                        @click="calSelected = ((num - 1) * 7) + (day + 1)"
                                                        :class="{ selectedDay: calSelected == ((num - 1) * 7) + (day + 1) }">
                                                        {{ ((num - 1) * 7) + (day + 1) }}</p>
                                                    <p v-else>
                                                    <p class="calDay" v-if="(((num - 1) * 7) + (day + 1)) <= 31"
                                                        v-bind:style="styleCalendarSelected"
                                                        @click="calSelected = ((num - 1) * 7) + (day + 1)"
                                                        :class="{ selectedDay: calSelected == ((num - 1) * 7) + (day + 1) }"
                                                        v-bind:class="[20 == ((num - 1) * 7) + (day + 1) ? 'todayClass' : '']">
                                                        {{ ((num - 1) * 7) + (day + 1) }}
                                                    </p>
                                                    <p v-else class="calDayExtra" v-bind:style="styleCalendarSelected">
                                                        {{ (((num - 1) * 7) + (day + 1)) - 31 }}
                                                    </p>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-2 grid grid-cols-2 gap-1 ml-8">
                                <div>
                                    <jet-label class="mt-1">Calendar colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['cal-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['cal-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                    <jet-label class="mt-2">Calendar border colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['cal-border-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['cal-border-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                    <div>
                                        <jet-label class="mt-2">Date number colour</jet-label>
                                        <div class="flex border rounded-md">
                                            <s4-input v-model="form.setup.design['cal-lbl-color']" color="themeInput"
                                                type="text" class="block w-full h-8" />
                                            <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                                <s4-input v-model="form.setup.design['cal-lbl-color']"
                                                    color="themeInput" type="color" class="mr-2 color-input" />
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <jet-label class="mt-2">Inactive date colour</jet-label>
                                        <div class="flex border rounded-md">
                                            <s4-input v-model="form.setup.design['cal-disable-lbl-color']"
                                                color="themeInput" type="text" class="block w-full h-8" />
                                            <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                                <s4-input v-model="form.setup.design['cal-disable-lbl-color']"
                                                    color="themeInput" type="color" class="mr-2 color-input" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="">
                                    <jet-label class="mt-1">Today colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['cal-today-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['cal-today-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                    <jet-label class="mt-2">Hover colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['cal-hover-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['cal-hover-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                    <div>
                                        <jet-label class="mt-2">Selected colour</jet-label>
                                        <div class="flex border rounded-md">
                                            <s4-input v-model="form.setup.design['cal-selected-color']"
                                                color="themeInput" type="text" class="block w-full h-8" />
                                            <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                                <s4-input v-model="form.setup.design['cal-selected-color']"
                                                    color="themeInput" type="color" class="mr-2 color-input" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="h-90 bg-gray-50 border-l-2 border-dashed border-gray-300">
                        <jet-label class="pl-5 pt-3 font-bold text-ssr-blue2" style="font-size: 1rem!important;"
                            for="duration">Choice Box</jet-label>
                        <div class="grid grid-cols-1 rounded-md border border-gray-200 bg-white m-5 pb-1 pt-1">
                            <div class="p-2 pb-0">
                                <div class="p-2 rounded-md border" v-bind:style="{
                                    backgroundColor: form.setup.design['choice-box-color'],
                                    borderColor: form.setup.design['choice-box-border-color'],
                                }">
                                    <p style="display: inline-block;" class="rounded-full w-5 h-5 ml-1 mt-1"
                                        v-bind:style="{
                                            backgroundColor: form.setup.design['radio-color'],
                                            borderColor: form.setup.design['radio-border-color'],
                                        }"></p>
                                </div>
                            </div>
                            <div>
                                <div class="p-2">
                                    <div class="p-2 border rounded-md pl-2" v-bind:style="{
                                        backgroundColor: form.setup.design['choice-box-color'],
                                        borderColor: form.setup.design['choice-box-border-color'],
                                    }">
                                        <p style="display: inline-block;" class="w-5 h-5 ml-1 mt-1 border" v-bind:style="{
                                            backgroundColor: form.setup.design['checkbox-color'],
                                            borderColor: form.setup.design['checkbox-border-color'],
                                        }"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pl-5 pr-5 pb-5 pt-3">
                            <div class="grid grid-cols-1 gap-1">
                                <div>
                                    <jet-label class="mt-2">Choice box colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['choice-box-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['choice-box-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Choice box border colour</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['choice-box-border-color']"
                                            color="themeInput" type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['choice-box-border-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SET 8 -->
                <!-- <div class="grid grid-cols-2">
                    <div class="h-80 bg-gray-50 border-2 border-dashed border-gray-300">
                        <jet-label class="pl-5 pt-3 font-bold text-ssr-blue2" style="font-size: 1rem!important;"
                            for="duration">Select Box</jet-label>
                        <div class="grid grid-cols-2 pl-5 pr-5 pb-5 pt-1 gap-1">
                            <div class="">
                                <jet-label class="mt-1">Select box color</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['sb-color']" color="themeInput" type="text"
                                        class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['sb-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <jet-label class="mt-2">Select box border color</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['sb-border-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['sb-border-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Text color</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['sb-txt-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['sb-txt-color']" color="themeInput"
                                                type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Box selected color</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['sb-selected-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['sb-selected-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <jet-label class="mt-1">Text selected color</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['sb-selected-txt-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['sb-selected-txt-color']"
                                            color="themeInput" type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <jet-label class="mt-2">Box hover color</jet-label>
                                <div class="flex border rounded-md">
                                    <s4-input v-model="form.setup.design['sb-hover-color']" color="themeInput"
                                        type="text" class="block w-full h-8" />
                                    <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                        <s4-input v-model="form.setup.design['sb-hover-color']" color="themeInput"
                                            type="color" class="mr-2 color-input" />
                                    </div>
                                </div>
                                <div>
                                    <jet-label class="mt-2">Hover text color</jet-label>
                                    <div class="flex border rounded-md">
                                        <s4-input v-model="form.setup.design['sb-hover-txt-color']" color="themeInput"
                                            type="text" class="block w-full h-8" />
                                        <div class="block w-8 h-8 ml-1 grid grid-cols-1 place-items-center">
                                            <s4-input v-model="form.setup.design['sb-hover-txt-color']"
                                                color="themeInput" type="color" class="mr-2 color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="h-80 border-t-2 border-dashed border-gray-300">

                    </div>
                </div> -->

            </div>
        </template>


        <template #footer>
            <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="AddEditTheme()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else-if="modal.method == 'update'">
                    <span class="pl-3 pr-3 text-white">
                        <!--<font-awesome-icon :icon="faIcon.edit" />-->
                        Update
                    </span>
                </template>
                <template v-else-if="modal.method == 'copy'">
                    <span class="pl-3 pr-3 text-white">
                        <!--<font-awesome-icon :icon="faIcon.copy" />-->
                        Copy
                    </span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3 text-white">
                        <!-- <font-awesome-icon :icon="faIcon.save" /> -->
                        Save
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
    <s4-gallery-modal :modal="gallery" :type="'image'" @close="closeGallery()" @selectGallery="selectGallery" />
</template>



<style scoped>
.color-input {
    padding: 0 !important;
    width: 2.5vmin !important;
    height: 2.5vmin !important;
    transition: background-color 0.3s ease, transform 0.1s ease;
    /* Smooth transition for hover effect */
    cursor: pointer;
    /* Make it clickable */
}

.color-input:hover {
    transform: scale(1.2);
}

/* Custom slider styles */
.slider {
    -webkit-appearance: none;
    appearance: none;
    background: rgb(157, 192, 245);
    cursor: pointer;
}

.slider::-webkit-slider-track {
    background: #d1d5db;
    height: 10px;
    border-radius: 5px;
    border: 1px solid #9ca3af;
}

.slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    background: #3b82f6;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.slider::-webkit-slider-thumb:hover {
    background: #2563eb;
    transform: scale(1.1);
}

.slider::-moz-range-track {
    background: #d1d5db;
    height: 10px;
    border-radius: 5px;
    border: 1px solid #9ca3af;
}

.slider::-moz-range-thumb {
    background: #3b82f6;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.slider::-moz-range-thumb:hover {
    background: #2563eb;
}

.ckbtn {
    border: none;
    border-radius: 0.3rem;
}

input:checked~.dot {
    transform: translateX(100%);
    background-color: #759fd1;
}

#imgBr {
    filter: brightness(var(--background-img-brightness));
}

#btn {
    color: var(--button-text-color);
    background-color: var(--button-color);
}

#btn:hover {
    background-color: var(--button-hover-color);
}

#tblList:nth-child(even) {
    background: var(--list-bg-color1);
    color: var(--list-lbl-color);
}

#tblList:nth-child(odd) {
    background: var(--list-bg-color2);
    color: var(--list-lbl-color);
}

#emote {
    cursor: pointer;
}

#emote:hover {
    background: var(--isb-hover-color);
    color: var(--isb-hover-txt-color);
}

#emote {
    background: var(--isb-color);
    color: var(--isb-txt-color);
}

#emote.selectedEmote {
    border: var(--isb-selected-color) 4px solid;
    color: var(--isb-selected-txt-color);
}

.calDay {
    cursor: pointer;
    color: var(--cal-lbl-color);
}

.calDayExtra {
    cursor: pointer;
    color: var(--cal-disable-lbl-color);
}

.calDay:hover {
    cursor: pointer;
    background: var(--cal-hover-color);
    width: 3.4vmin;
    height: 3.4vmin;
    border-radius: 50%;
    text-align: center;
    padding-top: 0.9vmin;
}

.calDayExtra:hover {
    cursor: pointer;
    background: var(--cal-hover-color);
    width: 3.4vmin;
    height: 3.4vmin;
    border-radius: 50%;
    text-align: center;
    padding-top: 0.9vmin;
}

.calDay.selectedDay {
    cursor: pointer;
    background: var(--cal-selected-color);
    color: var(--cal-lbl-color);
    width: 3.4vmin;
    height: 3.4vmin;
    border-radius: 50%;
    text-align: center;
    padding-top: 0.9vmin;
    color: white;
}

.todayClass {
    cursor: pointer;
    width: 3.4vmin;
    height: 3.4vmin;
    border-radius: 50%;
    text-align: center;
    padding-top: 0.9vmin;
    border: 1px solid var(--cal-today-color);
}
</style>