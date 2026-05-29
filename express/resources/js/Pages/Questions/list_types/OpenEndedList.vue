<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4Input from '@/S4/Input.vue'; 
import { ref, inject } from 'vue';
const props = defineProps(['setup'])
const isRTL = inject('isRTL', false);
const totalcharacter = ref(0)
const message = ref('')
const charCount = ()=>{
    totalcharacter.value = message.value.length; 
}
</script>
<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="md:flex md:items-center"> 
        <div>
         <s4-text-area class="w-full rounded-lg text-sm border-slate-300 text-sm focus:border-slate-200 focus:ring-1 focus:ring-slate-400 text-slate-400" style="width:23rem!important; height:2.5rem!important;" 
         placeholder="Open ended" @keyup='charCount()' v-model="message" :maxlength="setup.validate.max"></s4-text-area>
        <div class="flex justify-end">
            <p class="text-xs">{{totalcharacter}}/{{setup.validate.max}}</p>
        </div> 
        </div>
    </div>
</template>


