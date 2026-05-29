<template>
    <div class="grid gap-1 mt-2" :class="grid_cols">
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
                <div class="col-span-12" v-for="(condtn, index) in condition.conditions" :key="index">
                    <criteria class="mt-1" 
                        :deep="deep + 1"
                        :condition="condtn"
                        :indexes="pushIndex(index)"/>
                </div>
            </div>
        </template>
        <span @click="deleteSegment()" class="text-gray-400 icon-x">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
        </span>
    </div>
</template>

<script setup>
import JetButton from '@/Components/Button.vue';
import Criteria from './Criteria.vue';
import CustomSelect from '@/Components/Select.vue';
import CustomInput from '@/Components/Input.vue';

import Helpers from '../../Mixins/Helpers';
import  { computed, inject } from 'vue';

const props = defineProps([
    'deep',
    'condition',
    'indexes'
]);

const { assign } = Helpers();

const $state = inject('state');

const addSegment = () => {
    $state.addSegment(props.indexes);
};

const deleteSegment = () => {
    $state.deleteSegment(props.indexes);
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
