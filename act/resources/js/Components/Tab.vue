<script setup>
import { appBase } from "@/utils/basePath";
import { ref, onMounted, onUnmounted, watch } from "vue";
import filter from "lodash/filter";

const props = defineProps({
    tabs: { type: Array, required: true },
    resetSignal: { type: Number, required: false, default: 0 },
});

const emit = defineEmits(["selected"]);

const tab_class = (tab) => {
    const base =
        "w-full py-5 gap-2 flex flex-col rounded-none items-center text-[14px] font-bold border border-gray-200 duration-300 transition-all ease-in-out";
    const isActive = opened.value === tab.key;
    const isDisabled = tab.disabled;

    if (isDisabled) {
        return `${base} bg-gray-100 text-gray-400 cursor-not-allowed opacity-60`;
    }

    return `${base} ${
        isActive
            ? "bg-[#257556] text-white"
            : "bg-white text-[#323232] hover:bg-[#257556] hover:text-white"
    }`;
};

const selected = ref(filter(props.tabs, "active")[0]?.key);
const opened = ref(selected.value); // tracks currently opened content
const isTransitioning = ref(false); // prevents rapid clicking during transitions

const select = (key) => {
    // Prevent rapid clicking during transitions
    if (isTransitioning.value) return;

    // Check if the tab is disabled
    const tab = props.tabs.find((t) => t.key === key);
    if (tab && tab.disabled) {
        return; // Don't do anything if tab is disabled
    }

    if (opened.value === key) {
        // Same button clicked - just close
        opened.value = null;
        selected.value = null;
        emit("selected", null);
    } else {
        // Different button clicked - transition properly
        isTransitioning.value = true;

        if (opened.value) {
            // Hide current panel first
            opened.value = null;
            selected.value = null;

            // Wait for close animation to complete, then open new one
            setTimeout(() => {
                opened.value = key;
                selected.value = key;
                emit("selected", key);
                isTransitioning.value = false;
            }, 300); // Match the transition duration
        } else {
            // No panel open - open immediately
            opened.value = key;
            selected.value = key;
            emit("selected", key);
            isTransitioning.value = false;
        }
    }
};

// Expose the select method for parent components
defineExpose({
    select,
});

// Click outside to close functionality
const handleClickOutside = (event) => {
    // Don't close during transitions
    if (isTransitioning.value) return;

    const tabContainer = event.target.closest(".tab-container");
    const contentPanel = event.target.closest(".content-panel");

    // If clicked outside both the tab container and content panel, close the panel
    if (!tabContainer && !contentPanel && opened.value) {
        opened.value = null;
        selected.value = null;
        emit("selected", null);
    }
};

onMounted(() => {
    // document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    // document.removeEventListener("click", handleClickOutside);
});

// Allow parent to reset active/opened state (e.g., after save)
watch(
    () => props.resetSignal,
    () => {
        opened.value = null;
        selected.value = null;
    }
);
</script>

<template>
    <!-- Add Round Start -->
    <div class="flex flex-row">
        <div class="flex flex-row">
            <!-- Sidebar Start -->
            <div
                class="tab-container bg-white w-[6rem] flex-shrink-0 flex flex-col text-[#241f1f] h-[92.5vh] z-[20] overflow-y-auto"
            >
                <div class="max-w-8xl mx-auto">
                    <div class="text-sm text-center text-gray-500">
                        <div class="w-[6rem]">
                            <ul class="flex flex-wrap -mb-px flex-col">
                                <template v-for="(tab, key) in tabs" :key="key">
                                    <li v-if="tab.key !== 'preview'" class="">
                                        <button
                                            :class="tab_class(tab)"
                                            @click="select(tab.key)"
                                            :disabled="tab.disabled"
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <span
                                                v-html="tab.icon"
                                                class="w-[30px] h-[30px] transition-all"
                                            ></span>
                                            <span
                                                class="text-[14px] font-bold"
                                                >{{ tab.text }}</span
                                            >
                                        </button>
                                    </li>
                                    <li v-else class="">
                                        <a
                                            :target="tab.target"
                                            :class="tab_class(tab)"
                                            :href="
                                                tab.href
                                                    ? tab.href
                                                    : appBase() +
                                                      '/survey-test?preview=1&survey_id=' +
                                                      tab.surveyId +
                                                      '&id=test'
                                            "
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <span
                                                v-html="tab.icon"
                                                class="w-[30px] h-[30px] transition-all"
                                            ></span>
                                            <span
                                                class="text-[14px] font-bold"
                                                >{{ tab.text }}</span
                                            >
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Sidebar End -->

            <!-- Sidebar content start -->
            <Transition
                name="slide-fade"
                enter-active-class="transition-all duration-300 ease-out"
                leave-active-class="transition-all duration-300 ease-in"
                enter-from-class="transform translate-x-[-100%] opacity-0"
                enter-to-class="transform translate-x-0 opacity-100"
                leave-from-class="transform translate-x-0 opacity-100"
                leave-to-class="transform translate-x-[-100%] opacity-0"
                @before-enter="isTransitioning = true"
                @after-enter="isTransitioning = false"
                @before-leave="isTransitioning = true"
                @after-leave="isTransitioning = false"
            >
                <div
                    v-if="opened"
                    class="content-panel w-full h-[92.5vh] z-10 bg-white border rounded-md shadow-2xl relative"
                    :class="{ 'pointer-events-none': isTransitioning }"
                >
                    <slot />
                </div>
            </Transition>
            <!-- Sidebar content end -->
        </div>
        <!-- Add Round End -->
    </div>
</template>

<style scoped>
/* Enhanced smooth transitions */
.content-panel {
    backdrop-filter: blur(10px);
}

/* Custom transition classes for smoother animations */
.slide-fade-enter-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-fade-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-fade-enter-from {
    transform: translateX(-100%);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateX(-100%);
    opacity: 0;
}

/* Smooth hover effects for tab buttons */
/* .tab-container button {
    position: relative;
    overflow: hidden;
}

.tab-container button::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    transition: left 0.5s;
}

.tab-container button:hover::before {
    left: 100%;
} */

/* Ensure smooth content transitions */
/* .content-panel * {
    transition: all 0.2s ease-in-out;
} */
</style>
