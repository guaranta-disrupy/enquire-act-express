
<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import S4Button from '@/S4/Button.vue'


const showModal = (table, modal, method, index = -1) => {
 
}
</script>

<template>
<div>
    
    <div class="header-content flex items-center flex-row">
        <s4-button @click="showModal('gallery',  'add_edit', 'add')" :theme="'floatingBTN'">
            <span class="pl-3 pr-3">Add Video</span>
        </s4-button>
    </div>
    <div>
        <div class="bg-white rounded-lg p-5">
            <!-- <h3 v-if="$page.props.gallery.length<=0" class="text-bold"> No Images yet.</h3> -->
            <div class="overflow-hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                Video
            </div>
        </div>
    </div>
    <div>
        <!-- <gallery-add :modal="modals.gallery" @close="closeModal('gallery', 'add_edit')"></gallery-add>
        <gallery-delete :modal="modals.gallery" @close="closeModal('gallery', 'delete')"></gallery-delete> -->
    </div>
    </div>
</template>

<style>
</style>