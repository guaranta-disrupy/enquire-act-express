<script setup>
import { ref } from "vue";
import filter from "lodash/filter";

const props = defineProps(["tabs"]);

const emit = defineEmits(["selected"]);

const tab_class = (tab) => {
    let classes =
        "relative px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 tracking-wide";

    if (tab?.disabled) {
        classes += " text-gray-400 cursor-not-allowed opacity-50 rounded-xl";
    } else if (tab.key === selected.value) {
        classes += " text-white font-semibold shadow-md rounded-xl";
    } else {
        classes += " text-gray-600 hover:bg-gray-100 rounded-xl";
    }

    return classes;
};

const selected = ref(filter(props.tabs, "active")[0]?.key);

const select = (key) => {
    selected.value = key;
    emit("selected", key);
};
</script>

<template>
    <div class="">
        <div class="max-w-8xl mx-auto">
            <div class="pb-4">
                <ul class="flex space-x-2">
                    <template v-for="(tab, key) in tabs" :key="key">
                        <li>
                            <button
                                :href="tab.route"
                                :class="tab_class(tab)"
                                @click="select(tab.key)"
                                :disabled="tab.disabled"
                                class="bg-gray-50 group rounded-xl"
                            >
                                <span
                                    class="relative z-10 flex items-center gap-2"
                                    :class="
                                        tab.key === selected
                                            ? 'text-white'
                                            : 'text-gray-600'
                                    "
                                    ><span v-html="tab.icon"></span> {{ tab.text }}</span
                                >
                                <!-- Active indicator -->
                                <div
                                    v-if="tab.key === selected"
                                    class="bg-[#15919A] absolute inset-0 border-b-[#15919A] rounded-xl"
                                ></div>
                                <!-- Hover effect -->
                                <div
                                    v-if="!tab.disabled && tab.key !== selected"
                                    class="bg-gray-100 absolute inset-0 rounded-xl group-hover:bg-[#15919A20]"
                                ></div>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
    <div class="w-full">
        <slot />
    </div>
</template>

<style scoped>
/* Ensure SVG icons have specific colors while text remains gray-600 */
span[class*="text-white"] svg {
    color: white;
}

span[class*="text-gray-600"] svg {
    color: #15919a;
}

/* Target SVG stroke elements directly - only icons, not text */
:deep(span[class*="text-gray-600"] svg path),
:deep(span[class*="text-gray-600"] svg circle),
:deep(span[class*="text-gray-600"] svg rect) {
    stroke: #15919a !important;
}

:deep(span[class*="text-white"] svg path),
:deep(span[class*="text-white"] svg circle),
:deep(span[class*="text-white"] svg rect) {
    stroke: white !important;
}
</style>
