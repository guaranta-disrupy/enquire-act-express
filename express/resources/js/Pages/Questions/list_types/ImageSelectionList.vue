<script setup>
import { onBeforeUpdate, ref, watch, nextTick, inject, computed } from 'vue';

const props = defineProps(['choices','setup'])
const getTranslatedChoice = inject('getTranslatedChoice', (choice) => choice?.name || '');
const isRTL = inject('isRTL', false);

// Filter out null/undefined choices to prevent rendering errors
const validChoices = computed(() => {
    if (!props.choices || !Array.isArray(props.choices)) return [];
    return props.choices.filter(choice => choice != null && typeof choice === 'object');
});

const new_choice = ref('')
const itemRefs = ref([])

const setItemRef = (el)=>{
    if (el) {
        itemRefs.value.push(el)
    }
}
const gridCols = ()=>{
    //return 'grid-cols-'+12
    console.log
    return (props.setup?.imageSelection?.grid) ? 'grid-cols-'+ props.setup.imageSelection.grid : '5'
}

onBeforeUpdate(()=>{
 itemRefs.value = []
})



// watch(()=>{
//     new_choice,
//     (new_choice) => {
//         if (new_choice.value != '') {
//             $questionvar.add_edit.choices.push({name: new_choice})
//             $nextTick(() => {
//                 itemRefs.value[$questionvar.add_edit.choices.length - 1].focus();
//                 new_choice.value = ''
//             })
//         }
//     }
// })

watch(new_choice, (value) => {
    if (value !== '') {
        $questionvar.add_edit.choices.push({ name: value });
        nextTick(() => {
            itemRefs.value[$questionvar.add_edit.choices.length - 1].focus();
            new_choice.value = '';
        });
    }
});


</script>
<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="grid gap-1" :class="gridCols()">

        <!--<template v-for="(choice, index) in choices" :key="index">
            <div class="overflow-auto">
                <div class="p-2 container flex justify-center">               
                    <div class="imgCont border-2 w-32 h-32">
                        <img :src="choice.src">
                    </div>                    
                </div>
                <p class="text-center font-bold" v-html="choices[index].name"></p>
            </div>
        </template>-->

        <div class="col-span-12">
            <table class="table-fixed mt-5">
                <tbody class="grid gap-1" :class="gridCols()">
                    <tr v-for="(choice, index) in validChoices" :key="choice?.CUUID || index" class="mb-4 mr-3">
                        <td>
                            <div class="flex rounded-xl border border-slate-200 w-20 h-20 bg-contain bg-no-repeat bg-center " :style="{backgroundImage: choice?.src ? `url(${choice.src})` : 'none'} ">
                                <!-- <img class="bg-contain bg-no-repeat bg-center " :src="choice.src">                                 -->
                            </div>
                            <p v-if="!choice?.hide_name" class="MChoice w-20" v-html="getTranslatedChoice(choice)"></p>
                        </td>
                    </tr>
                </tbody>
            </table> 
        </div>
        


        <!-- <template v-for="(choice, index) in choices" :key="index">            
            <label class="py-1 pr-2">
                <div class="">
                    <div class="border cont1" style="height:4rem; width:5rem;">
                        <img class="cont2" :src="choice.src">
                    </div>
                    <div class="w-28">
                        <p class="text-center" v-html="choices[index].name"></p>
                    </div> 
                </div>               
            </label>
        </template> -->
    </div>
</template>



<style scoped>


/*.imgCont {
    margin:auto auto;
}
.imgCont img {
     max-height:10vmin;
     position:relative;
}*/

.imgCont {
    position: relative;
}
.imgCont img {
    /* position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    height: 100%;
    border-radius: 15px; */
    /* background-size: cover; */
}

.MChoice {
    text-align: center;
    font-size: smaller;
}

</style>