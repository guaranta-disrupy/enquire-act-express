<template>
 <div class="max-w-md mx-auto rounded-lg">
    <div
      class="relative w-full h-10"
      @mousedown="startDrag"
      @touchstart.passive="startTouch"
      @touchmove.prevent="onTouchMove"
      @touchend="stopTouch"
      ref="sliderTrack"
    >
      <!-- Track -->
      <div class="absolute top-1/2 w-full h-2 bg-gray-300 rounded-full -translate-y-1/2">
        <div
          class="h-full bg-[#15919A] rounded-full"
          :style="{ width: percent + '%' }"
        ></div>
      </div>

      <!-- Draggable Bubble -->
      <div
        class="absolute top-[3px] w-8 h-8 border-4 border-[#15919A] bg-white text-[#323232] text-xs flex items-center justify-center rounded-full shadow-md cursor-pointer "
        :style="{ left: percent + '%', transform: 'translate(-50%, 0)' }"
        @mousedown.stop.prevent="startDrag"
        @touchstart.stop.passive="startTouch"
      >
        {{ displayValue.toFixed(1) }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { onUnmounted, onMounted, ref, computed, watch } from 'vue';
import { getElementPosition } from "@/utils/positionUtils";

const props = defineProps({
  modelValue: {
    type: Number,
    default: 1
  },
  min: {
    type: Number,
    default: 1
  },
  max: {
    type: Number,
    default: 100
  },
  step: {
    type: Number,
    default: 1
  }
});

const emit = defineEmits(['update:modelValue']);
console.log(props);

const value = ref(typeof props.modelValue === 'number' && !isNaN(props.modelValue) ? props.modelValue : (props.min ?? 0));
const isDragging = ref(false);
const isTouchDragging = ref(false);
const sliderTrack = ref(null);

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  value.value = (typeof newValue === 'number' && !isNaN(newValue)) ? newValue : (props.min ?? 0);
});

// Watch for internal changes and emit updates
watch(value, (newValue) => {
  emit('update:modelValue', newValue);
});

const normalizedValue = computed(() => {
  const n = Number(value.value);
  return Number.isFinite(n) ? n : (props.min ?? 0);
});

const percent = computed(() => {
  const range = (props.max - props.min) || 1;
  return ((normalizedValue.value - props.min) / range) * 100;
});

const displayValue = computed(() => {
  const n = normalizedValue.value;
  if (props.step < 1) return n;
  return Math.round(n);
});

const startDrag = (event) => {
  isDragging.value = true;
  onDrag(event);
  window.addEventListener('mousemove', onDrag);
  window.addEventListener('mouseup', stopDrag);
};

const stopDrag = () => {
  isDragging.value = false;
  window.removeEventListener('mousemove', onDrag);
  window.removeEventListener('mouseup', stopDrag);
};

const onDrag = (event) => {
  if (!isDragging.value) return;

  const track = sliderTrack.value;
  
  // Use utility function for efficient positioning
  const trackPos = getElementPosition(track);
  
  const offsetX = event.clientX - trackPos.left;
  const clampedX = Math.max(0, Math.min(offsetX, trackPos.width));
  const ratio = clampedX / trackPos.width;
  
  // Calculate the new value based on step
  const rawValue = props.min + ratio * (props.max - props.min);
  const steppedValue = Math.round(rawValue / props.step) * props.step;
  
  // Ensure the value is within bounds
  value.value = Math.max(props.min, Math.min(steppedValue, props.max));
};

// Touch support
const startTouch = (e) => {
  isTouchDragging.value = true;
  onTouchMove(e);
  window.addEventListener('touchmove', onTouchMove, { passive: false });
  window.addEventListener('touchend', stopTouch);
};

const stopTouch = () => {
  isTouchDragging.value = false;
  window.removeEventListener('touchmove', onTouchMove);
  window.removeEventListener('touchend', stopTouch);
};

const onTouchMove = (event) => {
  if (!isTouchDragging.value) return;
  const touch = event.touches && event.touches[0];
  if (!touch) return;
  const track = sliderTrack.value;
  
  const trackPos = getElementPosition(track);
  const offsetX = touch.clientX - trackPos.left;
  const clampedX = Math.max(0, Math.min(offsetX, trackPos.width));
  const ratio = clampedX / trackPos.width;
  const rawValue = props.min + ratio * (props.max - props.min);
  const steppedValue = Math.round(rawValue / props.step) * props.step;
  value.value = Math.max(props.min, Math.min(steppedValue, props.max));
};

onUnmounted(() => {
  window.removeEventListener('mousemove', onDrag);
  window.removeEventListener('mouseup', stopDrag);
  window.removeEventListener('touchmove', onTouchMove);
  window.removeEventListener('touchend', stopTouch);
});
</script>

<style scoped>
.range-slider {
    display: flex;
    align-items: center;
}

.range-slider input {
    margin-right: 10px;
}


input[type='range'] {
  height: 2rem;
  outline: none;
  cursor: pointer;
}

input[type='range']::-webkit-slider-thumb {
  appearance: none;
  height: 24px;
  width: 24px;
  border-radius: 50%;
  background: transparent;
  border: none;
  margin-top: -11px;
  position: relative;
  z-index: 10;
}

input[type='range']::-moz-range-thumb {
  height: 24px;
  width: 24px;
  border-radius: 50%;
  background: transparent;
  border: none;
  position: relative;
  z-index: 10;
}
</style>