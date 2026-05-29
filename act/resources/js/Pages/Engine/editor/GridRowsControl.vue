<template>
    <div class="grid-rows-control col-span-2">
        <!-- <label class="block text-xs font-medium text-gray-700 mb-1">
            Grid Rows (Header, Prime/Target, Footer)
        </label> -->
        <div class="grid grid-cols-3 gap-2">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Header
                </label>
                <input
                    type="number"
                    v-model="header"
                    @input="updateValue"
                    class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#15919A] focus:border-[#15919A] text-sm"
                    placeholder="Header"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Prime/Target
                </label>
                <input
                    type="number"
                    v-model="primeTarget"
                    @input="updateValue"
                    class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#15919A] focus:border-[#15919A] text-sm"
                    placeholder="Prime/Target"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Footer
                </label>
                <input
                    type="number"
                    v-model="footer"
                    @input="updateValue"
                    class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#15919A] focus:border-[#15919A] text-sm"
                    placeholder="Footer"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits } from "vue";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["update:modelValue"]);

const header = ref(0);
const primeTarget = ref(0);
const footer = ref(0);

const updateValue = () => {
    emit(
        "update:modelValue",
        `${header.value || 0}% ${primeTarget.value || 0}% ${footer.value || 0}%`
    );
};

watch(
    () => props.modelValue,
    (newValue) => {
        const parts = (newValue || "").split(" ");
        header.value = parseFloat(parts[0]) || 0;
        primeTarget.value = parseFloat(parts[1]) || 0;
        footer.value = parseFloat(parts[2]) || 0;
    },
    { immediate: true }
);
</script>
