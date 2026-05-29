<script setup>
import IconsVue from "./Icons.vue";
import { ref, useSlots, computed } from "vue";

// Use an object props definition so we can extend it safely.
const props = defineProps({
    showSlideBar: { type: Boolean, default: true },
    title: { type: String, default: "" },
    // Existing prop – acts as the expanded state, but only when enableExpand is true.
    expand: { type: Boolean, default: false },
    // NEW: opt‑in flag for expandable behaviour (Gallery sets this to true).
    // When false (default), SliderBar behaves like a fixed panel (for Settings.vue).
    enableExpand: { type: Boolean, default: false },
});

const emit = defineEmits(["showSettingHandler"]);

const slots = useSlots();

// Legacy ref kept in case something still relies on it.
const barWidth = ref({
    width: "w-full",
    expand: true,
});

const isExpanded = computed(() => props.expand ?? false);
const expandEnabled = computed(() => props.enableExpand === true);

// Width behaviour:
// - Settings.vue (enableExpand = false): full width of parent (no expand effect)
// - Gallery.vue (enableExpand = true): 345px collapsed, 90vw expanded
const sliderWidth = computed(() => {
    if (!expandEnabled.value) {
        return "100%";
    }
    return isExpanded.value ? "90vw" : "345px";
});

</script>

<template>
    <!-- <div
        class="relative z-40 h-screen"
        @click="close"
    >
    </div> -->
    <div class="">
        <div class="relative">
            <div 
                class="bg-white h-[95vh] box-border relative shadow-lg"
                :style="{ width: sliderWidth, transition: 'width 300ms ease-in-out' }"
            >
                <!-- <div
                    class="font-bold box-border duration-150 transition-all mb-0 h-10 text-slate-500"
                >
                </div> -->
                <!-- <div class="absolute right-6 top-3 flex gap-3 items-center"> -->
                <!-- <slot name="topRightSection"></slot> -->
                <!-- </div> -->

                <!-- <div class="flex gap-8 flex-col mt-4" v-if="!barWidth.expand">
                    <slot name="sidebar" />
                </div> -->
                <div class="relative w-full">
                    <slot name="expand" />
                </div>


                <div class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 z-10" v-if="slots.controller && expandEnabled">
                    <slot name="controller"></slot>
                </div>
            </div>
        </div>
    </div>
</template>
