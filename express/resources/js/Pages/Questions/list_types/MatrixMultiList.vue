<script setup>
import S4Checkbox from '@/S4/Checkbox.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4Input from '@/S4/Input.vue';
import S4Button from '@/S4/Button.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { nextTick, onBeforeUpdate, ref, watch, inject } from 'vue';

const props = defineProps(['items','rows','setup'])
const getTranslatedItem = inject('getTranslatedItem', (item) => item?.name || '');
const isRTL = inject('isRTL', false);

</script>


<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="grid grid-cols-12 gap-1">
        <div class="col-span-12">
            <table class="table-fixed mt-3 border">
                <thead>
                    <tr class="border">
                        <th class="col border"></th>
                        <th class="font-normal border" v-for="(item,index) in items" :key="index" :colspan="item.column.length">
                            {{item.title}}
                        </th>
                        
                    </tr>
                    <tr class="border">
                        <th></th>
                        <template class="colVal" v-for="(item,index) in items" :key="index">
                            <th v-for="(item2,index2) in item.column" :key="index2" class="border">
                                <span class="mr-3">{{item2.name}}</span>
                            </th>
                        </template>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(row,index) in rows" :key="index" class="border">
                        <td class="colRow px-2">
                            <span v-html="getTranslatedItem(row)"></span>
                        </td>
                        <template v-for="(item,index) in items" :key="index" class="colVal">
                            <template v-for="(item2,index2) in item.column" :key="index2">
                                <td v-if="item2.type == 1" class="px-2 border">
                                    <select required class="form-select text-slate-400 cursor-pointer text-sm rounded-lg border-slate-300 focus:border-slate-200 focus:ring-1 focus:ring-slate-400 placeholder-slate-300 w-full">
                                        <option value="" hidden selected>{{item2.placeholder}}</option>
                                        <option class="text-sm text-slate-400" v-for="(item3,index) in item2.items" :key="index">{{item3.name}}</option>
                                    </select>
                                </td>
                                <td v-if="item2.type == 2" class="justify-items-center divRad px-2 border">
                                    <jet-checkbox class="rounded-lg colInput float-left ml-3 text-sm text-slate-300" disabled />
                                    
                                </td>
                                <td v-if="item2.type == 3" class="px-2 border">
                                    <s4-input class="border-slate-300 text-sm focus:border-slate-200 focus:ring-1 focus:ring-slate-400 text-slate-400" style="width:20vmin!important;" :placeholder="item2.placeholder"></s4-input>
                                </td>
                            </template>
                             
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>


<style scoped>
/* table, tr, td, th {
    border:1px solid lightgrey;
    font-size:2vmin;
}
tr,td,th{
    text-align:center;
}
select:required:invalid {
  color: lightgray;
}
option[value=""][disabled] {
  display: none;
}
option {
  color: black;
}
.col{
    width:11vmin;
}
.colVal{
    width:20vmin;
    text-align: center;
}
.colInput {
    height:1.2vmin!important; 
    width:1.2vmin!important;
    border-color:#ACABAB!important;
    display:inline-block;
    margin-right:35px;
}
.divRad{
    margin-left:40px;
} */
</style>