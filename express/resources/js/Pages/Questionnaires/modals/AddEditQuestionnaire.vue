<script setup>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import S4Button from "@/S4/Button.vue";
import S4Input from "@/S4/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import S4Loading from "@/S4/Loading.vue";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faSpinner } from "@fortawesome/free-solid-svg-icons";
import { useForm, usePage } from "@inertiajs/vue3";
import { ref, onBeforeMount, watch } from "vue";
import helpers from "@/helpers.js";
import IconsVue from "../../../S4/Icons.vue";

const { assign, toast } = helpers();

const props = defineProps({
    modal: {
        type: Object,
        default: {
            add_edit: false,
            method: "add",
            data: null,
        },
    },
    project: {
        type: Object,
    },
});

const faIcon = ref({
    spinner: faSpinner,
});

const form = useForm(
    {
        name: "",
        details: "",
        code: "",
        setup: {
            design: usePage().props.themes[0].setup.design,
            design_id: usePage().props.themes[0].setup.design_id,
            random: 0,
            variable: "q",
            variable_start: 1,
            hidePrevBtn: 1,
            showProgressBar: 1, // not found in ide
            display_logo: 0,
            theme: 0,
            display_asterisk: 1,
            messages: {
                loading: "Loading...",
                saving: "Saving data...",
                nextBtn: "NEXT",
                prevBtn: "PREVIOUS",
                finalBtn: "CONTINUE", // not found in ide
            },
            footer: {
                //footer not found in ide
                enable: 1,
                logo: "",
            },
            customCSS: "",
            customJS: "",
            modals: {
                // not found in ide
                invalidProject: {
                    title: "Application Error",
                    content: "Invalid Project.",
                },
                surveyClosed: {
                    title: "Application Error",
                    content: "This survey is not yet available.",
                },
                noIdAssigned: {
                    title: "Application Error",
                    content: "No ID assigned.",
                },
                connectionLost: {
                    title: "Application Error",
                    content:
                        "Unable to connect with server. Please check your internet connection.",
                },
            },
            redirects: {
                // not found in ide
                complete: {
                    content: "Preparing the next part...",
                    url: "https://www.splitsecondresearch.com/?status=1",
                    duration: 0,
                    passExternalVars: 1,
                    passInternalVars: 1,
                },
                screenout: {
                    content: "Prease wait while we load the next step.",
                    url: "https://www.splitsecondresearch.com/?status=2",
                    duration: 0,
                    passExternalVars: 1,
                    passInternalVars: 1,
                },
                qualityControl: {
                    content: "Prease wait while we load the next step.",
                    url: "https://www.splitsecondresearch.com/?status=3",
                    duration: 0,
                    passExternalVars: 1,
                    passInternalVars: 1,
                },
            },
        },
    },
    {
        resetOnSuccess: true,
    }
);

const colors = ref(null);
const loading = ref(false);
const messageLoading = ref(null);
const copyData = useForm({
    copyCodeTo: null, //Where qnr will be copied into what project
    codeToCopy: null, //Code to copy
});

const initialized = () => {
    copyData.copyCodeTo = assign(usePage().props.project.code);
};

const emit = defineEmits(["close"]);

const close = () => {
    form.reset("name");
    form.reset("details");
    form.reset("code");
    emit("close");
    loading.vallue = false;
};

onBeforeMount(() => {
    initialized();
});

watch(
    () => props.modal,
    (modal) => {
        if (modal.method == "edit" && modal.add_edit == true) {
            form.name = assign(modal.data.name);
            form.details = assign(modal.data.details);
            form.code = assign(modal.data.code);
        }
        colors.value = modal.method === "delete" ? "red" : "orange"; //change color button
    },
    { deep: true }
);

const AddEditQuestonnaire = () => {
    loading.value = true;
    if (props.modal.method == "delete") {
        messageLoading.value = "Deleting Questionnaire";

        // this.form.processing = true;
        form.delete(route("questionnaires.destroy", props.modal.data.code), {
            preserveScroll: true,
            onSuccess: () => {
                toast.warning(`Questionnaire successfully deleted`, {
                    timeout: 2000,
                });
                close();
                // this.form.processing = false;
            },
            onError: () => {
                //code
                this.loading = false;
            },
            onFinish: () => {
                //code
            },
        });
    } else if (props.modal.method == "copy") {
        var params = [
            usePage().props.project.code,
            props.modal.data.code,
        ];
        copyData.codeToCopy = props.modal.data.code;
        copyData.post(route("questionnaires.copy", params), {
            preserveScroll: true,
            onSuccess: () => {
                toast.warning("Questionnaire copied successfully", {
                    timeout: 2000,
                });
                close();
            },
            onError: () => {},
            onFinishL: () => {},
        });
    } else {
        messageLoading.value =
            props.modal.method == "add"
                ? "Adding Questionnaire"
                : "Updating Questionnaire";
        var web_route =
            props.modal.method == "add"
                ? "questionnaires.store"
                : "questionnaires.update";
        form.post(route(web_route, usePage().props.project.code), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    `Questionnaire successfully ${
                        props.modal.method == "add" ? "added" : "updated"
                    }`,
                    {
                        timeout: 2000,
                    }
                );
                close();
            },
            onError: () => {
                //code
                loading.value = false;
            },
            onFinish: () => {
                //code
            },
        });
    }
};
</script>
<template>
    <jet-dialog-modal :show="modal.add_edit" @close="close()">
        <template #title>
            <s4-loading
                v-show="loading.value"
                :label="messageLoading"
            ></s4-loading>
            <div
                class="mx-auto flex items-center mt-2 bg-slate-100 rounded-l-full rounded-r-lg"
            >
                <img
                    :src="$page.props.auth.user.profile_photo_url"
                    :alt="$page.props.auth.user.name"
                    class="mx-0 relative w-12 h-12 rounded-full object-cover border"
                />
                <div class="ml-4 leading-tight">
                    <div>{{ $page.props.auth.user.name }}</div>
                    <div class="text-gray-700 text-sm">
                        {{ $page.props.auth.user.email }}
                    </div>
                </div>
            </div>
        </template>
        <template #content>
            <div
                class="col-span-6 text-lg md:container md:mx-auto border mx-2 rounded-lg mt-8 bg-slate-50 text-justify px-4 py-8"
                v-if="modal.method == 'delete'"
            >
                <span class="text-red-500 inline-block mr-2"
                    ><IconsVue icon="delete" /></span
                >Are you sure you want to delete this
                <span class="text-red-500 font-bold">{{
                    modal.data.name
                }}</span>
                questionnaire?
            </div>
            <!-- <div class="col-span-6 text-lg font-semibold px-14" v-if="modal.method == 'delete'">
                    Are you sure you want to delete this <span class="text-ssr-blue2">{{modal.data.name}}</span> questionnaire?
            </div> -->
            <div class="col-span-6 mx-auto" v-else-if="modal.method == 'copy'">
                <!-- <div class="col-span-12 mx-auto"> -->
                <select
                    v-model="copyData.copyCodeTo"
                    class="w-full truncate form-select cursor-pointer text-gray-500 rounded-md border-1 border-gray-300 focus:border-ssr-blue3 focus:ring-1 focus:ring-ssr-blue3"
                >
                    <option
                        v-for="(proj, index) in $page.props.projects"
                        :key="index"
                        :value="proj.code"
                    >
                        {{ proj.name }}
                    </option>
                </select>
                <!-- </div> -->
            </div>
            <div class="grid grid-cols-6 gap-1 px-14" v-else>
                <div class="col-span-6 mx-auto">
                    <span
                        class="text-lg font-semibold"
                        v-if="modal.method == 'add'"
                        >Add Questionnaire</span
                    >
                    <span class="text-lg font-semibold" v-else
                        >Update Questionnaire</span
                    >
                </div>
                <div class="col-span-6">
                    <s4-input
                        theme="theme2"
                        label="Questionnaire Name"
                        v-model="form.name"
                    ></s4-input>
                    <jet-input-error
                        :message="form.errors.name"
                        color="ssrGray"
                    />
                    <s4-input
                        theme="theme2"
                        label="Questionnaire Details"
                        v-model="form.details"
                    ></s4-input>
                    <jet-input-error
                        :message="form.errors.details"
                        color="ssrGray"
                    />
                    <!-- <jet-label value="Questionnaire Name" />
                    <s4-input type="text" color="ssrGray" placeholder="Input Questionnaire Name" class="mt-1 block w-full"
                        v-model="form.name" />
                    <jet-input-error :message="form.errors.name" />
                    <jet-label value="Questionnaire Details" class="mt-5" />
                    <s4-input type="text" color="ssrGray" placeholder="Input Questionnaire Details" class="mt-1 block w-full" 
                        v-model="form.details" /> -->
                </div>
            </div>
            <br />
        </template>
        <template #footer>
            <s4-button :theme="'ssrCancel'" class="mr-1" @click="close()">
                Cancel
            </s4-button>
            <s4-button
                :theme="'ssrOrange'"
                :color="colors"
                :disabled="form.processing"
                @click="AddEditQuestonnaire()"
            >
                <template v-if="form.processing">
                    <span
                        ><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span
                    >
                </template>
                <template v-else-if="modal.method == 'edit'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.edit" />-->
                        Update
                    </span>
                </template>
                <template v-else-if="modal.method == 'delete'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.trash" />-->
                        Delete
                    </span>
                </template>
                <template v-else-if="modal.method == 'copy'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.copy" />-->
                        Copy
                    </span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.save" /> -->
                        Save
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>
<style scoped></style>
