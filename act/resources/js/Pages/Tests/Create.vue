<template>
  <Layout :title="action === 'create' ? 'New Trial' : 'Update Trial'">
    <template #header>
      <div class="inline-flex items-center">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="w-auto h-6"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
          /></svg
        >&nbsp; {{ project.name }} > {{ survey.name }} >
        <span v-if="action === 'create'">&nbsp;Create trial</span
        ><span v-else>&nbsp;Update Trial</span>
      </div>
    </template>
    <div>
      <div class="px-2 mx-auto max-w-8xl">
        <div
          class="flex justify-between px-2 py-2 mb-2 bg-white shadow-md rounded-xl"
        >
          <div class="inline-flex items-center">
            <Link
              :href="route('tests.index', survey)"
              class="inline-flex items-center text-sm text-sky-600"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-5"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                  clip-rule="evenodd"
                />
              </svg>
              Back
            </Link>
          </div>
          <div class="inline-flex items-center">
            <SmallButtonVue class="mr-1" @click="trialAction">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-auto h-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"
                /></svg
              >&nbsp;save
            </SmallButtonVue>
            <a
              :href="
                app.engine_preview +
                'survey_id=' +
                survey.id +
                '&preview=1&id=test'
              "
              target="_blank"
              class="inline-flex items-center px-2 py-1 text-xs font-medium tracking-wide text-gray-700 uppercase transition bg-white border border-gray-300 rounded-md shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:text-gray-800 active:bg-gray-50 disabled:opacity-25"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-auto h-4"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"
                />
                <path
                  d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"
                />
              </svg>
              Preview
            </a>
            <template v-if="navigator?.hasPrevious">
              <Link :href="navigator.previous" class="ml-1 mr-1">
                <SmallButtonVue>
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-auto h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M15 19l-7-7 7-7"
                    />
                  </svg>
                  Previous Trial
                </SmallButtonVue>
              </Link>
            </template>
            <template v-if="navigator?.hasNext">
              <Link :href="navigator.next" class="ml-1">
                <SmallButtonVue>
                  Next trial
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-auto h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M9 5l7 7-7 7"
                    />
                  </svg>
                </SmallButtonVue>
              </Link>
            </template>
          </div>
        </div>
        <div class="pt-1 pb-4 bg-white shadow rounded-xl">
          <Tab :tabs="tab_data" @selected="selected_tab">
            <CustomForm @submitted="createTrial">
              <template #form>
                <div v-show="active_tab === 'settings'">
                  <TestSettings :survey="survey" />
                </div>
                <div v-show="active_tab === 'primes'">
                  <TestPrimes />
                </div>
                <div v-show="active_tab === 'targets'">
                  <TestTargets />
                </div>
              </template>
            </CustomForm>
          </Tab>
        </div>
      </div>
    </div>
  </Layout>
</template>
<script setup>
import Layout from "@/Layouts/Layout.vue";
import Tab from "@/Components/Tab.vue";
import CustomForm from "@/Components/Form2.vue";
import TestSettings from "./TestSettings.vue";
import TestPrimes from "./TestPrimes.vue";
import TestTargets from "./TestTargets.vue";
import SmallButtonVue from "../../Components/SmallButton.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, provide, watch, onBeforeMount } from "vue";
import Helpers from "../../Mixins/Helpers";
import isEmpty from "lodash/isEmpty";

const props = defineProps([
  "survey",
  "app",
  "group",
  "project",
  "action",
  "test",
  "navigator",
]);
console.log(props.survey);

const { toast, assign } = Helpers();

const form = useForm({
  config: {
    title: "",
    main_trial: true,
    random: true,
    prime_header: "",
    main_header: "",
    empty_selection: "Please select at least 1 item",
    main_header_timeout: 3000,
    type: null,
    single_select: false,
    limit: 0,
    auto_next: false,
    display_by: 1,
    random_prime: false,
    random_prime_subsets: false,
    prime_as_next: true,
    bottom_text:
      "<em>Press the text in the centre to proceed to the next trial</em>",
    conditions: [],
  },
  primes: [],
  targets: [],
});

const active_tab = ref("settings");
const enableCK = ref(
  !localStorage.getItem("ck") ? true : localStorage.getItem("ck") === "true"
);

provide("form", form);
provide("ck", enableCK);

watch(enableCK, (newval) => {
  localStorage.setItem("ck", newval);
});

const tab_data = ref([
  {
    text: "settings",
    active: active_tab.value === "settings",
    key: "settings",
    disabled: false,
  },
  {
    text: "primes",
    active: active_tab.value === "primes",
    key: "primes",
    disabled: false,
  },
  {
    text: "targets",
    active: active_tab.value === "targets",
    key: "targets",
    disabled: false,
  },
]);

onBeforeMount(() => {
  if (props.action === "update") {
    form.config = assign(props.test.config);
    form.primes = assign(props.test.primes);
    form.targets = assign(props.test.targets);
    if (typeof form.config.random_prime_subsets === "undefined") {
      form.config.random_prime_subsets = false;
    }
    if (typeof form.config.conditions === "undefined") {
      form.config.conditions = [];
    }
  }
});

watch(form, (newVal) => {
  tab_data.value[1].disabled = !isEmpty(newVal.config.title);
  tab_data.value[2].disabled = !isEmpty(newVal.config.title);
});

const selected_tab = (tab_key) => {
  return (active_tab.value = tab_key);
};

const trialAction = () => {
  if (props.action === "create") {
    createTrial();
  } else {
    updateTrial();
  }
};

const createTrial = () => {
  if (form.targets.length == 0 || form.primes.length == 0) {
    form.config.type = "descriptive";
  } else {
    form.config.type = "trial";
  }
  form.post(route("tests.store", props.survey), {
    preserveScroll: true,
    errorBag: "createTrial",
    onSuccess: () => {
      toast.success("Trial successfully created");
      console.log(props.survey);
    },
  });
};

const updateTrial = () => {
  if (form.targets.length == 0 || form.primes.length == 0) {
    form.config.type = "descriptive";
  } else {
    form.config.type = "trial";
  }
  form.put(route("tests.update", [props.survey, props.test]), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.success("Trial successfully updated");
    },
  });
};
</script>
