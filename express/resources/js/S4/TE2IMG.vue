<template>
    <div class="TE2IMG" ref="elem" v-show="state.show" :style="{ top: `${state.position.top}px`, left: `${state.position.left}px` }">
        <div class="content grid grid-cols-[80px_1fr] gap-2 p-2 rounded-md overflow-hidden bg-[#444444] text-white">
            <div class="img"><img class="rounded-md overflow-hidden w-full h-auto" :src="state.src"/></div>
            <div class="context p-2">
                <table class="text-xs">
                    <template v-for="device in devices" :key="device.name">
                        <tr><td colspan="2" class="font-bold text-center" :class="device.name !== 'desktop' ? 'pt-2 pb-1' : 'pb-1'">{{ device.label }}</td></tr>
                        <tr><td>Width:</td>
                        <td><input v-model="form[device.widthKey]" placeholder="e.g 100%" class="!border-[var(--mcolor2)] !ring-[var(--mcolor2)] text-xs rounded-sm text-black" type="text" /></td></tr>
                        <tr><td>Float:</td>
                        <td class="ml-1">
                            <div class="grid grid-cols-2 rounded-sm overflow-hidden border border-gray-300 w-full">
                                <button 
                                    v-for="(option, index) in floatOptions" 
                                    :key="option.value"
                                    type="button"
                                    @click="setFloat(device.name, option.value)"
                                    :class="getFloatButtonClass(device.floatKey, option.value, index > 0)"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </td></tr>
                    </template>
                </table>
                <div class="grid grid-cols-2 gap-2 pt-2 text-black font-bold">
                    <button class="bg-red-500 rounded-sm px-2 py-1 text-xs" @click="deleteImage">Delete</button>
                    <button class="bg-[var(--mcolor)] rounded-sm px-2 py-1 text-xs" @click="updateImage">Update</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted, onBeforeUnmount, defineExpose, defineEmits } from "vue";

const emits = defineEmits(["update:image"])
const elem = ref(null)

// Simplified state structure
const state = ref({
    show: false,
    target: null,
    src: null,
    position: { top: 0, left: 0 },
})

// Simplified form structure
const form = ref({
    width: null,
    float: "none",
    tabletWidth: null,
    tabletFloat: "none",
    mobileWidth: null,
    mobileFloat: "none",
})

// Configuration data
const devices = [
    { name: 'desktop', label: 'Desktop', widthKey: 'width', floatKey: 'float' },
    { name: 'tablet', label: 'Tablet', widthKey: 'tabletWidth', floatKey: 'tabletFloat' },
    { name: 'mobile', label: 'Mobile', widthKey: 'mobileWidth', floatKey: 'mobileFloat' }
]

const floatOptions = [
    { value: 'left', label: 'Left' },
    { value: 'right', label: 'Right' }
]

// Computed function for button classes
const getFloatButtonClass = (floatKey, value, hasBorder) => {
    const isActive = form.value[floatKey] === value
    return [
        'px-2 py-1 text-xs font-medium transition-colors w-full',
        hasBorder ? 'border-l border-gray-300' : '',
        isActive ? 'bg-[var(--mcolor)] text-white' : 'bg-white text-gray-700 hover:bg-gray-50'
    ]
}

// Property mapping for cleaner code
const propertyMap = {
    desktop: { width: '--imgwidth', float: '--imgfloat' },
    tablet: { width: '--imgwidth-tablet', float: '--imgfloat-tablet' },
    mobile: { width: '--imgwidth-mobile', float: '--imgfloat-mobile' }
}

const loadProperties = (target) => {
    devices.forEach(device => {
        const widthProp = propertyMap[device.name].width
        const floatProp = propertyMap[device.name].float
        
        const width = target.style.getPropertyValue(widthProp)
        const float = target.style.getPropertyValue(floatProp)
        
        if (width) form.value[device.widthKey] = width
        if (float) form.value[device.floatKey] = float
    })
    
    // Fallback to computed style for desktop float
    const computedFloat = window.getComputedStyle(target).float
    if (!form.value.float && computedFloat !== 'none') {
        form.value.float = computedFloat
    }
}

const resetForm = () => {
    form.value = {
        width: null,
        float: "none",
        tabletWidth: null,
        tabletFloat: "none",
        mobileWidth: null,
        mobileFloat: "none",
    }
}

const _fn = {
    setup: (obj) => {
        state.value.target = obj.target
        if (state.value.target) {
            state.value.target.classList.add("ssr-import-image")
            state.value.src = state.value.target.getAttribute('src')
            
            loadProperties(state.value.target)
            alignTooltip()
            state.value.show = true
        }
    }
}

const handleClickOutside = (e) => {
    if (
        state.value.show &&
        elem.value &&
        !state.value.target.contains(e.target) &&
        !elem.value.contains(e.target)
    ) {
        state.value.show = false;
    }
};

const deleteImage = () => {
    state.value.target.remove();
    state.value.target = null
    state.value.src = null
    state.value.show = false;
}

const setFloat = (device, value) => {
    const deviceConfig = devices.find(d => d.name === device)
    if (deviceConfig) {
        // Toggle off if clicking the same button that's already active
        if (form.value[deviceConfig.floatKey] === value) {
            form.value[deviceConfig.floatKey] = ''
        } else {
            form.value[deviceConfig.floatKey] = value
        }
    }
}

const updateImage = () => {
    // Set all CSS properties using the property map
    devices.forEach(device => {
        const widthProp = propertyMap[device.name].width
        const floatProp = propertyMap[device.name].float
        
        state.value.target.style.setProperty(widthProp, form.value[device.widthKey])
        state.value.target.style.setProperty(floatProp, form.value[device.floatKey])
    })
    
    // Reset and hide
    state.value.target = null
    state.value.src = null
    state.value.show = false
    resetForm()
    
    emits('update:image')
}


const alignTooltip = () => {
    const elemCont = elem.value.closest(".don-editor").getBoundingClientRect()
    if (!state.value.target) return
    
    const rect = state.value.target.getBoundingClientRect()
    const tooltipWidth = 320 // Approximate tooltip width
    
    // Calculate default position (right side)
    let left = rect.left - elemCont.left + rect.width + 10
    
    // Check if tooltip would exceed screen width
    const screenWidth = window.innerWidth
    const tooltipRightEdge = rect.left + rect.width + 10 + tooltipWidth
    
    // If tooltip exceeds screen, position it on the left side
    if (tooltipRightEdge > screenWidth) {
        left = rect.left - elemCont.left - tooltipWidth - 10
        
        // Ensure tooltip doesn't go off the left edge of screen
        if (rect.left - tooltipWidth - 10 < 0) {
            left = rect.left - elemCont.left - tooltipWidth + 10
        }
    }
    
    state.value.position = {
        top: rect.top - elemCont.top,
        left: left,
    }
}

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    document.querySelectorAll("*").forEach(child => {
        child.addEventListener("scroll", alignTooltip);
    });
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
    document.querySelectorAll("*").forEach(child => {
        child.removeEventListener("scroll", alignTooltip);
    });

});

defineExpose({ _fn })
</script>

<style lang="css" scoped>
.TE2IMG {
    position: absolute;
    top: 50%;
    left: 50%;
    z-index: 9999;
}
</style>