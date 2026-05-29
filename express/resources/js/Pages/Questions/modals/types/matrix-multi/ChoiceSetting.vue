<script setup>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetLabel from "@/Jetstream/Label.vue";
import S4Button from "@/S4/Button.vue";
import S4Input from "@/S4/Input.vue";
import S4InputSelect from "@/S4/InputSelect.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4TextArea from "@/S4/TextArea.vue";
import ButtonIconsVue from "../../../../../S4/ButtonIcons.vue";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faEdit,
    faTrashAlt,
    faPlusSquare,
} from "@fortawesome/free-solid-svg-icons";
import { ref, reactive, onUpdated, watch, inject, nextTick } from "vue";
import helpers from "@/helpers.js";

const { assign } = helpers();
const add_edit_form = inject("add_edit_form");
const emit = defineEmits(["close"]);
const editIndex = ref(0);
const props = defineProps({
    modal: {
        default: {
            choice_setting: false,
            method: "edit",
            data: null,
        },
    },
});

const default_column = {
    name: "",
    type: 1,
    // textbox settings
    validate: {
        type: 1,
        min: 0,
        max: 1,
        errorMsg: "Please provide an answer",
    },
    //input select settings
    placeholder: "",
    items: [],
};
const new_column = ref({});
watch(
    () => props.modal,
    (modal) => {
        //assign values to edit
        if (modal.choice_setting == true) {
            clearform();
            // new_column = _.cloneDeep(column_copy)
        }
    },
    { deep: true }
);

const faIcon = {
    edit: faEdit,
    trash: faTrashAlt,
    plus: faPlusSquare,
};

const type = [
    { value: 1, text: "Dropdown", category: "enter" },
    { value: 2, text: "Radio", category: "enter" },
    { value: 3, text: "Textbox", category: "enter" },
];

const text_type = [
    {
        value: 1,
        text: "Character Length",
    },
    {
        value: 2,
        text: "Whole Number",
    },
    {
        value: 3,
        text: "Decimal Number",
    },
    {
        value: 4,
        text: "Email Address",
    },
    {
        value: 5,
        text: "Date",
    },
    {
        value: 6,
        text: "Time",
    },
    {
        value: 7,
        text: "Date Time",
    },
];

const empty_fields = reactive({
    type: false,
    name: false,
    textarea: false,
    validate_type: false,
});

const matrixMultiRefs = reactive({
    dropRefs: [],
    radRefs: [],
});

const dropDownArea = ref(null);
const editChoice = ref(false);
const multiple_items = ref(false);

const newdrop_choice = ref("");
watch(newdrop_choice, async (val) => {
    if (val != "" && new_column.value.type == 1) {
        new_column.value.items.push({
            name: val,
            value: new_column.value.items.length,
        });
        await nextTick();
        let ref_length = matrixMultiRefs.dropRefs.length - 1;
        matrixMultiRefs.dropRefs[ref_length].focus();
        newdrop_choice.value = "";
    }
});

watch(
    () => new_column.value.name,
    async (name) => {
        if (name?.length > 0) empty_fields.name = false;
        if (name?.length > 0 && new_column.value.type == 2) {
            add_edit_form.items[props.modal.index].column.push({
                ...new_column.value,
            });
            await nextTick();
            let ref_length = matrixMultiRefs.radRefs.length - 1;
            matrixMultiRefs.radRefs[ref_length].focus();
            new_column.value.name = "";
        }
    }
);
watch(
    () => new_column.value.type,
    (type) => {
        if (type > 0 && editChoice == false) {
            empty_fields.type = false;
            // new_column.value.name = "";
        }
    }
);

onUpdated(() => {
    // clearform()
});

const close = () => {
    clearform();
    emit("close");
};

const choiceSettingsChecker = () => {
    if (
        multiple_items.value === true &&
        dropDownArea.value === null &&
        new_column.value.name === ""
    ) {
        empty_fields.name = true;
        empty_fields.textarea = true;
        throw "nothing to input";
    }

    if (new_column.value.name.trim() == "" && multiple_items.value == false) {
        empty_fields.name = true;
        throw "empty column name";
    }

    if (new_column.value.validate.type < 1 && new_column.value.type == 3) {
        empty_fields.validate_type = true;
        throw "no text box type selected";
    }
    if (
        (new_column.value.type == 1 &&
            multiple_items.value === true &&
            dropDownArea.value === null) ||
        (new_column.value.type == 2 &&
            multiple_items.value === true &&
            dropDownArea.value === null)
    ) {
        empty_fields.textarea = true;
        throw "text area empty";
    }
};

const clearform = () => {
    editChoice.value = false;
    dropDownArea.value = null;
    multiple_items.value = false;

    empty_fields.name = false;
    empty_fields.type = false;
    empty_fields.textarea = false;
    empty_fields.validate_type = false;

    new_column.value = assign(default_column);
    matrixMultiRefs.dropRefs = [];
    matrixMultiRefs.radRefs = [];
};

const updateForm = () => {
    add_edit_form.items[props.modal.index].column[editIndex.value] = assign(
        new_column.value
    );
    editIndex.value = -1;
    clearform();
};

const addToColumns = () => {
    choiceSettingsChecker();

    let starting_index = new_column.value;
    let dropdownChoices =
        dropDownArea.value !== null ? dropDownArea.value.split("\n") : []; // explode text by new line to array
    //for dropdown textbox
    _.forEach(dropdownChoices, (drop, dindex) => {
        switch (new_column.value.type) {
            case 1:
                new_column.value.items.push({
                    name: drop,
                    value: starting_index + dindex,
                }); // add items to column by name and value
                break;
            case 2:
                let tmp_column = assign(new_column.value);
                tmp_column.name = drop;
                add_edit_form.items[props.modal.index]["column"].push(
                    tmp_column
                );
                // console.log('pushed to radio',drop)
                break;
        }
    });
    if (editChoice.value === false)
        add_edit_form.items[props.modal.index]["column"].push({
            ...new_column.value,
        });

    clearform();
};

const edit_column = (data, index) => {
    // dont't need edit column kay naka sync naman ang data sa global
    console.log(data, index);
    editIndex.value = parseInt(index);
    editChoice.value = true;
    if (data.type == 2) {
        new_column.value.type = assign(data.type);
        return;
    }
    new_column.value = assign(data);
};

const remove_column = (index) => {
    add_edit_form.items[props.modal.index]["column"].splice(index, 1);
};

const cancelAction = () => {
    editChoice.value = false;
    editIndex.value = -1;
    clearform();
    // new_column = _.cloneDeep(column_copy)
};
</script>

<template>
    <jet-dialog-modal :show="modal.choice_setting" @close="close()">
        <template #title>
            <span>Choice settings</span>
        </template>
        <template #content>
            <div class="col-span-3">
                <jet-label>Type:</jet-label>
                <s4-input-select
                    class="w-full"
                    color="ssrGray"
                    :lists="type"
                    v-model="new_column.type"
                ></s4-input-select>
                <!-- <select
                    class="box-2 form-select cursor-pointer font-semibold shadow-none text-gray-700 rounded-md border focus:ring-0 placeholder-gray-400 w-full border-gray-300 focus:border-ssr-blue3 focus:ring-1 focus:ring-ssr-blue3"
                    :class="{ emptyValues: empty_fields.type }"
                    v-model="new_column.type"
                >
                    <option
                        :value="list.value"
                        v-for="(list, index) in type"
                        :key="index"
                    >
                        {{ list.text }}
                    </option>
                </select> -->
            </div>
            <div class="grid grid-cols-6 gap-1 mt-5">
                <div class="col-span-6">
                    <jet-label>Name:</jet-label>
                    <div class="col-span-2">
                        <s4-input
                            color="ssrRedIcon"
                            class="w-full mb-1 border-2"
                            :class="{ emptyValues: empty_fields.name }"
                            placeholder="Enter name"
                            v-model="new_column.name"
                        ></s4-input>
                    </div>
                </div>
                <!-- Drop down Area -->
                <template v-if="new_column.type === 1">
                    <div class="col-span-6 mt-5">
                        <jet-label>Dropdown options:</jet-label>

                        <div>
                            <template
                                v-for="(drop, index) in new_column.items"
                                :key="index"
                            >
                                <div class="grid grid-cols-12">
                                    <div class="col-span-10">
                                        <s4-input
                                            color="ssrGray"
                                            class="w-full mb-1"
                                            placeholder="Enter title"
                                            v-model="drop.name"
                                            :ref="
                                                (el) => {
                                                    matrixMultiRefs.dropRefs[
                                                        index
                                                    ] = el;
                                                }
                                            "
                                        >
                                        </s4-input>
                                    </div>
                                    <div class="col-span-2">
                                        <ButtonIconsVue
                                            icon="delete"
                                            @click="
                                                new_column.items.splice(
                                                    index,
                                                    1
                                                )
                                            "
                                        />
                                        <!-- <s4-button
                                            color="red"
                                            class="mb-1 ml-1"
                                            @click="
                                                new_column.items.splice(
                                                    index,
                                                    1
                                                )
                                            "
                                        >
                                            <font-awesome-icon
                                                class="fa-w-20 fa-h-20"
                                                :icon="faIcon.trash"
                                            />
                                        </s4-button> -->
                                    </div>
                                </div>
                            </template>
                        </div>

                        <s4-input
                            class="w-full mb-3"
                            color="orange"
                            v-model="newdrop_choice"
                            placeholder="Input new dropdown choice"
                        ></s4-input>
                    </div>
                </template>
                <!-- End of Dropdrown -->
                <!-- radio -->
                <template v-if="new_column.type == 2">
                    <div class="col-span-6">
                        <div
                            class=""
                            v-for="(rad, index) in add_edit_form.items[
                                modal.index
                            ].column"
                            :key="index"
                        >
                            <div class="w-full flex">
                                <s4-input
                                    color="ssrGray"
                                    class="w-full mb-1"
                                    placeholder="Enter name"
                                    v-model="rad.name"
                                    :ref="
                                        (el) => {
                                            matrixMultiRefs.radRefs[index] = el;
                                        }
                                    "
                                >
                                </s4-input>
                                <ButtonIconsVue
                                    icon="delete"
                                    @click="
                                        add_edit_form.items[
                                            modal.index
                                        ].column.splice(index, 1)
                                    "
                                />
                                <!-- <s4-button
                                    color="ssrRedIcon"
                                    class="mb-1 ml-1"
                                    @click="
                                        add_edit_form.items[
                                            modal.index
                                        ].column.splice(index, 1)
                                    "
                                >
                                    <font-awesome-icon
                                        class="fa-w-20 fa-h-20"
                                        :icon="faIcon.trash"
                                    />
                                </s4-button> -->
                            </div>
                        </div>
                    </div>
                </template>
                <!-- end of radio -->
                <!-- multiple items -->
                <div class="col-span-6" v-if="new_column.type != 3">
                    <s4-text-area
                        v-if="multiple_items"
                        color="ssrGray"
                        class="w-full h-40"
                        :class="{ emptyValues: empty_fields.textarea }"
                        v-model="dropDownArea"
                        placeholder="Enter choices separated by new line..."
                    ></s4-text-area>
                    <template v-if="!multiple_items">
                        <s4-button
                            :theme="'ssrAddColRow'"
                            @click="multiple_items = !multiple_items"
                            class="ml-1 pt-1 pb-1 text-sm pl-3 pr-3"
                            >Multiple Items</s4-button
                        >
                    </template>
                    <template v-else>
                        <s4-button
                            :theme="'ssrAddColRow'"
                            @click="addToColumns"
                            class="ml-1 pt-1 pb-1 text-sm pl-3 pr-3"
                            >Add</s4-button
                        >
                        <s4-button
                            :theme="'ssrAddColRow'"
                            @click="multiple_items = false"
                            class="br-0 ml-1 pt-1 pb-1 text-sm pl-3 pr-3"
                            >Cancel</s4-button
                        >
                    </template>
                </div>
                <!-- end of multiple items -->
                <!-- textbox -->
                <template v-if="new_column.type == 3">
                    <div class="col-span-2 mt-5">
                        <jet-label>Textbox Type:</jet-label>
                        <s4-input-select
                            class="w-full"
                            color="ssrGray"
                            :lists="text_type"
                            v-model="new_column.validate.type"
                        ></s4-input-select>
                        <!-- <select
                            :class="{ emptyValues: empty_fields.validate_type }"
                            class="form-select cursor-pointer font-semibold shadow-none text-gray-700 rounded-md border focus:ring-0 placeholder-gray-400 w-full border-gray-300 focus:border-ssr-blue3 focus:ring-1 focus:ring-ssr-blue3"
                            v-model="new_column.validate.type"
                        >
                            <option
                                :value="list.value"
                                v-for="(list, index) in text_type"
                                :key="index"
                            >
                                {{ list.text }}
                            </option>
                        </select> -->
                    </div>
                    <div class="col-span-2 mt-5">
                        <jet-label>Min character:</jet-label>
                        <s4-input
                            color="ssrGray"
                            class="w-full mb-1"
                            v-model.number="new_column.validate.min"
                        >
                        </s4-input>
                    </div>
                    <div class="col-span-2 mt-5">
                        <jet-label>Max Character:</jet-label>
                        <s4-input
                            color="ssrGray"
                            class="w-full mb-1"
                            v-model.number="new_column.validate.max"
                        >
                        </s4-input>
                    </div>
                    <div class="col-span-2 mt-5">
                        <label>
                            <s4-checkbox
                                class="cursor-pointer"
                                v-model="new_column.validate.type"
                            />
                            <span class="ml-2 text-sm text-gray-600 select-none"
                                >Validation Type</span
                            >
                        </label>
                    </div>
                    <div class="col-span-6 mt-3 mb-1">
                        <label>
                            <span class="text-sm text-gray-600 select-none"
                                >Error Message:</span
                            >
                            <s4-input
                                color="ssrGray"
                                v-model="new_column.validate.errorMsg"
                                placeholder="Error Message...  "
                                class="w-full"
                            />
                        </label>
                    </div>
                    <!-- end of textbox -->
                </template>
                <!-- buttons -->
                <div class="col-span-3" v-if="new_column.type != 2">
                    <s4-button
                        v-if="editChoice == false"
                        :theme="'ssrAddColRow'"
                        @click="addToColumns"
                        class="p-1"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="w-4 h-4 mr-1 self-center"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>
                        <!-- <font-awesome-icon :icon="faIcon.plus" /> -->
                        Add to Choice List
                    </s4-button>
                    <s4-button
                        :theme="'ssrAddColRow'"
                        v-if="editChoice == true"
                        @click="updateForm"
                        class="rounded-none ml-1 pt-1 pb-1 text-sm pl-3 pr-3"
                    >
                        <!-- <font-awesome-icon :icon="faIcon.edit" /> -->
                        Update
                    </s4-button>

                    <s4-button
                        :theme="'ssrAddColRow'"
                        v-if="editChoice == true"
                        @click="cancelAction"
                        class="rounded-none ml-1 pt-1 pb-1 text-sm pl-3 pr-3"
                    >
                        <font-awesome-icon />
                        Cancel
                    </s4-button>
                </div>
                <!-- end of buttons -->
            </div>
            <!-- choice list -->
            <div class="col-span-12 mt-5 pt-3 mb-5" v-if="new_column.type != 2">
                <jet-label class="font-bold">Choice Lists:</jet-label>
                <template
                    v-for="(col, col_index) in add_edit_form.items[modal.index][
                        'column'
                    ]"
                    :key="col_index"
                >
                    <div class="flex mt-1">
                        <div
                            class="w-full border border-gray-200 rounded-lg"
                            style="height: 2.5rem"
                        >
                            <jet-label
                                v-html="col.name"
                                class="pt-2 pl-3"
                            ></jet-label>
                        </div>
                        <ButtonIconsVue
                            @click="edit_column(col, col_index)"
                            icon="setting"
                        />
                        <!-- <s4-button
                            color="ssrOrangeIcon"
                            class="ml-2"
                            @click="edit_column(col, col_index)"
                        >
                            <font-awesome-icon
                                class="fa-w-20 fa-h-20"
                                :icon="faIcon.edit"
                            />
                        </s4-button> -->
                        <ButtonIconsVue
                            icon="delete"
                            @click="remove_column(col_index)"
                        />
                        <!-- <s4-button
                            color="ssrRedIcon"
                            class="ml-1"
                            @click="remove_column(col_index)"
                        >
                            <font-awesome-icon
                                class="fa-w-20 fa-h-20"
                                :icon="faIcon.trash"
                            />
                        </s4-button> -->
                    </div>
                </template>
            </div>
            <!-- end of choice list -->
        </template>
        <template #footer>
            <s4-button :theme="'ssrOrange'" class="mr-3" @click="close()">
                Close
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>

<style scoped>
.ssrBT {
    border: none;
    border-radius: 50%;
    width: 2.65rem;
    /* width:2rem;
    height:2rem; */
}

.ssrBT:hover {
    border: 1px solid;
    border-radius: 50%;
}

.llist {
    background: #759fd110;
}

.emptyValues {
    border-color: #f87171;
}
</style>
