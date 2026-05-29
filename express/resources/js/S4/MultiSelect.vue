<script setup>
import { computed, ref } from "vue"

const props = defineProps(["options","selected"])
const emits = defineEmits(['update:selected'])
const show = ref(false)

const open = () =>  {show.value = true }
const close = () =>  { show.value = false }
const isOpen = () => { return show.value }
const select = (index) => {  
    if (!props.options[index].selected) { 
        props.options[index].selected = true;
        props.selected.push(index);

    } else { 
        props.selected.splice(props.selected.indexOf(index),1);
        props.options[index].selected = false
    }  
}
const remove = (index) => { 
    props.options[index].selected = false;
    props.selected.splice(props.selected.indexOf(index),1);   


}

</script>


<template> 
    <div class="w-full">
    <input name="values" type="hidden" v-bind:value="selected" @input="$emit('update:value', $event.target.value)"/>
      <div class="relative">
        <div class="flex flex-col items-center relative">
          <div class="w-full">
            <div class="my-1 p-1 flex border border-gray-300 focus:border-ssr-blue3 focus:ring-1 focus:ring-ssr-blue3 bg-white rounded-lg ">
              <div class="flex flex-auto flex-wrap">
                <div v-for="(option,index) in options"  :key="index" >
                  <div class="flex justify-center items-center m-1 font-medium py-1 px-1 bg-white rounded bg-yellow-500 border " v-if="option.selected">
                    <div class="text-xs font-normal leading-none max-w-full flex-initial text-white">{{option.name}} </div>
                    <div class="flex flex-auto flex-row-reverse">
                      <div @click="remove(index)">
                        <svg class="fill-current h-4 w-4 text-white" role="button" viewBox="0 0 20 20" >
                          <path d="M14.348,14.849c-0.469,0.469-1.229,0.469-1.697,0L10,11.819l-2.651,3.029c-0.469,0.469-1.229,0.469-1.697,0
                                           c-0.469-0.469-0.469-1.229,0-1.697l2.758-3.15L5.651,6.849c-0.469-0.469-0.469-1.228,0-1.697s1.228-0.469,1.697,0L10,8.183
                                           l2.651-3.031c0.469-0.469,1.228-0.469,1.697,0s0.469,1.229,0,1.697l-2.758,3.152l2.758,3.15
                                           C14.817,13.62,14.817,14.38,14.348,14.849z"
                          />
                        </svg>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-if="selected.length === 0" class="flex-1">
                  <input placeholder="Select a option" class=" bg-transparent  p-1 px-2 appearance-none outline-none h-full w-full text-gray-800 "/>
                </div>
              </div>
              <div class="text-gray-300 w-8 py-1 pl-2 pr-1 border-l flex items-center border-gray-200 svelte-1l8159u">
                <button type="button" v-show="isOpen() === true" @click="close"  class="cursor-pointer w-6 h-6 text-gray-600 outline-none focus:outline-none" >
                  <svg version="1.1" class="fill-current h-4 w-4" viewBox="0 0 20 20" >
                    <path d="M17.418,6.109c0.272-0.268,0.709-0.268,0.979,0s0.271,0.701,0,0.969l-7.908,7.83 c-0.27,0.268-0.707,0.268-0.979,0l-7.908-7.83c-0.27-0.268-0.27-0.701,0-0.969c0.271-0.268,0.709-0.268,0.979,0L10,13.25
	                L17.418,6.109z"/>
                  </svg>
                </button>
                <button type="button" v-show="isOpen() === false" @click="open" class=" cursor-pointer w-6 h-6 text-gray-600 outline-none focus:outline-none">
                  <svg class="fill-current h-4 w-4" viewBox="0 0 20 20">
                    <path  d="M2.582,13.891c-0.272,0.268-0.709,0.268-0.979,0s-0.271-0.701,0-0.969l7.908-7.83 c0.27-0.268,0.707-0.268,0.979,0l7.908,7.83c0.27,0.268,0.27,0.701,0,0.969c-0.271,0.268-0.709,0.268-0.978,0L10,6.75L2.582,13.891z"
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>
          <div class="w-full px-4" v-show="show">
            <div class=" absolute shadow top-100 bg-white z-50 w-full left-0 rounded max-h-select">
              <div class="flex flex-col w-full overflow-y-auto h-64">
                <div  v-for="(option,key) in options" :key="key" class="overflow-auto" >
                  <div class="cursor-pointer w-full  border-gray-100 rounded-t border-b hover:bg-gray-100" @click="select(key)">
                    <div class="flex w-full items-center p-2 pl-2 border-transparent border-l-2 relative">
                      <div class="w-full items-center flex justify-between">
                        <div class="mx-2 leading-6" :model="option">{{option.name}}</div>
                        <div v-show="option.selected">
                          <svg class="svg-icon" viewBox="0 0 20 20">
                            <path fill="none" d="M7.197,16.963H7.195c-0.204,0-0.399-0.083-0.544-0.227l-6.039-6.082c-0.3-0.302-0.297-0.788,0.003-1.087
                              C0.919,9.266,1.404,9.269,1.702,9.57l5.495,5.536L18.221,4.083c0.301-0.301,0.787-0.301,1.087,0c0.301,0.3,0.301,0.787,0,1.087
                              L7.741,16.738C7.596,16.882,7.401,16.963,7.197,16.963z"
                            ></path>
                          </svg>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div> 
</template>



<style>

.svg-icon {
  width: 1em;
  height: 1em;
}

.svg-icon path,
.svg-icon polygon,
.svg-icon rect {
  fill: #333;
}

.svg-icon circle {
  stroke: #4691f6;
  stroke-width: 1;
}

</style>