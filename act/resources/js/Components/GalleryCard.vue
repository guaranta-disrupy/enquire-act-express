<template>
    <div class="bg-white shadow border-gray-300 border flex flex-col col-span-1 rounded-xl">
     
         <!-- Action button -->
        <div class=" border-gray-300 p-2  justify-between flex">
            <slot name="actions"></slot>
        </div>


        <div class=" mt-auto">

          
          <!-- Gallery Image -->
        <div class="py-1 text-center">
            <slot name="galleryimage"></slot>
        </div>


</div>
    </div>
</template>