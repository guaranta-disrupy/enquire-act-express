<template>
    <Teleport to="body">
        <Transition name="DraggableModalAnimation">
        <div ref="$modal" class="SSR_DraggableModal fixed z-[9999] flex flex-col justify-center bg-base-100 shadow-2xl border border-base-300 rounded-xl translate-x-[0%] translate-y-[0%] min-w-[310px]" v-if="$props.show" :style="{'top':$setup.pos.y+'px','left':$setup.pos.x+'px'}">
                <div 
  class="w-full px-4 py-2 pr-10 font-bold text-center cursor-move DM_title text-base-content border-b-1 border-base-300" 
  @mousedown="onMouseDown"
>
<slot name="title"></slot>
</div>
<div class="DM_controls">
    <button class="absolute text-base-content/50 hover:text-error top-2 right-2" @click="$emits('close:modal')">
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
</svg>
    </button>

</div>
                <div class="w-full px-4 pt-2 DM_content">
                    <slot name="content"></slot>
                </div>
                <div class="px-4 py-2 DM_footer">
                    <slot name="footer"></slot>
                </div>
        </div>
        </Transition>
    </Teleport>
</template>


<script setup>
import { ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'

const $modal = ref(null)

const $props = defineProps({
    show : false,
    initX : {default:0},
    initY : {default:0},
})

const $setup = ref({
  show: false,
  pos: { x: $props.initX, y: $props.initY }
})


const $emits = defineEmits(['close:modal'])

const isDragging = ref(false)
const dragOffset = ref({ x: 0, y: 0 })
const modalDimensions = ref({ width: 0, height: 0 })

const onMouseDown = (e) => {
  isDragging.value = true
  // Use offsetLeft/offsetTop instead of getBoundingClientRect for better performance
  const pos = {
    left: $modal.value.offsetLeft,
    top: $modal.value.offsetTop
  }
  dragOffset.value = {
    x: e.clientX - pos.left,
    y: e.clientY - pos.top
  }
  document.addEventListener('mousemove', onMouseMove)
  document.addEventListener('mouseup', onMouseUp)
}

const onMouseMove = (e) => {
  if (!isDragging.value) return
  
  // Use cached dimensions instead of measuring again
  const { width, height } = modalDimensions.value
  const newX = e.clientX - dragOffset.value.x
  const newY = e.clientY - dragOffset.value.y

  // Keep modal within viewport
  const maxX = window.innerWidth - width
  const maxY = window.innerHeight - height

  $setup.value.pos.x = Math.min(Math.max(0, newX), maxX)
  $setup.value.pos.y = Math.min(Math.max(0, newY), maxY)
}


const onMouseUp = () => {
  isDragging.value = false
  document.removeEventListener('mousemove', onMouseMove)
  document.removeEventListener('mouseup', onMouseUp)
}

const cacheModalDimensions = () => {
  if ($modal.value) {
    modalDimensions.value = {
      width: $modal.value.offsetWidth,
      height: $modal.value.offsetHeight
    }
  }
}

const $Fn = {
  initPosition: async () => {
    if($props.initX == 0 && $props.initY == 0) {
        let x = window.innerWidth / 2
        let y = window.innerHeight / 2
        await nextTick()
        
        // Use cached dimensions instead of getBoundingClientRect
        cacheModalDimensions()
        const { width, height } = modalDimensions.value
        
        $setup.value.pos.x = x - width / 2
        $setup.value.pos.y = y - height / 2
    }else{
        $setup.value.pos.x = $props.initX
        $setup.value.pos.y = $props.initY      
    }
  }
}

watch(() => $props.show, () => {
  $Fn.initPosition()
}, { immediate: true })

onMounted(() => {
  setTimeout(() => {
    $setup.value.show = true
  }, 300)
  
  // Cache dimensions and reposition on resize
  cacheModalDimensions()
  window.addEventListener('resize', () => {
    cacheModalDimensions()
    $Fn.initPosition()
  })
})

onBeforeUnmount(() => {
  document.removeEventListener('mousemove', onMouseMove)
  document.removeEventListener('mouseup', onMouseUp)
  window.removeEventListener('resize', $Fn.initPosition)
})

</script>


<style lang="css" scoped>
.DraggableModalAnimation-enter-active {
  animation: DraggableModalAnimation-in 0.3s;
}
.DraggableModalAnimation-leave-active {
  animation: DraggableModalAnimation-in 0.3s reverse;
}
@keyframes DraggableModalAnimation-in {
  0% {
    transform: translateY(20px);
    opacity:0;
  }
  100% {
    transform: translateY(0);
    opacity:1;
  }
}

.DM_title {
  user-select: none;
}

</style>


