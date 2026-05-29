<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import JetCheckbox from "@/Jetstream/Checkbox.vue";
import S4TextArea from "@/S4/TextArea.vue";
import S4Input from "@/S4/Input.vue";
import ButtonRatingColoredList from '../list_types/ButtonRatingColoredList.vue'
import { onBeforeMount, onMounted, ref, computed, watch, inject } from "vue";

const props = defineProps(["items", "values", "setup", "type"]);
const getTranslatedItem = inject('getTranslatedItem', (item) => item?.name || '');
const getTranslatedValue = inject('getTranslatedValue', (value) => value?.name || '');
const isRTL = inject('isRTL', false);

onBeforeMount(() => {
    setSliderIndex();
});

const ind = ref([]);
const itemName = ref(null);

// const setSliderIndex = () => {
//     let newDef = [];
//     if (props.setup.default > props.values.length - 1) newDef = 0;
//     else newDef = props.setup.default;
//     Object.keys(props.items).map((item) => {
//         ind.value.push(newDef);
//     });
//     return ind.value;
// };
const setSliderIndex = () => {
    let newDef = props.setup.default - 1;
    if (newDef < 0 || newDef > props.values.length - 1) newDef = 0;
    ind.value = props.items.map(() => newDef);
};

const getIndex = (indexI, index) => {
    ind.value[indexI] = index;
};
const ifImage = (val) => {
    const searchSTR = "<img ";

    if (val.includes(searchSTR)) {
        const match = val.match(/\"(.*?)\"/i);
        if (match) {
            const imageSrc = match[1];
            itemName.value = imageSrc;
            return true;
        }
    }
    return false;
};

const computedItems = computed(() =>
    props.items.map((item) => {
        const searchSTR = "<img ";
        const isImage = item.name.includes(searchSTR);
        const match = isImage ? item.name.match(/\"(.*?)\"/i) : null;
        const imageSrc = match ? match[1] : null;
        return { ...item, isImage: isImage && !!imageSrc, imageSrc };
    })
);

const updateIndex = (indexI, index) => {
    ind.value[indexI] = index;
};

const getValuesClass = () => {
    const valueCount = props.values.length;
    if (valueCount >= 13) return 'valuesCont-many';
    if (valueCount === 12) return 'valuesCont-12';
    if (valueCount === 11) return 'valuesCont-11';
    if (valueCount === 10) return 'valuesCont-10';
    if (valueCount === 9) return 'valuesCont-9';
    if (valueCount === 8) return 'valuesCont-8';
    return 'valuesCont-default';
};

watch(
  () => props.setup.default,
  (newDefault) => {
    let newDef = newDefault - 1;
    if (newDef < 0 || newDef > props.values.length - 1) newDef = 0;
    ind.value = props.items.map(() => newDef);
  }
);
</script>

<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'">
    <div class="pt-2 grid grid-cols-1" v-if="type === 8">
        <div
            class="sliderParent"
            v-for="(item, indexI) in computedItems"
            :key="indexI"
        >
            <p class="iCont" v-if="!item.isImage">
                <span class="iName" v-html="getTranslatedItem(item)"></span>
            </p>
            <p class="iCont" v-else>
                <img class="iName h-12" :src="item.imageSrc" />
            </p>
            <div class="iCont">
                <div
                    @click="updateIndex(indexI, index)"
                    class="valuesCont mt-3"
                    :class="getValuesClass()"
                    v-for="(value, index) in values"
                    :key="index"
                >
                    <div class="slider" style="position: relative">
                        <div
                            :class="{ sliderPointer: index == ind[indexI] }"
                            :style="{
                                'background-color': value.color,
                                'border-color': value.color,
                            }"
                        ></div>
                        <div
                            class="sliderCont"
                            :class="{
                                sliderFirst: index === 0,
                                sliderLast: index === values.length - 1,
                            }"
                        ></div>
                    </div>
                    <div class="valuesNameCont mt-2">
                        <div
                            class="valuesName pl-2 pr-2"
                            :class="{ valuesNameBold: index === ind[indexI] }"
                            v-html="getTranslatedValue(value)"
                        >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <template v-if="type === 15">
        <button-rating-colored-list :setup="setup" :items="items" :values="values"></button-rating-colored-list>       

    </template>
    </div>
</template>

<style scoped>
.valuesCont {
    display: inline-block;
    width: 15vmin;
    text-align: center;
    font-size: 1.1vmin;
    cursor: pointer;
}

/* Adjust width and font size based on number of values */
.valuesCont-9 {
    width: 12vmin !important;
    font-size: 1vmin !important;
}

.valuesCont-8 {
    width: 13.5vmin !important;
    font-size: 1.05vmin !important;
}

.valuesCont-10 {
    width: 10vmin !important;
    font-size: 0.9vmin !important;
}

.valuesCont-11 {
    width: 8.5vmin !important;
    font-size: 0.8vmin !important;
}

.valuesCont-12 {
    width: 7.5vmin !important;
    font-size: 0.75vmin !important;
}

.valuesCont-many {
    width: 6vmin !important;
    font-size: 0.7vmin !important;
}
.valuesNameCont {
    height: 6vmin;
    display: table;
}
.valuesName {
    width: 15vmin;
    display: table-cell;
    text-align: center;
}

/* Adjust label width to match segment width */
.valuesCont-9 .valuesName {
    width: 12vmin !important;
}

.valuesCont-8 .valuesName {
    width: 13.5vmin !important;
}

.valuesCont-10 .valuesName {
    width: 10vmin !important;
}

.valuesCont-11 .valuesName {
    width: 8.5vmin !important;
}

.valuesCont-12 .valuesName {
    width: 7.5vmin !important;
}

.valuesCont-many .valuesName {
    width: 6vmin !important;
}
/* Legacy hover styles removed - using new slider design */
.valuesCont:hover > .valuesNameCont > .valuesName {
    color: #C5C3C3 !important;
}
.valuesNameBold {
    font-weight: 700;
}
.sliderCont {
    background-color: rgb(227, 223, 223);
    height: 1vmin;
    margin-top: 0.5vmin;
}
.sliderFirst,
.sliderLast {
    width: 7.5vmin;
    margin-top: 0.5vmin;
}
.valuesCont-8 .sliderFirst,
.valuesCont-8 .sliderLast {
    width: 6.75vmin; /* half of 13.5 to keep rounded ends aligned */
}
.sliderFirst {
    margin-left: 7.5vmin;
    border-top-left-radius: 3rem;
    border-bottom-left-radius: 3rem;
}
.sliderLast {
    border-top-right-radius: 3rem;
    border-bottom-right-radius: 3rem;
}
.sliderPointer {
    width: 2.5vmin;
    height: 2.5vmin;
    border: 0.3vmin solid lightgrey;
    border-radius: 50%;
    position: absolute;
    top: -0.7vmin;
    left: 6.25vmin;
    background: white;
    z-index: 1;
}
.iName {
    display: inline;
}
.iCont {
    text-align: center;
}

</style>