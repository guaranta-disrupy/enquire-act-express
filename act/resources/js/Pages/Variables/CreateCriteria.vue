<script setup>
import JetButton from '@/Jetstream/Button.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetLabel from '@/Jetstream/Label.vue';
import CreateCriteria from './CreateCriteria.vue';
import { computed } from 'vue';
import Helpers from '../../Mixins/Helpers';





const props = defineProps(['deep', 'condition', 'indexes']);

const { assign, $varState } = Helpers();

const addSegment = () => {
    $group.addSegment(props.indexes);
};

const deleteSegment = () => {
    $group.deleteSegment(props.indexes);
};

const pushIndex = (index) => {
    let tmp_index = assign(props.indexes);
    tmp_index.push(index);
    return tmp_index;
};

const grid_cols = computed(() => {
    return 'grid-cols-' + (12 - props.deep)
});
</script>
<template>
    <div class="grid gap-1" :class="grid_cols">
        <div class="col-span-2">
            <jet-label value="Option" class="text-xs" />
            <select v-model="condition.method" class="form-select mt-1 block w-full select-sm text-xs">
                <option value="segment">Segment</option>
                <option value="process">Process</option>
            </select>
        </div>
        <div class="col-span-2" v-if="indexes[indexes.length -1] != 0" >
            <jet-label value="Left Operator" class="text-xs" />
            <select v-model="condition.left_operator" class="form-select mt-1 block w-full select-sm">
                <option value="and">And</option>
                <option value="or">Or</option>
            </select>
        </div>
        <template v-if="condition.method == 'process'">
            <div class="col-span-2">
                <jet-label value="Variable" class="text-xs" />
                <jet-input type="text" v-model="condition.var" class="mt-1 block w-full p-1 text-xs"/>
            </div>
            <div class="col-span-2">
                <jet-label value="Operator" class="text-xs" />
                <select v-model="condition.operator" class="form-select mt-1 block w-full select-sm">
                    <option value="==">&equals;&equals;</option>
                    <option value="<>">!&equals;</option>
                    <option value=">">&gt;</option>
                    <option value="<">&lt;</option>
                    <option value=">=">&gt;&equals;</option>
                    <option value="<=">&lt;&equals;</option>
                </select>
            </div>
            <div class="col-span-2">
                <jet-label value="Value" class="text-xs" />
                <jet-input type="text" v-model="condition.val" class="mt-1 block w-full p-1 text-xs"/>
            </div>
        </template>
        <template v-else>
            <div class="col-start-2 col-span-11">
                <div class="col-span-12">
                    <jet-button type="button" variant="secondary" class="border-gray-600 py-1 text-xs bg-gray-300 text-gray-900 hover:bg-gray-200 px-1" @click="addSegment">
                        <span>Add Segment</span>
                    </jet-button>
                </div>
                <div class="col-span-12" v-for="(condtn, index) in condition.conditions" :key="index">
                    <CreateCriteria class="mt-1" :deep="deep + 1"
                        :condition="condtn"
                        :indexes="pushIndex(index)"/>
                </div>
            </div>
        </template>
        <i class="fa fa-times-circle text-gray-400 icon-x" @click="deleteSegment()"></i>

        <!-- do not delete -->
        <div class="d-none">
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
<style scoped>
.select-sm {
    padding: .25rem;;
    font-size: .75rem;
}

.icon-x {
    font-size: 20px!important;
    position: absolute;
    margin-top: 25px;
    margin-left: -15px;
    cursor: pointer;
}
</style>
