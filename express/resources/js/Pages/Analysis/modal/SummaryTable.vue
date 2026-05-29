
<script setup>
 const props = defineProps({
    'question': Object,
    'ids' : Array,
    'answers' : Array,
    'rows' : Array,
    'item_key' : 0,

})
</script>

<template>
     <table class="table-fixed min-w-full leading-normal sm:rounded-lg" >
        <thead>
            <th></th>
            <th class="bg-blue-100 border-solid-200 py-2 px-2">Participant ID</th>
            <th class="bg-blue-100 border-solid py-2 px-2" v-if="rows.length == 0">Responses</th>
            <th class="bg-blue-100 border-solid py-2 px-2"  v-for="(row, key) in rows" :key="key" v-else>{{ row.name }}</th>
        </thead>
        <tbody class="overflow-y-scroll h-5/6">
            <tr v-for="(id,key) in ids" :key="key">
                <td></td>
                <td class="border px-2 py-2" style="text-align:center;"> {{ id }} </td>
                <template v-if=" question.question_type_id == 12">
                    <td class="border px-2 py-2" style="text-align:center;"  v-for="(row, row_key) in rows" :key="row_key"> {{answers[row_key][item_key]['answers'][key] }} </td>
                </template>
                <template v-else>
                    <td class="border px-2 py-2" style="text-align:center;" > {{answers[key] }} </td>
                </template>

                
            </tr>
        </tbody>
    </table>
</template>



