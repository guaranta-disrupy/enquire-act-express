<template>
<div ref="root" class="relative">
  <div class="fullscreen-wrapper relative">
    <slot name="content"/>
    <div class="absolute bottom-2 right-2 hover:scale-125 transition duration-250 ease-out hover:ease-in">
        <div class="text-white cursor-pointer" @click="toggle" >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
</svg></div>
  </div>
  </div>
  
  </div>
</template>
<script lang="ts">
  import {
    ref,
    defineComponent,
    toRefs,
    reactive,
  } from 'vue'
  import { api as fullscreen } from 'vue-fullscreen'
  export default defineComponent({
    setup() {
      const root = ref()
      const state = reactive({
        fullscreen: false,
        teleport: true,
      })

      async function toggle () {
        await fullscreen.toggle(root.value.querySelector('.fullscreen-wrapper'), {
          teleport: state.teleport,
          callback: (isFullscreen) => {
            // state.fullscreen = isFullscreen
          },
        })
        state.fullscreen = fullscreen.isFullscreen
      }

      return {
        root,
        ...toRefs(state),
        toggle,
      }
    },
  })
</script>