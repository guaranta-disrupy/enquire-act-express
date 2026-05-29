<script setup>
import { computed } from "vue";

const emit = defineEmits(["close"]);

const props = defineProps({
  show: { type: Boolean, default: false },
  maxWidth: { type: String, default: "44.5rem" },
  modalClass: { type: String, default: "" },
  positionMode: {
    type: String,
    default: "fixed-default", 
  },
  anchorLeft: { type: Number, default: null },
  anchorTop: { type: Number, default: null },


  modalHeight: { type: String, default: "70vmin" },
});

const close = () => emit("close");

// ✅ If anchored, we use left/top. Otherwise keep bottom/right defaults.
const positionClass = computed(() => {
  return props.positionMode === "anchored"
    ? "fixed z-50 bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden"
    : "fixed bottom-[107px] right-[4vmin] z-50 bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden";
});

const positionStyle = computed(() => {
  const base = {
    width: props.maxWidth,
    height: props.modalHeight,
    transition: "width 200ms cubic-bezier(0.4, 0, 0.2, 1)",
    pointerEvents: "auto",
  };

  if (props.positionMode === "anchored") {
    return {
      ...base,
      left: (props.anchorLeft ?? 0) + "px",
      top: (props.anchorTop ?? 0) + "px",
      bottom: "auto",
      right: "auto",
    };
  }

  return base;
});
</script>

<template>
  <teleport to="body">
    <transition
      appear
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="show"
        :class="[positionClass, modalClass]"
        :style="positionStyle"
        @click.stop
      >
        <!-- Header Slot -->
        <div v-if="$slots.header" class="border-b border-gray-200 bg-white p-3">
          <slot name="header" />
        </div>

        <!-- Content Slot -->
        <div v-if="$slots.content" class="flex-1 overflow-hidden flex flex-col h-[580px]">
          <slot name="content" />
        </div>

        <!-- Footer Slot -->
        <div v-if="$slots.footer">
          <slot name="footer" />
        </div>
      </div>
    </transition>
  </teleport>
</template>
