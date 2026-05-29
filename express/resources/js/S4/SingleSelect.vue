<template>
    <div class="S4SingleSelect min-w-0" ref="elem" :data-tag="props.modelValue" :data-size="props.size" :style="{ 
        width: props.width || '100%',
        maxWidth: props.maxWidth || 'none'
    }">
        <div @click="_fn.toggle" class="flex gap-1 S4SingleSelectSelection select-none relative cursor-pointer bg-gray-100 focus:ring-1 focus:! py-2.5 w-full text-sm text-slate-600 border-[1.9px] pr-8 appearance-none rounded-md px-[15px] border-slate-300 focus:border-slate-200 focus:ring-1 focus:ring-slate-400"
        :class="[(setup.active) ? '!border-[var(--mcolor)] ring-1 ring-[var(--mcolor)]' : 'border-gray-300',
        (props.size == 'sm') ? '!h-[26px] !py-[2px] !text-[12px] !pl-1 !pr-8' : ''
        ]" 
        >
        <img v-if="input && input.img" :src="input.img" class="img-selection w-[20px] h-[auto]"/>
        <div class="icon" v-if="input && input.icon" v-html="input.icon"></div>
        <div v-html="(input && input.text) || ''" class="grow-[1] min-w-0 justify-center items-center w-full overflow-hidden whitespace-nowrap"></div>
        <div class="absolute right-[10px] h-[20px] w-[20px] top-[50%] translate-y-[-50%] transition-all" :class="[(setup.active) ? 'rotate-[180deg]' : 'rotate-[0deg]']">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </div>
    </div>
        <teleport to="body">
            <Transition name="S4SingleSelectAnimation">
            <div v-if="setup.active" ref="elemOption" class="S4SingleSelectOption bg-white border-[.9px] rounded-lg select-none mr-[90px] !z-[1000]" :class="[props.searchable ? '' : 'grid grid-cols-1']" :data-size="props.size" :style="setup.position">
                <div v-if="props.searchable" class="sticky top-0 bg-white border-b border-gray-200 px-3 py-2 z-10" @click.stop>
                    <input 
                        type="text" 
                        v-model="searchTerm" 
                        @click.stop
                        @keydown.stop
                        placeholder="Search..." 
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-[var(--mcolor)] focus:border-[var(--mcolor)]"
                        ref="searchInput"
                    />
                </div>
                <div :class="[props.searchable ? 'max-h-[200px] overflow-y-auto' : '']">
                    <div class="w-full px-4 py-2 cursor-pointer hover:bg-gray-200 truncate !text-sm flex gap-2 justify-center items-center" 
                    
                    v-for="(option) in displayOptions" :key="option.value"
                    :class="[(option.value == props.modelValue) ? (props.searchable ? '' : '!bg-[var(--mcolor)] !text-white') : 'bg-transparent']"
                    :style="(option.value == props.modelValue && props.searchable) ? { 'background-color': 'rgba(209, 129, 11, 0.15)', 'color': 'rgba(209, 129, 11, 1)' } : {}"
                    @click="_fn.update(option)"
                    >
                    <div class="icon" v-if="option && option.icon" v-html="option.icon"></div>
                    <img v-if="option && option.img" :src="option.img" class="w-[30px] h-[auto]"/>
                    <div v-html="option && option.text ? option.text : ''" class="w-full grow-[1] overflow-hidden whitespace-nowrap"></div>
                    </div>
                    <div v-if="props.searchable && displayOptions.length === 0" class="w-full px-4 py-2 text-sm text-gray-500 text-center">
                        No results found
                    </div>
                </div>
            </div>
            </Transition>
        </teleport>
    </div>

    

</template>
<script setup>
import { size } from 'lodash'
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

const elem = ref(null)
const elemOption = ref(null)
const searchInput = ref(null)
const searchTerm = ref('')
const props = defineProps({
        modelValue: {
            default: ''
        },
        options: {
            default: []
        },
        size: {
            type: String,
            default: 'md'
        },
        width: {
            type: String,
            default: '100%'
        },
        maxWidth: {
            type: String,
            default: null
        },
        searchable: {
            type: Boolean,
            default: false
        },
        /** When true, dropdown width is fixed to the trigger width (no sizing by content). */
        fixedDropdownWidth: {
            type: Boolean,
            default: false
        },
    })
    const input = ref({
        text : '',
        value : ''
    })
    const setup = ref({
        active : false,
        position : {
            top : 0,
            left : 0,
            'min-width': 'unset'
        }
    })
    
    // Helper function to strip HTML tags and get plain text
    const stripHtml = (html) => {
        if (!html) return ''
        const tmp = document.createElement('DIV')
        tmp.innerHTML = html
        return tmp.textContent || tmp.innerText || ''
    }
    
    // Computed property to filter options based on search term (only when searchable)
    const filteredOptions = computed(() => {
        if (!props.searchable || !searchTerm.value.trim()) {
            return props.options || []
        }
        
        const searchLower = searchTerm.value.toLowerCase().trim()
        return (props.options || []).filter(option => {
            if (!option) return false
            // Search in text (strip HTML for searching)
            const text = stripHtml(option.text || '')
            // Also search in value and other properties if they exist
            const value = String(option.value || '').toLowerCase()
            const englishName = String(option.englishName || '').toLowerCase()
            const code = String(option.code || '').toLowerCase()
            
            return text.toLowerCase().includes(searchLower) || 
                   value.includes(searchLower) ||
                   englishName.includes(searchLower) ||
                   code.includes(searchLower)
        })
    })
    
    // Display options: filtered if searchable, otherwise all options
    const displayOptions = computed(() => {
        return props.searchable ? filteredOptions.value : (props.options || [])
    })
    watch(()=>setup.value.active,(on)=>{
        if(on){
            _fn.listener()
            // Focus search input when dropdown opens (only if searchable)
            if (props.searchable) {
                nextTick(() => {
                    if (searchInput.value) {
                        searchInput.value.focus()
                    }
                })
            }
        } else {
            // Clear search when dropdown closes
            if (props.searchable) {
                searchTerm.value = ''
            }
        }
    },{deep:true})

    watch(()=>props.modelValue,()=>{
        _fn.initData()
    })
    watch(()=>props.options,()=>{
        _fn.initData()
    },{deep:true})
    onMounted(()=>{
        _fn.initData()
        document.addEventListener("click",_fn.listenerClick)

        // Listen for global open events to close this dropdown when another opens
        window.addEventListener('S4SingleSelect-open', _fn.handleGlobalOpen)

        document.querySelectorAll("*").forEach(child => {
            child.addEventListener("scroll",_fn.listener);
        })

    })
    onBeforeUnmount(()=>{
        document.removeEventListener("click",_fn.listenerClick)

        window.removeEventListener('S4SingleSelect-open', _fn.handleGlobalOpen)

        document.querySelectorAll("*").forEach(child => {
            child.removeEventListener("scroll",_fn.listener);
        })

    })
    const emits = defineEmits(["update:modelValue"])

    const _fn = {
        toggle : ()=>{
            const willOpen = !setup.value.active

            // If this dropdown is about to open, notify all other instances to close
            if (willOpen && elem.value) {
                window.dispatchEvent(
                    new CustomEvent('S4SingleSelect-open', {
                        detail: { elem: elem.value }
                    })
                )
            }

            setup.value.active = willOpen  
        },
        listener : ()=>{
            if (!elem.value) return;
            
            let parentPos = elem.value.getBoundingClientRect()
            //console.log(parentPos)
            //if(elemOption)
            let nTop =  (parentPos.top + parentPos.height + 5)
            let nLeft = parentPos.left
            //console.log("elem",elemOption.value.getBoundingClientRect())
            // if((nTop + 250) > elemOption.value.getBoundingClientRect().height){
            //     console.log("exceed")
            // }

            setup.value.position.top = nTop+"px"
            setup.value.position.left = nLeft+"px"
            nextTick(()=>{
                if (!elemOption.value) return;
                
                let elemOpt = elemOption.value.getBoundingClientRect()
                if((elemOpt.top + elemOpt.height + 5) > window.innerHeight){
                    setup.value.position.top = (elemOpt.top - elemOpt.height - (parentPos.height + 10))+"px"
                }
            })



            setup.value.position['min-width'] = parentPos.width+"px"
            setup.value.position['max-width'] = props.maxWidth || parentPos.width+"px"
            if (props.fixedDropdownWidth) {
                setup.value.position['width'] = parentPos.width + 'px'
            } else {
                delete setup.value.position['width']
            }
        },
        listenerClick : (event)=>{
            // Don't close if clicking on search input (only if searchable)
            if(props.searchable && (event.target === searchInput.value || event.target.closest('input[type="text"]'))) {
                return
            }
            if(!event.target.closest(".S4SingleSelectOption") && 
            (!event.target.closest(".S4SingleSelect") || elem.value !== event.target.closest(".S4SingleSelect"))
            ){
                setup.value.active = false
            }
        },

        initData : ()=>{
            if (!Array.isArray(props.options)) {
                input.value = { text: '', value: '' }
                return
            }
            let target = props.options.find((obj)=>{
                return obj && obj.value == props.modelValue
            })
            //console.log(target)
            if(!target){
                //alert(JSON.stringify(props.options))
                target = props.options[0] || { text: '', value: '' }
                emits("update:modelValue",(target && target.value !== undefined ? target.value : ''))
            }
            input.value = target || { text: '', value: '' }

            
        },
        update : (val)=>{
            //alert(JSON.stringify(val))
            emits("update:modelValue",val.value)
            setup.value.active = false
        },

        handleGlobalOpen: (event) => {
            // Close this dropdown if another instance (different root element) is opening
            const openedElem = event.detail && event.detail.elem
            if (!elem.value) return
            if (openedElem && openedElem !== elem.value) {
                setup.value.active = false
            }
        }
    }
</script>

<style scope>
.S4SingleSelectOption .icon, .S4SingleSelectSelection .icon{
    margin-top:-6px;
    margin-bottom:-6px;
    margin-left:-11px;
}
.S4SingleSelectOption .icon > div, .S4SingleSelectSelection .icon > div{
    width:32px;
    height: 32px;
}

.S4SingleSelectOption p, .S4SingleSelectSelection p{
    display:inline-block!important;
    vertical-align: middle!important;
    padding-left:5px!important;
}
.S4SingleSelectOption{
    position:fixed;
    z-index:99999;
    top:0px;
    left:0px;
    max-height: 250px;
    overflow-y:auto;
    min-width: 100%;
}
.S4SingleSelectOption:not(.grid) {
    display: flex;
    flex-direction: column;
    max-height: 300px;
    overflow: hidden;
}
.S4SingleSelectOption img, .S4SingleSelectSelection img:not(.img-selection){
    width:30px!important;
    height:30px!important;
    object-fit: contain!important;
    display:inline-block!important;
    vertical-align: middle!important;
}

.S4SingleSelectAnimation-enter-active {
  animation: S4SingleSelectAnimation-in 0.3s;
}
.S4SingleSelectAnimation-leave-active {
  animation: S4SingleSelectAnimation-in 0.3s reverse;
}

.S4SingleSelect[data-size='sm'] .S4SingleSelectSelection, .S4SingleSelect[data-size='sm'] .S4SingleSelectSelection *{
    font-size:12px!important;
}
.S4SingleSelectOption[data-size='sm'], .S4SingleSelectOption[data-size='sm'] *{
    font-size:12px!important;
}

.S4SingleSelect[data-size='md'] .S4SingleSelectSelection, .S4SingleSelect[data-size='md'] .S4SingleSelectSelection *{
        font-size: 0.875rem!important;
}
.S4SingleSelectOption[data-size='md'], .S4SingleSelectOption[data-size='md'] *{
        font-size: 0.875rem!important;
}

@keyframes S4SingleSelectAnimation-in {
  0% {
    opacity:0;
    transform: translateX(-10px);
  }
  100% {
    opacity:1;
    transform: translateX(0);
  }
}

</style>