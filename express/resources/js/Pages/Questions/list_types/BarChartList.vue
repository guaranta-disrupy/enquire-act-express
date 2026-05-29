<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4Input from '@/S4/Input.vue';
import { onBeforeMount, ref, inject } from 'vue';


const props = defineProps(['items','setup'])
const getTranslatedItem = inject('getTranslatedItem', (item) => item?.name || '');
const isRTL = inject('isRTL', false);

const get_value =  ref([])
const total_val = ref(0)

onBeforeMount(()=>{
    initialize();
})

const initialize = ()=>{
    var x =0;
    for(x in props.items){
        get_value.value.push(0);
        x++;
    } 
//    console.log(this.setup)
}
const total = ()=>{
    var x = 0;
    total_val.value = 0;
    for(x in get_value.value){
        total_val.value = parseFloat(get_value.value[x]) + total_val.value;
    } 
}

</script>
<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="grid grid-cols-12 gap-1">
        <div class="col-span-12">
            <table class="table-fixed mt-3 w-full">
                <tbody>
                    <tr v-for="(item, index) in items" :key="index" class="mb-4">
                        <!-- info / label + color -->
                        <td class="align-top py-1">
                            <div class="flex items-center gap-2 pr-2">
                                <div
                                    class="w-3 h-3 rounded-full border border-slate-300"
                                    :style="{ backgroundColor: item.color || '#e5e7eb' }"
                                ></div>
                                <div class="text-sm" v-html="getTranslatedItem(item)"></div>
                            </div>
                        </td>

                        <!-- slider column (only when slider is enabled for this question) -->
                        <td v-if="setup?.enableSlider == 1" class="align-top py-1 w-1/3">
                            <div class="px-2">
                                <input
                                    type="range"
                                    class="w-full cs-range"
                                    :min="setup?.range?.min ?? 0"
                                    :max="setup?.range?.max ?? 100"
                                    :step="1"
                                    v-model.number="get_value[index]"
                                    @input="total()"
                                />
                            </div>
                        </td>

                        <!-- numeric input with prefix/suffix -->
                        <td class="align-top py-1">
                            <div class="flex items-center text-sm">
                                <span class="flex items-center rounded rounded-r-none border-0 px-3 text-grey-100">
                                    {{ setup.prefix }}
                                </span>

                                <s4-input
                                    type="number"
                                    v-model.number="get_value[index]"
                                    @input="total()"
                                    class="w-1/2 border-slate-300 text-sm focus:border-slate-200 focus:ring-1 focus:ring-slate-400 text-slate-400"
                                    style="height: 1.8rem;"
                                ></s4-input>

                                <span class="flex items-center rounded rounded-r-none border-0 px-3 text-grey-100">
                                    {{ setup.suffix }}
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- total row (matches engine behaviour when enabled) -->
                    <tr v-if="setup?.totalLabel?.enable == 1">
                        <td :colspan="setup?.enableSlider == 1 ? 3 : 2" class="pt-2">
                            <div class="text-sm font-medium text-slate-700 flex items-center gap-1">
                                <span v-html="setup.totalLabel.text"></span>
                                <span>:</span>
                                <span>{{ setup.prefix }} {{ total_val }}{{ setup.suffix }}</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>         
        </div>
    </div>
</template>
<style scoped>
.cs-range {
    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    height: 4px;
    border-radius: 999px;
    background: #d4d4d4; /* light gray track */
    outline: none;
}

.cs-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 14px;
    height: 14px;
    border-radius: 999px;
    background: #f97316; /* orange knob */
    cursor: pointer;
}

.cs-range::-moz-range-track {
    height: 4px;
    border-radius: 999px;
    background: #d4d4d4;
}

.cs-range::-moz-range-thumb {
    width: 14px;
    height: 14px;
    border-radius: 999px;
    background: #f97316;
    border: none;
    cursor: pointer;
}
</style>