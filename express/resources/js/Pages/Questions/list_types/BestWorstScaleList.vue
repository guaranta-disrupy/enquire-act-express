<script setup>
import { inject } from 'vue';

const props = defineProps(['items', 'setup', 'choices']);
const getTranslatedChoice = inject('getTranslatedChoice', (choice) => choice?.name || '');
const getTranslatedBestWorstLabel = inject('getTranslatedBestWorstLabel', (setup, key) => {
    if (key === 'labelA') return setup?.bestWostScale?.labelA || '';
    if (key === 'labelB') return setup?.bestWostScale?.labelB || '';
    return '';
});
const isRTL = inject('isRTL', false);
</script>
<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="">
        <table class="item-trial">
            <tr>
                <th>
                    <div v-html="getTranslatedBestWorstLabel(setup, 'labelA')"></div>
                </th>
                <th></th>
                <th>
                    <div v-html="getTranslatedBestWorstLabel(setup, 'labelB')"></div>
                </th>
            </tr>
            <tr v-for="(choice, idx) in choices.slice(0, setup.bestWostScale.itemsPerSet)" :key="idx">
                <td>
                    <div class="inline-block justify-self-end text-right border rounded-[50%] p-2 bg-[#ededed]"></div>
                </td>
                <td class="">
                    <div v-html="getTranslatedChoice(choice)"></div>
                </td>
                 <td >
                    <div class="inline-block justify-self-end text-right border rounded-[50%] p-2 bg-[#ededed]"></div>
                </td>
            </tr>

        </table>
    </div>
</template>

<style scoped>
.item-trial {
    text-align: center;
    border: 1px solid #e9e9e9;
    border-collapse: collapse;
}

.item-trial th {
    font-size: var(--font-sm);
    font-weight: 500;
    border: 1px solid #e9e9e9;
    background: #f5f5f5;
    padding: 1vmin 1vmin;
}

.item-trial td {
    border: 1px solid #e9e9e9;
    padding: 1vmin 1vmin;
    font-size: var(--font);
}
</style>