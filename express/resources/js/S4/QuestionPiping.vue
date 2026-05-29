<template>
    <transition name="pipemodalanimate">
    <div v-if="props.pipe_modal.show" class="fixed z-[90] top-[90px] left-[160px] bg-white shadow-sm w-[320px] h-[calc(100vh-110px)] rounded-lg flex flex-col overflow-hidden text-slate-700 text-sm">
        <div class="grid grid-cols-1 gap-1 px-6 pt-6 pb-2 w-full bg-[var(--mcolor)]">
            <div class="font-bold text-xl text-white">Piping</div>
            <!-- <s4-input-select class="w-full" color="ssrGray" :lists="options.list"
            v-model="options.type"></s4-input-select> -->
            <SingleSelect 
            :options="options.list"
            :modelValue="options.type" @update:modelValue="(res)=>{
                options.type = res
            }">

            </SingleSelect>
        </div>
        <div class=" grow-[1] item-list h-full overflow-y-auto" v-if="options.type == 1">
            <button @click="_fn.add(q)" 
                :class="selectedQuestion === q.order ? 'bg-orange-100 border-l-4 border-l-[var(--mcolor)]' : 'hover:bg-slate-100'"
                class="w-full text-left grid grid-cols-[auto_1fr] gap-1 px-6 py-3 hover:bg-[var(--mcolor)] hover:text-black active:bg-[var(--mcolor)] truncate cursor-pointer text-md border-t-[.9px] border-gray-200" 
                v-for="q in props.questions" :key="q.order">
                <div class="font-bold">Q{{ (q.order + parseInt(parseInt(usePage().props.questionnaire.setup.variable_start)) - 1) }} : </div>
                <div class="truncate" v-if="q.setup.chartTitle">{{ q.setup.chartTitle }}</div>
                <div class="truncate" v-else v-html="q.question"></div>
            </button>
        </div>
        <div class=" item-list px-6 py-4 overflow-y-auto rounded-lg " v-if="options.type == 2">
             <div class="text-sm text-slate-700 pb-2">Variable Name</div>
             <s4-input color="ssrGray" class="w-full mb-1 italic-placeholder" v-model="options.variable"
                    placeholder="e.g a1">
            </s4-input>      
            <button @click="_fn.add2" class="mt-2 relative inline-flex items-center px-4 py-2 bg-ssr-blue2 border border-transparent rounded-md font-bold text-xs text-white tracking-widest hover:bg-ssr-blue1 focus:outline-none !bg-[var(--txtcolor)] hover:!bg-black float-left">Add</button>  
        </div>
    </div>
    </transition>
</template>
<script setup>
    import {ref,inject,watch} from "vue"
    //import S4InputSelect from "@/S4/InputSelect.vue"
    import {  usePage } from "@inertiajs/vue3";
    import SingleSelect from "./SingleSelect.vue";
    import S4Input from "@/S4/Input.vue";
    const props = defineProps({
        "questions" : {required:true},
        "pipe_modal" : {required:true}
    })

    watch(()=>props.pipe_modal,(m)=>{
        if(m.show){
            document.querySelectorAll(".donModal").forEach((modal)=>{
                modal.dispatchEvent(new CustomEvent("sidebar",{detail:{open:true,target:'piping'}}))
            })
        }else{
            document.querySelectorAll(".donModal").forEach((modal)=>{
                modal.dispatchEvent(new CustomEvent("sidebar",{detail:{open:false,target:'piping'}}))
            })           
        }
    },{deep:true})

    // Watch for changes in the editor content to clear selection when pipe is deleted
    let textChangeHandler = null;
    
    watch(()=>props.pipe_modal.target, (editor) => {
        if (editor && editor.getQuill) {
            const quill = editor.getQuill();
            
            // Remove previous handler if exists
            if (textChangeHandler) {
                quill.off('text-change', textChangeHandler);
            }
            
            // Create new handler
            textChangeHandler = () => {
                if (selectedQuestion.value) {
                    const orderID = parseInt(selectedQuestion.value) + parseInt(usePage().props.questionnaire.setup.variable_start) - 1;
                    const pipeText = `PIPE:Q${orderID}`;
                    
                    // Get the current content
                    const htmlContent = quill.root.innerHTML;
                    
                    // Only clear if the pipe is not found in the HTML content
                    if (!htmlContent.includes(pipeText)) {
                        selectedQuestion.value = null;
                    }
                }
            };
            
            // Add the handler
            quill.on('text-change', textChangeHandler);
        }
    }, { deep: true });


    // console.log("Questions",props.questions)
    const options = ref({
        type : 1,
        list : [
            {
            text : "Question",
            value : 1
        },
        {
            text : "URL Variable",
            value : 2
        },
        ],
        variable : null
    })
    
    // Track the currently selected question
    const selectedQuestion = ref(null);
    let form = inject("add_edit_form");

    const _fn = {
        add : (obj)=>{
            // Set the selected question
            selectedQuestion.value = obj.order;
            
            //const text = "[PIPE:Q"+obj.order+"]"
            const orderID = parseInt(obj.order) + parseInt(usePage().props.questionnaire.setup.variable_start) - 1
            const text = "PIPE:Q"+orderID
            //alert(text)
            //document.execCommand("insertText", text)
            const editor = props.pipe_modal.target.getQuill()
            setTimeout(() => {
                const range = editor.getSelection() || { index: 0 };
                //console.log(range)
                // if(range.index == 0){
                //     editor.insertText(editor.getLength() - 1, text)
                // }else{
                //     editor.insertText(range.index, text)
                // }
                let o = {
                    tag:text,
                    label: "Question "+orderID
                }
               editor.insertEmbed(range.index, 'pipe', o)
                let ln = text.length + 1
               setTimeout(()=>{
                editor.setSelection(range.index + ln, props.user);
               },300)
               
            },100)           
        },
        add2 : ()=>{
            if(!options.value.variable){
                return
            }
            //const text = "[QUERY:"+options.value.variable.trim()+"]"
            const text = "QUERY:"+options.value.variable.trim()
            const editor = props.pipe_modal.target.getQuill()
            setTimeout(() => {
                const range = editor.getSelection() || { index: editor.getLength() - 1 };
                let o = {
                    tag:text,
                    label: "Variable : "+options.value.variable.trim()
                }

                // Insert the variable pipe at the current cursor position (or end if no selection)
                editor.insertEmbed(range.index, 'pipe', o)

                // Move cursor after the inserted pipe tag for continued typing
                setTimeout(()=>{
                    editor.setSelection(range.index + 1, props.user);
                },300)

                options.value.variable = null
            },100)      

        }
    }
</script>
<style>
.pipemodalanimate-enter-active {
  animation: pipemodalanimate-in 0.5s;
}
.pipemodalanimate-leave-active {
  animation: pipemodalanimate-in 0.5s reverse;
}
@keyframes pipemodalanimate-in {
  0% {
    opacity:0;
    transform: translateX(-100%);
  }
  100% {
    opacity:1;
    transform: translateX(0px);
  }
}
</style>