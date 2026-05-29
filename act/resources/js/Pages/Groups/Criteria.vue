<template>
    <div class="grid gap-1" :class="grid_cols">
        <div class="col-span-2">
            <jet-label value="Option" />
            <select v-model="condition.method" class="form-select mt-1 block w-full">
                <option value="segment">Segment</option>
                <option value="process">Process</option>
            </select>
        </div>
        <div class="col-span-2" v-if="indexes[indexes.length -1] != 0" >
            <jet-label value="Left Operator"/>
            <select v-model="condition.left_operator" class="form-select mt-1 block w-full">
                <option value="and">AND</option>
                <option value="or">OR</option>
            </select>
        </div>
        <template v-if="condition.method == 'process'">
            <div class="col-span-2">
                <jet-label value="Variable" />
                <jet-input type="text" v-model="condition.var" class="mt-1 block w-full"/>
            </div>
            <div class="col-span-2">
                <jet-label value="Operator" />
                <select v-model="condition.operator" class="form-select mt-1 block w-full">
                    <option value="==">&equals;&equals;</option>
                    <option value="!=">!&equals;</option>
                    <option value=">">&gt;</option>
                    <option value="<">&lt;</option>
                    <option value=">=">&gt;&equals;</option>
                    <option value="<=">&lt;&equals;</option>
                </select>
            </div>
            <div class="col-span-2">
                <jet-label value="Value" />
                <jet-input type="text" v-model="condition.val" class="mt-1 block w-full"/>
            </div>
        </template>
        <template v-else>
            <div class="col-start-2 col-span-11">
                <div class="col-span-12">
                    <jet-button type="button" variant="secondary" class="border-gray-600" @click="addSegment()">
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
        <i class="fa fa-times-circle text-gray-400 icon-x" @click="deleteSegment()"></i>
    </div>
</template>
<script setup>
import JetButton from '@/Jetstream/Button.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetLabel from '@/Jetstream/Label.vue';

import { computed, inject } from 'vue';
import Helpers from "@/Mixins/Helpers.js";

const props = defineProps(['deep', 'condition', 'indexes']);

const { assign } = Helpers();

const $state = inject('state');

const grid_cols = computed(() => {
    return 'grid-cols-' + (props.deep);
});

const addSegment = () => {
    $state.addSegment(props.indexes);
};

const deleteSegment = () => {
    return $state.deleteSegment(props.indexes);
};

const pushIndex = (index) => {
    var tmp_index = assign(props.indexes);
    tmp_index.push(index);
    return tmp_index;
};
</script>
