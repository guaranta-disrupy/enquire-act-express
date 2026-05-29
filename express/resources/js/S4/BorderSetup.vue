<template>
    <div>
        <p class="border-b-2 border-[var(--mcolor)] mb-2" style="border-style:solid;">
            <span class="setupCont rounded-[10px_0px_0px] bg-[var(--mcolor)] text-white font-bold text-[15px]">
                <template v-if="hasSlots">
                    <slot name="header" />
                </template>
                <template v-else>
                    Options & Layout
                </template>
            </span>
        </p>

        <div
            v-if="showDataType"
            class="w-[50vmin] mt-2 mb-2"
            :class="{
                'ml-6':
                    add_edit_form &&
                    (add_edit_form.question_type_id === 1 ||
                        add_edit_form.question_type_id === 2 ||
                        add_edit_form.question_type_id === 3 ||
                        add_edit_form.question_type_id === 4 ||
                        add_edit_form.question_type_id === 5 ||
                        add_edit_form.question_type_id === 6 ||
                        add_edit_form.question_type_id === 7 ||
                        add_edit_form.question_type_id === 8 ||
                        add_edit_form.question_type_id === 10 ||
                        add_edit_form.question_type_id === 16 ||
                        add_edit_form.question_type_id === 11 ||
                        add_edit_form.question_type_id === 19 ||
                        add_edit_form.question_type_id === 21),
                'ml-10':
                    add_edit_form &&
                    (add_edit_form.question_type_id === 9 ||
                        add_edit_form.question_type_id === 15 ||
                        add_edit_form.question_type_id === 20 ||
                        add_edit_form.question_type_id === 22),
            }"
        >
            <jet-label>Data Type</jet-label>
            <vue-form-multiselect class="testType" placeholder="Select Data Type" :options="dataTypeList"
                v-model="add_edit_form.setup.dataType" />
        </div>
    </div>
</template>
<script setup>
import { useSlots, computed, inject } from 'vue';
import JetLabel from '@/Jetstream/Label.vue';
import VueFormMultiselect from '@/S4/vueform/Multiselect.vue';

const slots = useSlots();
const hasSlots = computed(() => !!slots.header);

const add_edit_form = inject('add_edit_form', null);

const dataTypeList = [
    "nominal",
    "binary",
    "ordinal",
    "interval",
    "ratio",
    "continuous",
    "discrete",
    "qualitative",
    "derived",
];

const showDataType = computed(() => {
    // Only show the Data Type selector when:
    // - we have access to add_edit_form.setup
    // - and the default header ("Options & Layout") is being used
    //   (i.e. no custom header slot like "Analysis Options")
    return !!(add_edit_form && add_edit_form.setup) && !hasSlots.value;
});
</script>

<style scoped>

.setupCont {
    padding:0.2rem 1rem 0.2rem 1rem;
    border-top-right-radius:0.8rem;
   /*  border-top-left-radius:0.8rem; */
    position: relative;
}

.setupCont2 {
    padding:0.2rem 1rem 0.2rem 1rem;
    border-top-right-radius:0.8rem;
    border-top-left-radius:0.8rem;
}


.setupCont:before,
.setupCont:after {
    content: "";
    position: absolute;

    height: 10px;
    width: 20px;

    bottom: 0;
}

.setupCont:after {
    right: -20px;

    border-radius: 0 0 0 10px;
    -moz-border-radius: 0 0 0 10px;
    -webkit-border-radius: 0 0 0 10px;

    -webkit-box-shadow: -10px 0 0 0 #d1d5db;
    box-shadow: -10px 0 0 0 var(--mcolor);
}

</style>