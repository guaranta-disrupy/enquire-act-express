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
    <div class="btnRatingColoredListContainer" v-for="(item, indexItems) in items" :key="indexItems">
        <div v-if="items.length > 1" class="absolute left-[610px] cursor-pointer" @click="showAll = !showAll" :class="{'hidden':indexItems !=0 || showAll}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 hover:scale-110">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21L21 17.25" />
            </svg>
        </div>
        <div v-if="items.length > 1" class="absolute left-[610px] bottom-7 cursor-pointer" @click="showAll = !showAll" :class="{'hidden':!showAll}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 hover:scale-110">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12" />
            </svg>
        </div>
        <div class="border rounded-md px-8 py-6 grid gap-2 justify-start bg-white w-[600px] mb-2"
        :class="{'hidden':indexItems != 0 && !showAll}"
        >
            <!-- header -->
            <div class="grid justify-center text-center text-[12px] header leading-3">
                <div v-html="processedHeader(item)"></div>
            </div>
            <div class="grid my-10 scale-[1.10]">
                <div class="w-[500px] grid place-items-center" 
                :class="{
                    'h-[220px]':values.length >= 3 && values.length <= 5,
                    'h-[300px]':values.length >= 6,
                    'h-[150px]':values.length == 2,
                }">
                    <div class="border-8 border-black/0 w-[240px] h-[240px] relative grid place-items-center rounded-full" 
                    :class="{
                        '-top-[40px]':values.length == 2,
                        '-top-[60px]':values.length >= 3 && values.length <= 5,
                        '-top-[0px]':values.length >= 6,
                    }">

                        <!-- items -->
                        <div class="w-[140px] h-[140px] rounded-full grid place-items-center p-2 border-[6px] border-gray-400/30 z-10 bg-white">
                            <!-- individual item -->

                            <!-- * 1 - text only -->
                            <!-- <div v-if="setup.itemsLayout == 1" class="text-center text-[10px] leading-3">{{ item.name }}</div> -->

                            <!-- * 2 - image only / circular || 3 - image only / box -->
                            <!-- <div v-else-if="setup.itemsLayout == 2 || setup.itemsLayout == 3" class="grid text-center p-2">
                                <img :src="item.name" class="max-h-23 max-w-23 rounded-2xl">
                            </div> -->
                            
                            <!-- * 4 - text with image -->  
                            <div class="grid w-full text-center">
                                <div class="grid w-full text-center text-[10px] leading-[9px] p-2">
                                    <div class="headerItem w-full grid items-center leading-[1]" v-html="getTranslatedItem(item)"></div>
                                </div>                               
                            </div>

                        </div>

                        <div class="absolute" v-for="(value, index) in values" :key="index">

                            <svg class="absolute z-10" :class="arrowRotate()?.[index] || ''" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="40" height="40" viewBox="0 0 500 500" xml:space="preserve">
                                <g transform="matrix(6.6149946173 0 0 5.7263159124 245.9396656911 244.6569178853)" id="njubVlrze47oYzbdsJL14"  >
                                <path style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-dashoffset: 0; stroke-linejoin: miter; stroke-miterlimit: 4; fill: rgb(158, 158, 158); fill-rule: nonzero; opacity: 1;" vector-effect="non-scaling-stroke"  transform=" translate(-16.688465, -26.71541)" d="M 0 7.03037 L 19.12261 7.03037 L 19.12261 0 L 33.37693 26.71542 L 26.24977 40.07311 L 19.12261 53.43082 L 19.12261 46.400439999999996 L 0 46.400439999999996 z" stroke-linecap="round" />
                                </g>
                                <g transform="matrix(1 0 0 1 354.0494938133 243.0969156905)" id="b0cc32Od06tIbFCPsAosn"  >
                                <path style="stroke: rgb(0,0,0); stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-dashoffset: 0; stroke-linejoin: miter; stroke-miterlimit: 4; fill: rgb(158, 158, 158); fill-rule: nonzero; opacity: 1;" vector-effect="non-scaling-stroke"  transform=" translate(0.000005, 0)" d="M -92.51969 -176.72229 L 92.51968 0 L -92.51969 176.72229 z" stroke-linecap="round" />
                                </g>
                            </svg>

                        </div>

                        <!-- values -->
                        <div v-for="(value, index) in values" :key="index"
                            class="bg-white cursor-pointer grid rounded-full w-[90px] h-[90px] text-xs place-content-center absolute hover:z-50 valueHover duration-150 p-[6px]"
                            :class="{
                                [valueCount()[index]]: 1,
                                ' border-[6px] border-gray-200 shadow-sm shadow-gray-500': value.color == '#ffffff',
                                ' border-[6px] border-gray-200 shadow-sm shadow-gray-500': value.color,
                            }" :style="{
                                'background-color': value.color,
                            }">                            

                            <!-- individual value -->

                            <!-- * 1 - text only -->
                            <!-- <p v-if="setup.valuesLayout == 1" class="grid text-center overflow-hidden leading-[7px] text-[7px]"
                            :class="{'text-slate-700':value.color == '#ffffff', 'text-white':value.color != '#ffffff'}"    
                            >{{ value.name }}</p> -->

                            <!-- * 2 - image only / circular || 3 - image only / box -->
                            <!-- <div v-else-if="setup.valuesLayout == 2 || setup.valuesLayout == 3" class="grid text-center p-2">
                                <img :src="value.name" class="max-h-12 max-w-12">
                            </div> -->

                            <!-- * 4 - text with image -->
                            <div class="grid text-center"
                            :class="{'text-slate-700':value.color == '#ffffff', 'text-white':value.color != '#ffffff'}">
                                <div class="grid justify-center text-center text-[8px] leading-[6px] p-2" >
                                    <div class="headerValue grid leading-[1]" v-html="getTranslatedValue(value)"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- footer -->
            <div class="grid text-center text-[12px] leading-3">
                <div v-html="processedFooter(item)"></div>
            </div>
        </div>
    </div>
    </div>
</template>

<script setup>
import { ref, computed, inject } from "vue";

const props = defineProps(["items", "values", "setup"]);
const getTranslatedItem = inject('getTranslatedItem', (item) => item?.name || '');
const getTranslatedValue = inject('getTranslatedValue', (value) => value?.name || '');
const getTranslatedSetupHeader = inject('getTranslatedSetupHeader', (setup) => setup?.header || '');
const getTranslatedSetupFooter = inject('getTranslatedSetupFooter', (setup) => setup?.footer || '');
const isRTL = inject('isRTL', false);

// Text replacer computed property that replaces [EQ] with current item equivalent
const processedHeader = computed(() => {
    return (item) => {
        console.log("item", item);
        const header = getTranslatedSetupHeader(props.setup);
        if (!header) return '';
        return header.replace(/\[EQ\]/g, item?.equivalent || '');
    };
});

// Text replacer computed property for footer that replaces [EQ] with current item equivalent
const processedFooter = computed(() => {
    return (item) => {
        const footer = getTranslatedSetupFooter(props.setup);
        if (!footer) return '';
        return footer.replace(/\[EQ\]/g, item?.equivalent || '');
    };
});

// const arrowRotate = () => {
//     let count = props.values?.length ?? 0;

//     if (count == 2) return [
//         'top-[-20px] left-[-95px] rotate-[180deg]', // 1
//         'top-[-20px] left-[55px] rotate-[0deg]' // 5
//     ]

//     else if (count == 3) return [
//         'top-[32px] left-[-74px] rotate-[135deg]', // 2
//         'top-[53px] left-[-20px] rotate-[90deg]', // 3
//         'top-[34px] left-[32px] rotate-[45deg]', // 4
//     ]

//     else if (count == 4) return [
//         'top-[-20px] left-[-95px] rotate-[180deg]', // 1
//         'top-[43px] left-[-62px] rotate-[123deg]', // special
//         'top-[45px] left-[19px] rotate-[59deg]', // special
//         'top-[-20px] left-[55px] rotate-[0deg]', // 5
//     ]

//     else if (count == 5) return [
//         'top-[-20px] left-[-95px] rotate-[180deg]', // 1
//         'top-[32px] left-[-74px] rotate-[135deg]', // 2
//         'top-[53px] left-[-20px] rotate-[90deg]', // 3
//         'top-[34px] left-[32px] rotate-[45deg]', // 4
//         'top-[-20px] left-[55px] rotate-[0deg]', // 5
//     ]

//     else if (count == 6) return [        
//         'top-[-72px] left-[-75px] rotate-[224deg]', // 8
//         'top-[-20px] left-[-95px] rotate-[180deg]', // 1
//         'top-[32px] left-[-74px] rotate-[135deg]', // 2
//         'top-[34px] left-[32px] rotate-[45deg]', // 4
//         'top-[-20px] left-[55px] rotate-[0deg]', // 5
//         'top-[-71px] left-[35px] rotate-[316deg]', // 6        
//     ]

//     else if (count == 7) return [
//         'top-[-72px] left-[-75px] rotate-[224deg]', // 8
//         'top-[-20px] left-[-95px] rotate-[180deg]', // 1
//         'top-[32px] left-[-74px] rotate-[135deg]', // 2
//         'top-[53px] left-[-20px] rotate-[90deg]', // 3
//         'top-[34px] left-[32px] rotate-[45deg]', // 4
//         'top-[-20px] left-[55px] rotate-[0deg]', // 5
//         'top-[-71px] left-[35px] rotate-[316deg]', // 6     
//     ]

//     else if (count == 8) return [
//         'top-[-20px] left-[-95px] rotate-[180deg]', // 1
//         'top-[32px] left-[-74px] rotate-[135deg]', // 2
//         'top-[53px] left-[-20px] rotate-[90deg]', // 3
//         'top-[34px] left-[32px] rotate-[45deg]', // 4
//         'top-[-20px] left-[55px] rotate-[0deg]', // 5
//         'top-[-71px] left-[35px] rotate-[316deg]', // 6
//         'top-[-94px] left-[-20px] rotate-[270deg]', // 7
//         'top-[-72px] left-[-75px] rotate-[224deg]', // 8
//     ]
// }

const arrowRotate = () => {
    let count = props.values?.length ?? 0;
    let arr = [];
    if (count == 2) arr = [
        'top-[-20px] left-[-95px] rotate-[180deg]', // 1
        'top-[-20px] left-[55px] rotate-[0deg]' // 5
    ];
    else if (count == 3) arr = [
        'top-[32px] left-[-74px] rotate-[135deg]', // 2
        'top-[53px] left-[-20px] rotate-[90deg]', // 3
        'top-[34px] left-[32px] rotate-[45deg]', // 4
    ];
    else if (count == 4) arr = [
        'top-[-20px] left-[-95px] rotate-[180deg]', // 1
        'top-[43px] left-[-62px] rotate-[123deg]', // special
        'top-[45px] left-[19px] rotate-[59deg]', // special
        'top-[-20px] left-[55px] rotate-[0deg]', // 5
    ];
    else if (count == 5) arr = [
        'top-[-20px] left-[-95px] rotate-[180deg]', // 1
        'top-[32px] left-[-74px] rotate-[135deg]', // 2
        'top-[53px] left-[-20px] rotate-[90deg]', // 3
        'top-[34px] left-[32px] rotate-[45deg]', // 4
        'top-[-20px] left-[55px] rotate-[0deg]', // 5
    ];
    else if (count == 6) arr = [
        'top-[-72px] left-[-75px] rotate-[224deg]', // 8
        'top-[-20px] left-[-95px] rotate-[180deg]', // 1
        'top-[32px] left-[-74px] rotate-[135deg]', // 2
        'top-[34px] left-[32px] rotate-[45deg]', // 4
        'top-[-20px] left-[55px] rotate-[0deg]', // 5
        'top-[-71px] left-[35px] rotate-[316deg]', // 6        
    ];
    else if (count == 7) arr = [
        'top-[-72px] left-[-75px] rotate-[224deg]', // 8
        'top-[-20px] left-[-95px] rotate-[180deg]', // 1
        'top-[32px] left-[-74px] rotate-[135deg]', // 2
        'top-[53px] left-[-20px] rotate-[90deg]', // 3
        'top-[34px] left-[32px] rotate-[45deg]', // 4
        'top-[-20px] left-[55px] rotate-[0deg]', // 5
        'top-[-71px] left-[35px] rotate-[316deg]', // 6     
    ];
    else if (count == 8) arr = [
        'top-[-20px] left-[-95px] rotate-[180deg]', // 1
        'top-[32px] left-[-74px] rotate-[135deg]', // 2
        'top-[53px] left-[-20px] rotate-[90deg]', // 3
        'top-[34px] left-[32px] rotate-[45deg]', // 4
        'top-[-20px] left-[55px] rotate-[0deg]', // 5
        'top-[-71px] left-[35px] rotate-[316deg]', // 6
        'top-[-94px] left-[-20px] rotate-[270deg]', // 7
        'top-[-72px] left-[-75px] rotate-[224deg]', // 8
    ];
    // Always fill the array to the correct length
    while (arr.length < count) arr.push('');
    return arr;
}

const valueCount = () => {
    let count = props.values.length
    if (count == 2) return [
        'top-[68px] -left-[63px]', // 1
        'top-[67px] left-[198px]', // 5
    ]

    else if (count == 3) return [
        'top-[160px] left-[-24px]', // 2
        'top-[195px] left-[68px]', // 3
        'top-[160px] left-[160px]', // 4
    ]

    else if (count == 4) return [
        'top-[68px] -left-[63px]', // 1
        'top-[180px] left-[-1px]', // 2
        'top-[178px] left-[138px]', // 3
        'top-[67px] left-[198px]', // 5
    ]

    else if (count == 5) return [
        'top-[68px] -left-[63px]', // 1
        'top-[160px] left-[-24px]', // 2
        'top-[195px] left-[68px]', // 3
        'top-[160px] left-[160px]', // 4
        'top-[67px] left-[198px]', // 5
    ]

    else if (count == 6) return [
        'top-[-26px] left-[-29px]', // 8
        'top-[68px] -left-[63px]', // 1
        'top-[160px] left-[-24px]', // 2
        'top-[160px] left-[160px]', // 4
        'top-[67px] left-[198px]', // 5
        'top-[-26px] left-[161px]', // 6     
    ]

    else if (count == 7) return [
        'top-[-26px] left-[-29px]', // 8
        'top-[68px] -left-[63px]', // 1
        'top-[160px] left-[-24px]', // 2
        'top-[195px] left-[68px]', // 3
        'top-[160px] left-[160px]', // 4
        'top-[67px] left-[198px]', // 5
        'top-[-26px] left-[161px]', // 6   
    ]

    else if (count == 8) return [
        'top-[-26px] left-[-29px]',// 8
        'top-[68px] -left-[63px]', // 1
        'top-[160px] left-[-24px]', // 2
        'top-[195px] left-[68px]', // 3
        'top-[160px] left-[160px]', // 4
        'top-[67px] left-[198px]', // 5
        'top-[-26px] left-[161px]', // 6
        'top-[-63px] left-[66px]', // 7        
    ]
}

const showAll = ref(false)


</script>

<style scoped>
.stroke {
    color: white;
    text-shadow: -1px -1px 0 #757575, 1px -1px 0 #757575, -1px 1px 0 #757575, 1px 1px 0 #757575;
}

/* .header :deep() img {
    max-width: 40px;
    max-height: 40px;
    display: inline;
    margin: auto;
} */
/* .headerItem :deep() img {
    max-width: 40px;
    max-height: 40px;
    margin: auto;
} */
/* .headerValue :deep() img {
    max-width: 30px;
    max-height: 30px;
    margin: auto;
} */
.valueHover:hover {
    transform: scale(2);
}
.btnRatingColoredListContainer{
    --fs1: 6px;
    --fs2: 8px;
    --fs3: 10px;
    --fs4: 14px;
    --fsl: 18px;
}

.btnRatingColoredListContainer :deep() .ssr-import-image {
    width: var(--imgwidth, 70%);
    max-width: 70%;
    margin: auto;
    max-height: 70%;
    object-fit: contain;
}

.btnRatingColoredListContainer :deep() p{
    width:100%;
}
</style>