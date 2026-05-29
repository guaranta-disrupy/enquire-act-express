<script setup>
const props = defineProps(['items','headers'])
 
</script>

<template>
    <div class="cnt rounded-md mx-4 pb-4 ml-12 mt-2 mb-2"> 
        <div class="font-bold text-slate-400 text-sm mb-3 p-1 bg-slate-50">
            <slot name="header"/>
        </div>
        <div>
            <slot name="content"/>
        </div>
    </div>

</template>

<style scoped>
.cnt{
    border: 2px solid #DCE0E370;
}
</style>