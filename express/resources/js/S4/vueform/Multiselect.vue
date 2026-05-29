<script setup>
import VueMultiselect from '@vueform/multiselect'
import { ref } from 'vue'

const emit = defineEmits(['tag-removed'])

// Custom handler for tag removal that emits an event
const handleTagRemoveWithEmit = (option, event, handleTagRemove) => {
    // Emit the tag-removed event
    emit('tag-removed', option, event);
    
    // Also call the original handleTagRemove to actually remove the tag
    if (handleTagRemove) {
        handleTagRemove(option, event);
    }
}

 const props = defineProps({
        options: {
            default: []
        },
        placeholder: {
            default: ''
        },
        noResultsText: {
            default: 'No more choices to select'
        },
        value: {
            default: []
        },
        size: {
            default: 'min-h-input'
        },
        wrap: {
            default: false
        },
        max: {
            default: -1
        }
    })
const classes = ref({
    container: props.size+ ' ssr-multiselect relative mx-auto w-full flex items-start justify-end box-border cursor-pointer border border-gray-300 focus:border-ssr-blue3 focus:ring-1 focus:ring-ssr-blue3 rounded-md bg-white text-base leading-snug outline-none',
    containerDisabled: 'cursor-not-allowed opacity-50 bg-ssr-disabled',
    containerOpen: 'rounded-b-none',
    containerOpenTop: 'rounded-t-none',
    containerActive: 'ring-0',
    singleLabel: 'flex items-center absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5',
    multipleLabel: 'flex items-center absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5',
    search: 'w-full absolute inset-0 outline-none appearance-none box-border border-0 text-base font-sans bg-white rounded pl-3.5',
    tags: 'focus:!ring-1 !focus:ring-[var(--mcolor)] flex-grow flex-shrink flex flex-wrap items-start text-xs pr-[2vmin]',
    tag: 'bg-[var(--mcolor2)] !text-black relative text-white text-xs font-semibold py-0.5 pl-2 rounded mr-1 mb-1 flex items-center whitespace-nowrap max-w-[530px] pl-6 pr-4',
    tagDisabled: 'pr-2 !bg-gray-400 text-white',
    tagRemove: 'absolute left-0 flex items-center justify-center p-1 mx-0.5 rounded-sm hover:bg-black hover:bg-opacity-10 group',
    tagRemoveIcon: ' bg-multiselect-remove bg-center bg-gray-200 bg-no-repeat opacity-30 inline-block w-3 h-3 group-hover:opacity-60 rounded-lg',
    //tagsSearchWrapper: 'inline-block relative mx-[0px] mb-[0px] flex-grow flex-shrink h-[unset] w-full',
    // tagsSearch: 'absolute inset-0 border-0 outline-none appearance-none p-0 text-base font-sans box-border w-full',
    //tagsSearchCopy: 'invisible whitespace-pre-wrap inline-block h-px',
    placeholder: 'flex text-sm items-start h-[auto] absolute left-0 top-0 w-full pointer-events-none bg-transparent leading-snug text-gray-400 whitespace-nowrap overflow-hidden text-ellipsis',
    caret: 'bg-multiselect-caret bg-center bg-no-repeat w-4 h-4 py-px box-content mr-1 absolute top-[50%] translate-y-[-50%] bottom-0 z-10 opacity-40 flex-shrink-0 flex-grow-0 transition-transform transform pointer-events-none rounded-full mb-1',
    caretOpen: 'rotate-180 pointer-events-auto',
    clear: 'pr-1 relative opacity-40 transition duration-300 flex-shrink-0 flex-grow-0 flex hover:opacity-80 mb-4',
    clearIcon: 'absolute top-[-15px] right-[4px] bg-multiselect-remove bg-center bg-no-repeat w-3 h-3 py-px box-content inline-block border-2 rounded-full',
    spinner: 'bg-multiselect-spinner bg-center bg-no-repeat w-4 h-4 z-10 mr-3.5 animate-spin flex-shrink-0 flex-grow-0',
    dropdown: 'max-h-36 absolute -left-px -right-px -bottom-1 transform translate-y-full border border-gray-300 -mt-px overflow-y-scroll z-50 bg-white flex flex-col rounded-b text-xs shadow-lg',
    dropdownTop: '-translate-y-full top-px bottom-auto flex-col-reverse rounded-b-none rounded-t',
    dropdownHidden: 'hidden',
    options: 'flex flex-col p-0 m-0 list-none text-xs '+(props.wrap ? 'flex-wrap !flex-row' : ''),
    optionsTop: 'flex-col-reverse',
    option: 'flex items-center justify-start box-border text-left cursor-pointer text-base leading-snug py-1 px-2 text-xs',
    optionPointed: 'text-gray-800 bg-gray-100',
    optionSelected: 'text-white bg-green-500',
    optionDisabled: 'text-gray-300 cursor-not-allowed',
    optionSelectedPointed: 'text-white bg-green-500 opacity-90',
    optionSelectedDisabled: 'text-green-100 bg-green-500 bg-opacity-50 cursor-not-allowed',
    noOptions: 'py-2 px-3 text-xs text-gray-600 bg-white',
    noResults: 'py-2 px-3 text-gray-600 bg-white text-sm ',
    fakeInput: 'bg-transparent absolute left-0 right-0 -bottom-px w-full h-px border-0 p-0 appearance-none outline-none text-transparent',
    spacer: 'h-4 py-px box-content',
})



</script>
<template>
    <!-- <vue-multiselect 
        :options="options" 
        :noResultsText="noResultsText"
        mode="tags"
        :placeholder="placeholder"
        :close-on-select="false"
        :searchable="false"
        :createTag="true" 
        :classes="classes"
        :value="value"> -->
        <vue-multiselect 
        :options="options" 
        :noResultsText="noResultsText"
        mode="tags"
        :max="props.max"
        :placeholder="placeholder"
        :close-on-select="false"
        :searchable="false"
        :createTag="true" 
        :classes="classes"
        track-by="label"
        label="label"
        :value="value"> 
        <template #tag="{ option, handleTagRemove, disabled }">
                <div
                    class="multiselect-tag !rounded-md custom-select-tags !text-black !bg-gray-200 border inline-flex items-center gap-1 max-w-full"
                    :class="{ 'is-disabled': disabled }"
                >
                    <div class="tag-text text-[12px] py-2 font-normal text-black min-w-0 flex-1" v-html="option.label"></div>
                    <span
                        v-if="!disabled"
                        class="multiselect-tag-remove text-[10px] !text-red-700 leading-none flex-shrink-0 ml-1"
                        @mousedown.prevent="handleTagRemoveWithEmit(option, $event, handleTagRemove)"
                    >
                        ✕
                    </span>
                </div>
            </template>

            <!-- Customize the dropdown options -->
            <template #option="{ option }">
                <div class="flex items-center space-x-2 custom-option-tags">
                    <div v-html="option.label"></div>
                </div>
            </template>

        </vue-multiselect>
</template>
<style>
@import '@vueform/multiselect/themes/tailwind.css';
.custom-select-tags img, .custom-option-tags img {
    width:50px!important;
    height:auto!important;
    display:inline-block!important;
}
.custom-select-tags p, .custom-option-tags p, 
.custom-select-tags div, .custom-option-tags div
{
    display:inline-block!important;
}

</style>
<style lang="css" scoped>
.tag-text{
    display:block!important;
    overflow:hidden;
    text-overflow:ellipsis;
    word-break:break-word;
    white-space:normal;
    max-width:530px;
    line-height:1.15rem;
}
.min-h-input{
    background-color:#f3f4f6;
    padding:10px;
}

.min-h-input:focus-within{
border:2px solid var(--mcolor);
}

.ssr-multiselect.sm{
    padding-left:6px;
    padding-right: 6px;
    padding-top:6px;
    padding-bottom:4px;
    background-color:#f3f4f6;
    
}

.ssr-multiselect.sm /deep/ *{
    font-size:12px;
}
.ssr-multiselect.sm /deep/ .custom-select-tags img, .ssr-multiselect.sm /deep/ .custom-option-tags img {
    width:20px!important;
    height:auto!important;
}

.ssr-multiselect.sm /deep/ .custom-select-tags div{
    padding:2px!important;
    
}

.ssr-multiselect /deep/ .custom-option-tags div{
    padding-top:4px!important;
    padding-bottom:4px!important;
}

/* 
.ssr-multiselect /deep/ .multiselect-wrapper[aria-expanded="false"] .inline-block.relative{
    display:none!important;
} */
</style>

<style>
.ssr-multiselect /deep/ ul.wrap-items {
    display: flex!important;
    flex-wrap: wrap!important;
    flex-direction: row!important;
}
</style>