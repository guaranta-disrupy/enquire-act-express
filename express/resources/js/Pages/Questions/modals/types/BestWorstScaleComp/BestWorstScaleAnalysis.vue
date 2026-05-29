<template>
    <div class="flex flex-col gap-2">


        <div v-if="analysisItems.length === 0" class="p-2"><div class="flex flex-col items-center italic text-slate-400 text-md justify-center content-center p-[20px_20px]">
            <div class="w-full h-auto flex justify-center"><img :src="EMPTY_ICON" class="w-[100px] h-auto !max-h-[unset] pointer-events-none"></div>
            <div class="self-center w-full font-bold pt-[12px] text-center text-balance"> No added analysis yet!</div>
            <div class="pt-4 mb-[24px]">
                    <button 
                    @click="showModal = true"
                    theme="ssrAddColRow" 
                    class="w-[auto] min-w-[100px] flex justify-center max-w-[fit-content] inline-flex items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                >
                    <svg width="32" height="32" viewBox="0 0 980 980" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-74bd0dba="">
                        <path d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z" fill="white" data-v-74bd0dba=""></path>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z" fill="white" data-v-74bd0dba=""></path>
                        <path d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z" stroke="white" data-v-74bd0dba=""></path>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z" fill="white" data-v-74bd0dba=""></path>
                        <path d="M551 270V558M695 414H407" stroke="white" stroke-width="32" stroke-linecap="round" stroke-linejoin="round" data-v-74bd0dba=""></path>
                    </svg>
                    <span class="transition-all duration-300 ease-in-out hover:text-white"> Add</span>
                </button>
        </div>
        </div>
            
        
        </div>

        <!-- Display existing analysis items -->
        <div class="flex flex-col gap-4" v-else>
        <div 
            v-for="(analysis, index) in analysisItems" 
            :key="index"
            class="bg-gray-50 rounded-lg shadow p-4 border border-gray-200"
        >
            <div class="space-y-1 text-gray-700 text-sm">
                <div class="font-medium text-xl">{{ analysis.label }}</div>
                <div><span class="font-medium">Calculation Type:</span> {{ analysis.calculation_type }}</div>
                <div><span class="font-medium">Aggregation Method:</span> {{ analysis.aggregation_method }}</div>
                <div><span class="font-medium">Targets:</span> <div class="flex flex-wrap gap-2 py-2" v-html="formatValues(analysis.values)"></div></div>
            </div>
            <div class="flex justify-end mt-2">
                <button 
                    @click="removeAnalysis(index)"
                    class="text-red-500 hover:text-red-700 text-xs"
                >
                    Remove
                </button>
            </div>
        </div>
        
        <!-- Add New button -->
        <button 
            @click="showModal = true"
            theme="ssrAddColRow" 
            class="w-[auto] max-w-[fit-content] inline-flex items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
        >
            <svg width="32" height="32" viewBox="0 0 980 980" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-74bd0dba="">
                <path d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z" fill="white" data-v-74bd0dba=""></path>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z" fill="white" data-v-74bd0dba=""></path>
                <path d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z" stroke="white" data-v-74bd0dba=""></path>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z" fill="white" data-v-74bd0dba=""></path>
                <path d="M551 270V558M695 414H407" stroke="white" stroke-width="32" stroke-linecap="round" stroke-linejoin="round" data-v-74bd0dba=""></path>
            </svg>
            <span class="transition-all duration-300 ease-in-out hover:text-white"> Add New </span>
        </button>
        </div>
    </div>


    <!-- Add Analysis Modal -->
    <teleport to="body">
        <div 
            v-if="showModal" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click="closeModal"
        >
            <div 
                class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto"
                @click.stop
            >
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Analysis Item</h3>
                </div>
                
                <div class="px-6 py-4 space-y-4">
                    <!-- Label -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                        <input 
                            v-model="formData.label"
                            type="text" 
                            class="block py-2.5 px-0 w-full text-sm bg-transparent border-[1.9px] appearance-none rounded-md px-[15px] border-slate-300 focus:border-slate-200 focus:ring-1 focus:ring-slate-400 !border-gray-300 !bg-gray-100 focus:!border-[var(--mcolor2)] focus:ring-1 focus:!ring-[var(--mcolor)] w-full"
                            placeholder="e.g. Best"
                        />
                    </div>

                    <!-- Calculation Type -->
                    <!-- <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Calculation Type</label>
                        <SingleSelect
                            :options="calculationTypeOptions"
                            :modelValue="formData.calculation_type"
                            @update:modelValue="formData.calculation_type = $event"
                            placeholder="Select calculation type"
                        />
                    </div> -->

                    <!-- Aggregation Method -->
                    <!-- <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Aggregation Method</label>
                        <SingleSelect
                            :options="aggregationMethodOptions"
                            :modelValue="formData.aggregation_method"
                            @update:modelValue="formData.aggregation_method = $event"
                            placeholder="Select aggregation method"
                        />
                    </div> -->

                    <!-- Values -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Values</label>
                        <!-- <VueFormMultiselect 
                            v-model="formData.values"
                            :options="valueOptions"
                            :max="1"
                        /> -->
                        <SingleSelect
                            :options="valueOptions"
                            :modelValue="formData.values[0]"
                            @update:modelValue="($event)=>{
                                formData.values[0] = $event
                                formData.label = valueOptions.find((v) => v.value == $event)?.text || 'Best';
                            }"
                            placeholder="Select aggregation method"
                        />


                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button 
                        @click="closeModal"
                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="saveAnalysis"
                        :disabled="!isFormValid"
                        class="px-4 py-2 text-white rounded-md disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors bg-ssr-orange2 bg-[var(--mcolor)] !hover:bg-[var(--mcolor2)]"
                    >
                        Add Analysis
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>
<script setup>
import {
    ref,
    inject,
    computed,
    onBeforeMount,
} from "vue";
import SingleSelect from "@/S4/SingleSelect.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import EMPTY_ICON from "@/../assets/icons/emptyL.svg";

const add_edit_form = inject("add_edit_form");

// Options for SingleSelect components
const calculationTypeOptions = [
    { text: "Percentage", value: "percentage" },
    { text: "Count", value: "count" }
];

const aggregationMethodOptions = [
    { text: "Sum", value: "sum" },
    { text: "Average", value: "average" },
    { text: "Median", value: "median" }
];

const valueOptions = [
    { text: "Best", value: 1, color: "green" },
    { text: "Net", value: 0, color: "gray" },
    { text: "Worst", value: -1, color: "red" }
];

// Modal state
const showModal = ref(false);

// Form data for new analysis item
const formData = ref({
    label: "Best",
    calculation_type: "percentage",
    aggregation_method: "sum",
    values: [1],
});

// Initialize analysis array if it doesn't exist
onBeforeMount(() => {
    if (!add_edit_form.setup.analysis) {
        add_edit_form.setup.analysis = [];
    }
});

// Computed properties
const analysisItems = computed(() => {
    return add_edit_form.setup.analysis || [];
});

// const valueOptions = computed(() => {
//     let items = [
//          {
//             index : 1,
//             name : "Best",
//             color : "green",
//          },
//          {
//             index : 0,
//             name : "Net",
//             color : "gray",
//          },
//          {
//             index : -1,
//             name : "Worst",
//             color : "red",
//          }
//     ]
    
//     return items.map((value, index) => ({
//         value: value.index || (index + 1),
//         label: "<span style='background-color: "+value.color+";' class='rounded-full p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(value.index || index + 1)+"</span> "+(value.name || `Value ${index + 1}`),
//     }));
// });

const isFormValid = computed(() => {
    return formData.value.label.trim() !== "" &&
           formData.value.calculation_type !== "" &&
           formData.value.aggregation_method !== "" &&
           formData.value.values.length > 0;
});

// Methods
const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const resetForm = () => {
    formData.value = {
        label: "Best",
        calculation_type: "percentage",
        aggregation_method: "sum",
        values: [1],
    };
};

const saveAnalysis = () => {
    if (isFormValid.value) {
        const newAnalysis = {
            label: formData.value.label.trim(),
            calculation_type: formData.value.calculation_type,
            aggregation_method: formData.value.aggregation_method,
            values: formData.value.values,
        };
        if(!add_edit_form.setup?.analysis){
            Object.assign(add_edit_form.setup, { analysis: [] });
        }
        add_edit_form.setup.analysis.push(newAnalysis);
        closeModal();
    }
};

const removeAnalysis = (index) => {
    add_edit_form.setup.analysis.splice(index, 1);
};

const formatValues = (values) => {
    if(!values) return "N/A";
    
    return values.map((value, i) => {
        let target = valueOptions.find((v) => v.value === value);
        return "<div class='bg-gray-200 rounded-full  relative px-2 py-2 text-xs min-w-[20px] block text-center flex gap-1 items-center justify-center'><div class='w-[20px] h-[20px] rounded-full' style='background-color: "+target.color+";'></div><div class='max-w-['fit-content']'>"+(target.text)+"</div></div>";
    }).join(" ");
};
</script>