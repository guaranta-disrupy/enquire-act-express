<template>
    <!-- 
        ** @props.setup
        *
        ** itemsLayout
        * 
        * 1 - text only
        * 2 - image only / circular
        * 3 - image only / box
        * 4 - text with image
        * 
        ** valuesLayout
        *
        * 1 - text only
        * 2 - image only / circular
        * 3 - image only / box
        * 4 - text with image
    -->
    <div :dir="isRTL ? 'rtl' : 'ltr'">
    <div v-for="(item, indexItems) in items" :key="indexItems">
        <div class="border rounded-md px-8 py-6 grid gap-2 justify-start bg-white w-[700px] mb-2">
            <div class="grid justify-center text-center text-sm">
                {{ setup.header }} 
            </div>
            <div class="grid my-14 scale-125">
                <div class="w-[600px] h-[300px] grid place-items-center">
                    <div class="border-8 w-[240px] h-[240px] relative grid place-items-center rounded-full">

                        <!-- items -->
                        <div class="w-[145px] h-[145px] rounded-full grid place-items-center p-2">
                            <!-- individual item -->
                            <div class="text-center text-[10px]">{{ item.name }}</div>
                        </div>

                        <!-- values -->
                        <div v-for="(value, index) in values" :key="index" class="bg-white cursor-pointer grid rounded-full w-[90px] h-[90px] text-xs place-content-center absolute" 
                        :class="{
                            [valueCount()[index]]:1,
                            'border-2': !value.color,                     
                        }"
                        :style="{
                            'background-color': value.color,                            
                        }"
                        >
                            <!-- individual value --> 
                            
                            <!-- * 1 - text only -->
                            <p v-if="setup.valuesLayout==1" class="grid text-center text-[10px] p-2" :class="{'stroke':value.color}">{{ value.name }}</p>

                            <!-- * 2 - image only / circular -->
                            <div v-else-if="setup.valuesLayout==2" class="grid text-center p-2">
                                <img :src="value.name" class="max-h-12 max-w-12 rounded-full">
                            </div>
                        </div>  

                    </div>                    
                </div>
            </div>
            <div class="grid justify-center text-sm">
                {{ setup.footer }}
            </div>
        </div>
    </div>
    </div>
</template>

<script setup>
import { ref, inject } from "vue";


const props = defineProps(["items", "values", "setup"]);
const isRTL = inject('isRTL', false);

const valueCount = () => {
    let count = props.values.length
    if (count==2) return [
        'top-[70px] -left-[50px]',
        'top-[70px] left-[185px]'
    ]

    else if (count==3) return [
        'top-[10px] -left-[38px]',
        'top-[185px] left-[70px]',
        'top-[10px] left-[170px]'
    ]

    else if (count==4) return [
        'top-[70px] -left-[50px]',
        'top-[185px] left-[70px]',
        'top-[70px] left-[185px]',
        'top-[-50px] left-[70px]'
    ]

    else if (count==5) return [
        'top-[30px] -left-[45px]',
        'top-[160px] left-[-5px]',
        'top-[160px] left-[140px]',
        'top-[30px] left-[178px]',
        'top-[-50px] left-[67px]'
    ]

    else if (count==6) return [
        'top-[70px] -left-[50px]',
        'top-[170px] left-[10px]',
        'top-[170px] left-[125px]',
        'top-[70px] left-[185px]',
        'top-[-35px] left-[125px]',
        'top-[-35px] left-[10px]'
    ]

    else if (count==7) return [
        'top-[70px] -left-[50px]',
        'top-[161px] left-[-4px]',
        'top-[179px] left-[97px]',
        'top-[119px] left-[174px]',
        'top-[18px] left-[174px]',
        'top-[-47px] left-[93px]',
        'top-[-24px] left-[-8px]'
    ]

    else if (count==8) return [
        'top-[68px] -left-[51px]',
        'top-[150px] left-[-14px]',
        'top-[183px] left-[68px]',
        'top-[150px] left-[150px]',
        'top-[67px] left-[185px]',
        'top-[-16px] left-[151px]',
        'top-[-48px] left-[66px]',
        'top-[-16px] left-[-19px]'
    ]
}


</script>

<style scoped>
.stroke {
    color: white;
    text-shadow: -1px -1px 0 #757575, 1px -1px 0 #757575, -1px 1px 0 #757575, 1px 1px 0 #757575;
}
</style>