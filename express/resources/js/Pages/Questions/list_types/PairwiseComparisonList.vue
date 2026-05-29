<script setup>
import { inject } from 'vue';

const props = defineProps(['items', 'setup', 'values', 'question']);
const getTranslatedItemStatementA = inject('getTranslatedItemStatementA', (item) => item?.statementA || '');
const getTranslatedItemStatementB = inject('getTranslatedItemStatementB', (item) => item?.statementB || '');
const getTranslatedSetupLabel = inject('getTranslatedSetupLabel', (setup, key) => {
    if (key === 'statementA') return setup?.labels?.statementA || '';
    if (key === 'statementB') return setup?.labels?.statementB || '';
    return '';
});
const getTranslatedSetupFooter = inject('getTranslatedSetupFooter', (setup) => setup?.footer || '');
const isRTL = inject('isRTL', false);
</script>

<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'">
    <!-- <div class="flex justify-center mb-2">
        <div v-html="question"></div>
    </div> -->

    <div class="grid grid-cols-3 text-xs mr-30 mb-2  font-semibold">
        <div class="text-right" v-html="getTranslatedSetupLabel(setup, 'statementA')"></div>
        <div></div>
        <div class="" v-html="getTranslatedSetupLabel(setup, 'statementB')"></div>
        <div class="inline-block justify-self-end text-right border rounded-md p-2" v-if="items && items.length > 0"
            v-html="getTranslatedItemStatementA(items[0])">
        </div>
        <div class="lineBox ">
            <div class="line ml-2"></div>
        </div>
        <div class="inline-block justify-self-start border rounded-md p-2" v-if="items && items.length > 0"
            v-html="getTranslatedItemStatementB(items[0])">
        </div>
    </div>
    <div class="flex justify-center">
        <div v-html="getTranslatedSetupFooter(setup)"></div>
    </div>
    </div>

</template>

<style scoped>
.lineBox {
    position: relative;
    width: 95%;
    display: flex;
    align-items: center;
    z-index: 3;
}

.line {
    width: 100%;
    height: 5px;
    position: absolute;
    background: gray;

}

.line::after {
    content: "";
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-bottom: 10px solid #808080;
    transform: rotate(-90deg);
    position: absolute;
    left: -8px;
    top: -2.5px;
}

.line::before {
    content: "";
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-bottom: 10px solid #808080;
    transform: rotate(90deg);
    position: absolute;
    right: -8px;
    top: -2.5px;
}
</style>