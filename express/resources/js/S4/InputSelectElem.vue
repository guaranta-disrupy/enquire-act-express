<template>
    <div class="w-full">
            <Multiselect 
            class="w-full"
            :options="props.options" 
            placeholder=""
            :close-on-select="true"
            :allowEmpty="false"
            :searchable="false"
            :createTag="true" 
            :append-to-body="true"
            track-by="text"
            label="text"
            :multiple="false"
            @update:modelValue="_fn.update"
            v-model="input"
            @mousedown.stop
            > 
            
                <template #option="{ option }">
                    <div class="InputSelectElemOption truncate">
                        <div v-html="option.text"></div>
                    </div>
                </template>    
            
    
            <template #singleLabel="{option}">
                <div class="InputSelectElemSelect truncate">
                    <div v-html="option.text"></div>
                </div>
            </template>
            </Multiselect>
            
    </div>
    </template>
    <style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
    <script setup>
    import Multiselect from 'vue-multiselect'
    import { computed, ref, watch, onMounted } from 'vue'
    const props = defineProps({
        modelValue: {
            default: ''
        },
        options: {
            default: []
        }
    })
    const input = ref({})

    // watch(props.modelValue,()=>{
    //     alert(props.modelValue)
    //     _fn.initData()
    // })
    watch(()=>props.options,()=>{
        _fn.initData()
    },{deep:true})

    onMounted(()=>{
        _fn.initData()
    })
    const emits = defineEmits(["update:modelValue"])

    const _fn = {
        initData : ()=>{
            let target = props.options.find((obj)=>{
                obj.value == props.modelValue
            })
            if(!target){
                //alert(JSON.stringify(props.options))
                target = props.options[0]
                console.log(props.options)
            }
            input.value = target

            
        },
        update : (val)=>{
            //alert(JSON.stringify(val))
            emits("update:modelValue",val.value)
        }
    }
    
    
    
    </script>
<style scope>
.InputSelectElemSelect p, .InputSelectElemOption p{
    display:inline-block;
}
.multiselect__content-wrapper {
    position: absolute !important;
    z-index: 9999;
}

</style>