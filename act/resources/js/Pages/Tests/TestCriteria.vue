<template>
    <div class="grid gap-1 mt-2 hidden" :class="grid_cols">
        <div class="col-span-2">
            <CustomSelect v-model="condition.method" label="Option" size="sm">
                <option value="segment">Segment</option>
                <option value="process">Process</option>
            </CustomSelect>
        </div>
        <div class="col-span-2" v-if="indexes[indexes.length -1] != 0" >
            <CustomSelect v-model="condition.left_operator" label="Left Operator" size="sm">
                <option value="and">AND</option>
                <option value="or">OR</option>
            </CustomSelect>
        </div>
        <template v-if="condition.method == 'process'">
            <div class="col-span-2">
                <CustomInput v-model="condition.var" label="Variable" size="sm" />
            </div>
            <div class="col-span-2">
                <CustomSelect v-model="condition.operator" label="Operation" size="sm">
                    <option value="==">&equals;&equals;</option>
                    <option value="!=">!&equals;</option>
                    <option value=">">&gt;</option>
                    <option value="<">&lt;</option>
                    <option value=">=">&gt;&equals;</option>
                    <option value="<=">&lt;&equals;</option>
                </CustomSelect>
            </div>
            <div class="col-span-2">
                <CustomInput v-model="condition.val" label="Value" size="sm" />
            </div>
        </template>
        <template v-else>
            <div class="col-start-2 col-span-12">
                <div class="col-span-12">
                    <jet-button type="button" size="sm" class="mt-3" @click="addSegment()">
                        <span>Add Segment</span>
                    </jet-button>
                </div>
                <div class="col-span-12 relative" v-for="(condtn, index) in condition.conditions" :key="index">
                    <TestCriteria class="mt-1" 
                        :deep="deep + 1"
                        :condition="condtn"
                        :indexes="pushIndex(index)"/>
                </div>
            </div>
        </template>
        <!-- <span @click="deleteSegment()" class="text-gray-400 icon-x">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
        </span> -->
        <span @click="deleteSegment()" class="text-gray-700 top-0.5 right-0  absolute cursor-pointer">
            <!-- <svg xmlns="http://www.w3.org/2000/svg" class="h-auto w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg> -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
        </span>
    </div>
    <div class="ml-1 relative grid gap-1 border border-dashed border-gray-400 rounded-md mt-2 bg-gray-50 py-2 dark:bg-gray-800 dark:border-gray-500 items-center" :class="grid_cols">
        <div class="col-span-12 -mt-2 py-1 border-b dark:border-gray-500" v-if="indexes[indexes.length -1] != 0" >
            <div class="flex items-center px-2">
                <jet-label value="Logic Gate" class="mt-1 text-xs font-semibold" />
                <div class="w-2/3 ml-2">
                    <select v-model="condition.left_operator" class="form-select mt-1 block w-3/6 text-xs dark:bg-gray-900 rounded-lg p-1 border-gray-300 dark:border-gray-500">
                        <option value="and">AND (All of the following are true)</option>
                        <option value="or">OR (Any of the following are true)</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="px-2" :class="condition.method !== 'process' ? 'col-span-3' : 'col-span-2'">
            <jet-label value="Option" class="text-xs" />
            <div class="relative flex" v-if="condition.method!=='process'">
                <select v-model="condition.method" class="m-0 block w-1/6 text-xs dark:bg-gray-900 rounded-l-lg p-1 border-gray-300 border-r-0 min-w-0 flex-auto dark:border-gray-500">
                    <option value="segment">Segment</option>
                    <option value="process">Process</option>
                </select>
                <JetButton @click="addSegment" variant="primary" size="xs" class="rounded-l-none">
                    Add segment
                </JetButton>
            </div>
            <select v-else v-model="condition.method" class="form-select mt-1 block w-full text-xs dark:bg-gray-900 rounded-lg p-1 border-gray-300 dark:border-gray-500">
                <option value="segment">Segment</option>
                <option value="process">Process</option>
            </select>
        </div>
        
        <template v-if="condition.method == 'process'">
            <div class="col-span-2">
                <jet-label value="Variable" class="text-xs" />
                <JetInput type="text" v-model="condition.var" class="mt-1 block w-full p-1 text-xs"/>
            </div>
            <div class="col-span-2">
                <jet-label value="Operator" class="text-xs" />
                <select v-model="condition.operator" class="form-select mt-1 block w-full text-xs dark:bg-gray-900 rounded-lg p-1 border-gray-300 dark:border-gray-500">
                    <option value="==">equal to &equals;&equals;</option>
                    <option value="!=">not equal to !&equals;</option>
                    <option value=">">greater than &gt;</option>
                    <option value="<">is less than &lt;</option>
                    <option value=">=">greater than or equal to &gt;&equals;</option>
                    <option value="<=">less than or equal to &lt;&equals;</option>
                </select>
            </div>
            <div class="col-span-2">
                <jet-label value="Value" class="text-xs" />
                <JetInput type="text" v-model="condition.val" class="mt-1 block w-full p-1 text-xs"/>
            </div>
        </template>
        <template v-else>
            <div class="col-start-2 col-span-11">
                <!-- <div class="col-span-12">
                    <button type="button" variant="secondary" class="inline-flex items-center px-2 py-1 bg-blue-700 dark:bg-blue-700 border border-transparent rounded-md text-xs text-white dark:text-gray-50 dark:shadow-lg tracking-widest hover:bg-indigo-500 dark:hover:bg-blue-600 focus:bg-blue-700 dark:focus:bg-blue active:bg-blue-900 dark:active:bg-blue-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150" @click="addSegment">
                        <span>Add Segment</span>
                    </button>
                </div> -->
                <div class="col-span-12" v-for="(condtn, index) in condition.conditions" :key="index">
                    <CreateCriteria class="mt-1" :deep="deep + 1"
                        :condition="condtn"
                        :indexes="pushIndex(index)"  />
                </div>
            </div>
        </template>
        <span @click="deleteSegment()" class="text-gray-700 top-0.5 right-0  absolute cursor-pointer">
            <!-- <svg xmlns="http://www.w3.org/2000/svg" class="h-auto w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg> -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
        </span>

        <!-- do not delete -->
        <div class="hidden">
            <div class="grid-cols-12"></div>
            <div class="grid-cols-11"></div>
            <div class="grid-cols-10"></div>
            <div class="grid-cols-9"></div>
            <div class="grid-cols-8"></div>
            <div class="grid-cols-7"></div>
            <div class="grid-cols-6"></div>
        </div>
    </div>
</template>

<script setup>
import JetButton from '@/Components/Button.vue';
import TestCriteria from './TestCriteria.vue';
import CustomSelect from '@/Components/Select.vue';
import CustomInput from '@/Components/Input.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetInput from '@/Jetstream/Input.vue';

import Helpers from '@/Mixins/Helpers';
import  { computed, inject } from 'vue';

const props = defineProps([
    'deep',
    'condition',
    'indexes'
]);

const { assign } = Helpers();

const $testState = inject('testState');

const addSegment = () => {
    $testState.addSegment(props.indexes);
};

const deleteSegment = () => {
    $testState.deleteSegment(props.indexes);
};

const pushIndex = (index) => {
    var tmp_index = assign(props.indexes);
    tmp_index.push(index);
    return tmp_index;
};

const grid_cols = computed(() => {
    return 'grid-cols-' + (12 - props.deep)
});
</script>
<style scoped>
.select-sm {
    padding: .25rem;;
    font-size: .75rem;
}

.icon-x {
    font-size: 20px!important;
    position: absolute;
    margin-top: 25px;
    margin-left: -18px;
    cursor: pointer;
}
</style>
