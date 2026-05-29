<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import S4Loading from '@/S4/Loading.vue'
import { ref, onBeforeMount, onMounted } from 'vue'
import { useForm, usePage } from "@inertiajs/vue3"

const copyData = useForm({
    copyCode:null,
    fromCode:null,
})

const initialized = () =>{
     copyData.copyCode = usePage().props.project.code
}

const copyQnr = () => {
    var params = [
        usePage().props.project.code,
    ]
}


onBeforeMount(()=>{
    console.log(usePage().props.projects)
   initialized() 
})
onMounted(()=>{
    console.log(usePage().props.projects)
})

</script>
<template>
    <jet-dialog-modal @close="close()">
        
        <template #title>
            <s4-loading v-show="loading.value" :label="messageLoading.value"></s4-loading>
            <h4>Copy Survey</h4>
        </template>

        <template #content>
            <div class="grid grid-cols-6 gap-1"> 
                <div class="col-span-12 mx-auto">
                    <select v-model="copyCode" class="w-full border-1 border-gray-300 focus:border-ssr-blue3  focus:ring-1 focus:ring-ssr-blue3">
                        <option v-for="(proj,index) in usePage().props.projects" :key="index" :value="proj.code">{{proj.name}}</option>
                    </select>
                </div>
            </div>
        </template>

    </jet-dialog-modal>
</template>


