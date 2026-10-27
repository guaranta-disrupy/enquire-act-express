<script setup>
import { appBase } from "@/utils/basePath";
import { useTestsStore } from "@/stores/tests";

import Tab from "@/Components/Tab.vue";
import CustomForm from "@/Components/Form2.vue";
import TestSettings from "./TestSettings.vue";
import TestPrimes from "./TestPrimes.vue";
import TestTargets from "./TestTargets.vue";
import TestInstruction from "./TestInstruction.vue";
import TestAdvance from "./TestAdvance.vue";
import WarningModal from "@/Pages/Engine/editor/WarningModal.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import Chatbot from "@/S4/AI/Chatbot.vue";
import {
    ref,
    computed,
    watch,
    onBeforeMount,
    provide,
    onMounted,
    onBeforeUnmount,
} from "vue";

import Helpers from "@/Mixins/Helpers";

import {
    buildImageManifestFromTests,
    warmCacheSession,
    decodeImagesInBackground,
} from "@/utils/cacheWarmup";

// const pagedata = usePage().props;
// const tests = pagedata?.tests || [];
// const primes = tests.length > 0 ? tests[0].primes : [];
// const targets = tests.length > 0 ? tests[0].targets : [];

// const primes = pagedata.tests[0].primes;
// const targets = pagedata.tests[0].targets;
const userPermissions = usePage().props;
console.log("userPermissions", userPermissions);

const InstructionData = ref({});
const dataStore = useTestsStore();
const app_url = ref(import.meta.env.VITE_APP_URL);
const props = defineProps([
    "survey",
    "action",
    "tests",
    "test",
    "userPermissions",
    "variables",
    "project",
]);
console.log("props", props);
// console.log("project_id", props.project_id);
const isSyncingFromProps = ref(false);

if (props.tests) {
    // Handle both single test objects and arrays
    const testsData = Array.isArray(props.tests) ? props.tests : [props.tests];

    // Ensure testid and surveyID are set for saving
    if (props.tests.id) {
        dataStore.testid = props.tests.id;
    }
    if (props.survey?.id) {
        dataStore.surveyID = props.survey.id;
    }
}
const tests = dataStore.tests;
const targets = tests.targets;
const primes = tests.primes;
const config = tests.config;

const iframeOnMountedId = ref(null);

//chatbot state
const isChatOpen = ref(false);
const selectedPlatform = ref('act');

// Simple toggle for chatbot
const handleChatbotToggle = () => {
    isChatOpen.value = !isChatOpen.value;
};

const openChatbotList = (platform) => {
    selectedPlatform.value = platform || 'act';
};

const BUTTON_SIZE = 48;
const PADDING = 12;
const STORAGE_KEY = "chatbotPos";

const dragPos = ref({ x: 0, y: 0 });
const dragging = ref(false);

const pointerId = ref(null);
const startPointer = ref({ x: 0, y: 0 });
const startPos = ref({ x: 0, y: 0 });
const movedEnough = ref(false);
const rafId = ref(null);

const clampPos = (x, y) => {
    const maxX = window.innerWidth - BUTTON_SIZE - PADDING;
    const maxY = window.innerHeight - BUTTON_SIZE - PADDING;
    return {
        x: Math.min(Math.max(PADDING, x), Math.max(PADDING, maxX)),
        y: Math.min(Math.max(PADDING, y), Math.max(PADDING, maxY)),
    };
};

const loadPos = () => {
    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || "null");
        if (saved && typeof saved.x === "number" && typeof saved.y === "number") {
            dragPos.value = clampPos(saved.x, saved.y);
            return;
        }
    } catch (_) {}

    // default bottom-right
    dragPos.value = clampPos(
        window.innerWidth - BUTTON_SIZE - 32,
        window.innerHeight - BUTTON_SIZE - 32
    );
};

const savePos = () => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(dragPos.value));
};

const snapToEdge = () => {
    // optional: magnet to left/right edge
    const centerX = dragPos.value.x + BUTTON_SIZE / 2;
    const snapLeft = PADDING;
    const snapRight = window.innerWidth - BUTTON_SIZE - PADDING;

    const nextX = centerX < window.innerWidth / 2 ? snapLeft : snapRight;
    dragPos.value = clampPos(nextX, dragPos.value.y);
    savePos();
};

const onPointerMove = (e) => {
    if (!dragging.value || e.pointerId !== pointerId.value) return;

    const dx = e.clientX - startPointer.value.x;
    const dy = e.clientY - startPointer.value.y;

    // 6px threshold before we consider it a drag (prevents jitter)
    if (!movedEnough.value && (Math.abs(dx) > 6 || Math.abs(dy) > 6)) {
        movedEnough.value = true;
    }

    // rAF for smoother dragging
    if (rafId.value) cancelAnimationFrame(rafId.value);
    rafId.value = requestAnimationFrame(() => {
        const next = clampPos(startPos.value.x + dx, startPos.value.y + dy);
        dragPos.value = next;
    });
};

const stopPointerDrag = (e) => {
    if (e.pointerId !== pointerId.value) return;

    dragging.value = false;
    pointerId.value = null;

    window.removeEventListener("pointermove", onPointerMove);
    window.removeEventListener("pointerup", stopPointerDrag);
    window.removeEventListener("pointercancel", stopPointerDrag);

    if (movedEnough.value) {
        // save EXACT position, do not snap
        dragPos.value = clampPos(dragPos.value.x, dragPos.value.y);
        savePos();

        setTimeout(() => (movedEnough.value = false), 0);
    } else {
        movedEnough.value = false;
        handleChatbotToggle();
    }
};

const startPointerDrag = (e) => {
    // Only left mouse button for mouse pointers
    if (e.pointerType === "mouse" && e.button !== 0) return;

    pointerId.value = e.pointerId;
    dragging.value = true;
    movedEnough.value = false;

    startPointer.value = { x: e.clientX, y: e.clientY };
    startPos.value = { x: dragPos.value.x, y: dragPos.value.y };

    // Capture pointer so we keep receiving moves even if cursor leaves button
    e.currentTarget?.setPointerCapture?.(e.pointerId);

    window.addEventListener("pointermove", onPointerMove, { passive: true });
    window.addEventListener("pointerup", stopPointerDrag, { passive: true });
    window.addEventListener("pointercancel", stopPointerDrag, { passive: true });
};

const onResizeClampOnly = () => {
    // IMPORTANT: clamp current position, do NOT reset to default
    dragPos.value = clampPos(dragPos.value.x, dragPos.value.y);
    savePos();
};

onMounted(() => {
    loadPos();
    window.addEventListener("resize", onResizeClampOnly);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", onResizeClampOnly);
    window.removeEventListener("pointermove", onPointerMove);
    window.removeEventListener("pointerup", stopPointerDrag);
    window.removeEventListener("pointercancel", stopPointerDrag);
    if (rafId.value) cancelAnimationFrame(rafId.value);
});


// Keep local tests in sync when parent props change (e.g., route update)
watch(
    () => props.tests,
    (newVal) => {
        isSyncingFromProps.value = true;
        dataStore.setTests(newVal);
        // dataStore.setVariables(props.variables);

        // Allow reactivity to flush before re-enabling change detection
        Promise.resolve().then(() => (isSyncingFromProps.value = false));
    },
    { deep: true, immediate: true }
);

// Mark data as dirty when the user edits Primes/Targets/Settings (mutating dataStore.tests)
// watch(
//     () => dataStore.tests,
//     () => {
//         if (!isSyncingFromProps.value) {
//             dataStore.isDirty = true;
//         }
//     },
//     { deep: true }
// );

const { toast, assign } = Helpers();

function getInitials(name) {
    if (!name) return "";
    const parts = name.trim().split(" ");
    const first = parts[0]?.[0] || "";
    const last = parts.length > 1 ? parts[parts.length - 1][0] : "";
    return (first + last).toUpperCase();
}

const active_tab = ref("design");
const tabComponent = ref(null);
const enableCK = ref(
    !localStorage.getItem("ck") ? true : localStorage.getItem("ck") === "true"
);

const showLiveEditor = ref(false);
const isEditorLoading = ref(true);
const tabResetTick = ref(0);

// Computed property for editor source URL
const editorSrc = computed(() => {
    return `${app_url.value}/surveys/${dataStore.surveyID}/editor?tid=${iframeOnMountedId.value}`;
});

// Helper: find test index by id from current survey tests list
const getTestIndexById = (testId) => {
    const testsArr = props.survey?.tests || [];
    const idx = testsArr.findIndex(
        (t) => t.id === testId || t?.config?.id === testId
    );
    return idx >= 0 ? idx : 0;
};

// Post a navigate message to the iframe without reloading it
const notifyIframeOfNavigation = (testId, trialIndex = 0) => {
    const editorIframeEl = document.getElementById("app1");
    if (!editorIframeEl) return;
    const testIndex = getTestIndexById(testId);
    try {
        editorIframeEl.contentWindow.postMessage(
            {
                type: "PARENT_NAVIGATE",
                testIndex,
                trialIndex,
            },
            "*"
        );
    } catch (_) {}
};

// Handle editor iframe load
const handleEditorLoad = () => {
    isEditorLoading.value = false;
    // On load, navigate to the currently selected test without reloading
    if (dataStore.testid) {
        notifyIframeOfNavigation(dataStore.testid, 0);
    }
};

// Watch for test ID changes and navigate the preview without reloading
watch(
    () => dataStore.testid,
    (newTestId) => {
        if (newTestId) {
            notifyIframeOfNavigation(newTestId, 0);
        }
    }
);

// Watch for changes in the data store's dirty state and notify the iframe
watch(
    () => dataStore.isDirty,
    (isDirty) => {
        const editorIframeEl = document.getElementById("app1");
        if (editorIframeEl && editorIframeEl.contentWindow) {
            editorIframeEl.contentWindow.postMessage(
                {
                    type: "DATA_DIRTY_STATE",
                    isDirty: isDirty,
                },
                "*"
            );
        }
    },
    { immediate: true }
);

// Check if current test is descriptive type
const isCurrentTestDescriptive = computed(() => {
    return dataStore.tests?.config?.type === "descriptive";
});

const tab_data = computed(() => [
    {
        text: "Instruction",
        active: active_tab.value === "instruction",
        key: "instruction",
        disabled: false, // Always enabled
        icon: `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list-minus-icon lucide-list-minus"><path d="M16 5H3"/><path d="M11 12H3"/><path d="M16 19H3"/><path d="M21 12h-6"/></svg>
                  `,
    },
    {
        text: `Primes (${dataStore.tests.primes.length})`,
        active: active_tab.value === "primes",
        key: "primes",
        disabled: isCurrentTestDescriptive.value, // Disable for descriptive tests
        icon: `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-equal-icon lucide-circle-equal"><path d="M7 10h10"/><path d="M7 14h10"/><circle cx="12" cy="12" r="10"/></svg>
                  `,
    },
    {
        text: `Targets (${dataStore.tests.targets.length})`,
        active: active_tab.value === "targets",
        key: "targets",
        disabled: isCurrentTestDescriptive.value, // Disable for descriptive tests
        icon: `<div class="grid grid-rows-2 grid-cols-2 h-8 w-8 -ml-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-small-icon lucide-circle-small"><circle cx="12" cy="12" r="6"/></svg><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-small-icon lucide-circle-small"><circle cx="12" cy="12" r="6"/></svg><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-small-icon lucide-circle-small"><circle cx="12" cy="12" r="6"/></svg><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-small-icon lucide-circle-small"><circle cx="12" cy="12" r="6"/></svg></div>
                  `,
    },
    {
        text: "Settings",
        active: active_tab.value === "settings",
        key: "settings",
        disabled: isCurrentTestDescriptive.value,
        icon: `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings2-icon lucide-settings-2"><path d="M14 17H5"/><path d="M19 7h-9"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                  `,
    },
    {
        text: "Custom",
        active: active_tab.value === "advance",
        key: "advance",
        disabled: false, // Always enabled
        icon: `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-code-corner-icon lucide-file-code-corner"><path d="M4 12.15V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2h-3.35"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m5 16-3 3 3 3"/><path d="m9 22 3-3-3-3"/></svg>
            `,
    },
    {
        text: "Full Preview",
        active: false,
        key: "preview",
        disabled: false, // Always enabled
        target: "_blank",
        href:
            appBase() +
            "/survey-test?preview=1&survey_id=" +
            dataStore.surveyID +
            "&id=test",
        icon: `<svg
                    width="30"
                    height="30"
                    viewBox="0 0 30 30"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M30 3.80658V26.2237C29.8062 26.8647 29.1638 28.1157 28.1438 27.9914C27.1238 27.8671 10.1688 27.9396 1.81875 27.9914C1.23124 27.9072 0.045 27.4203 0 26.146V3.86485C0.0625125 3.25617 0.64125 2.03108 2.00625 2H28.1438C28.7625 2.06476 30 2.51672 30 3.80658ZM27.9375 3.80658C28.1475 3.82212 28.1875 4.02026 28.1813 4.11738V7.10892H1.81875V4.03968C1.81875 3.80658 2.05624 3.78715 2.175 3.80658H27.9375ZM28.1813 8.9932V25.8546C28.1875 25.9582 28.1513 26.1654 27.9563 26.1654H1.9719C1.90838 26.1524 1.78125 26.0993 1.78125 25.9905C1.78125 25.8818 1.80626 14.6137 1.81875 8.9932H28.1813ZM11.7375 13.8302C11.8575 12.7734 12.85 12.7035 13.3313 12.8006L19.35 16.608C19.995 17.1519 19.6188 18.0131 19.35 18.3757L13.3313 22.222C12.3113 22.4396 11.8437 21.6522 11.7375 21.2313V13.8302ZM13.5563 19.8909V15.1511L17.2688 17.4822L13.5563 19.8909Z"
                        fill="currentColor"
                    />
                </svg>
            `,
    },
]);

const form = useForm({
    config: {
        title: "",
        main_trial: true,
        random: true,
        prime_header: "",
        main_header: "",
        trial_unselect: false,
        default_trial_unselect: false,
        trial_unselect_label: "none of these",
        empty_selection: "Please select at least 1 item",
        main_header_timeout: 3000,
        type: null,
        single_select: false,
        limit: 0,
        auto_next: false,
        display_by: 1,
        random_prime: false,
        random_prime_subsets: false,
        mobile_vertical_targets: false,
        prime_as_next: true,
        bottom_text:
            "<em>Press the text in the centre to proceed to the next trial</em> (Click the center, if none of these apply)",
        conditions: [],
        macros: [],
        target_delay: 1500,
        limit_reach: "You have reached the maximum selection limit",
        auto_next_when: 1,
    },
    primes: [],
    targets: [],
});

provide("form", form);
provide("ck", enableCK);

// watch(
//     () => props.tests,
//     (newTargets) => {
//         console.log("test changed:", newTargets);
//     },
//     { deep: true, immediate: true }
// );

onBeforeMount(() => {
    if (props.action === "update" && props.test) {
        form.config = assign(config);
        form.primes = assign(primes);
        form.targets = assign(targets);
        if (typeof form.config.random_prime_subsets === "undefined") {
            form.config.random_prime_subsets = false;
        }
        if (typeof form.config.conditions === "undefined") {
            form.config.conditions = [];
        }
        if (typeof form.config.macros === "undefined") {
            form.config.macros = [];
        }
    }
    iframeOnMountedId.value = props.tests.id;
});

// Removed iframe postMessage handling; using LiveEditor component directly

// onBeforeMount(() => {
//   if (props.action === "update") {
//     form.config = assign(props.test.config);
//     form.primes = assign(props.test.primes);
//     form.targets = assign(props.test.targets);
//     if (typeof form.config.random_prime_subsets === "undefined") {
//       form.config.random_prime_subsets = false;
//     }
//     if (typeof form.config.conditions === "undefined") {
//       form.config.conditions = [];
//     }
//     if (typeof form.config.macros === "undefined") {
//       form.config.macros = [];
//     }
//   }
// });

// watch(form, (newVal) => {
//   tab_data.value[1].disabled = !isEmpty(newVal.config.title);
//   tab_data.value[2].disabled = !isEmpty(newVal.config.title);
// });

const showUnsavedChangesModal = ref(false);

const onModalSave = async () => {
    // await dataStore.saveTest();
    const editorIframeEl = document.getElementById("app1");
    if (editorIframeEl && editorIframeEl.contentWindow) {
        editorIframeEl.contentWindow.postMessage(
            { type: "SAVE_TEST_STYLES" },
            "*"
        );
    }
    dataStore.setHasUnsavedChanges(false);
    showUnsavedChangesModal.value = false;
};

const onModalDiscard = () => {
    // dataStore.setTests(props.tests);
    const editorIframeEl = document.getElementById("app1");
    if (editorIframeEl && editorIframeEl.contentWindow) {
        editorIframeEl.contentWindow.postMessage(
            { type: "DISCARD_TEST_STYLES" },
            "*"
        );
    }
    dataStore.setHasUnsavedChanges(false);
    showUnsavedChangesModal.value = false;
};

const onModalCancel = () => {
    showUnsavedChangesModal.value = false;
};

const selected_tab = (tab_key) => {
    return (active_tab.value = tab_key);
};

// Function to close any open tab panel
const closeOpenPanel = () => {
    active_tab.value = null;
};

// Handle form submission from CustomForm component
const createTrial = async () => {
    // Determine if we're creating or updating based on action prop
    if (props.action === "create") {
        // For create, determine test type based on primes/targets
        if (form.targets.length === 0 || form.primes.length === 0) {
            form.config.type = "descriptive";
        } else {
            form.config.type = "trial";
        }
        
        form.post(route("tests.store", props.survey), {
            preserveScroll: true,
            errorBag: "createTrial",
            onSuccess: () => {
                toast.success("Trial successfully created");
                router.visit(route("survey2.index", props.survey));
            },
        });
    } else {
        // For update, use saveTest which handles the update logic
        await saveTest();
    }
};


const saveTest = async (testIndex = null, trialIndex = null) => {
    try {
        // If the data store is dirty, save the data and reload the preview
        if (dataStore.isDirty) {
            await dataStore.saveTest(); // This saves data and resets the isDirty flag
            toast.success("Trial data successfully updated");

            // Warm newly referenced assets before asking the preview to reload
            try {
                const urls = buildImageManifestFromTests(
                    dataStore.tests,
                    props.survey
                );
                await warmCacheSession(urls);
                decodeImagesInBackground(urls);
            } catch (_) {}

            // Reload the preview to reflect the data changes
            const editorIframeEl = document.getElementById("app1");
            if (editorIframeEl) {
                editorIframeEl.contentWindow.postMessage(
                    {
                        type: "RELOAD_PREVIEW",
                        testIndex: testIndex,
                        trialIndex: trialIndex,
                    },
                    "*"
                );
            }

            const url = route("survey2.update", [
                props.survey.id,
                dataStore.testid,
            ]);
            router.visit(url, {
                preserveState: true,
                preserveScroll: true,
                only: ["survey", "tests"],
            });
            dataStore.setTests(props.tests);
        } else {
            // If data is not dirty, it means only styles were saved.
            // The style saving happens inside the iframe, and it's already done.
            // So we just show a toast indicating styles were updated.
            // toast.success("Styles successfully updated");
        }
    } catch (error) {
        console.error("Save error:", error);
        toast.error("Failed to save changes");
    }
};

// Define message handler outside of lifecycle hooks so we can remove it later
const handleMessage = (event) => {
    if (event.origin !== window.location.origin) {
        return;
    }

    if (event.data.type === "EDITOR_READY") {
        // The editor iframe is ready, send the current dirty state
        const editorIframeEl = document.getElementById("app1");
        if (editorIframeEl && editorIframeEl.contentWindow) {
            editorIframeEl.contentWindow.postMessage(
                {
                    type: "DATA_DIRTY_STATE",
                    isDirty: dataStore.isDirty,
                },
                "*"
            );
        }
    }

    if (event.data.type === "SAVE_CHANGES") {
        let testIndex = event.data.testIndex;
        let trialIndex = event.data.trialIndex;
        saveTest(testIndex, trialIndex);
        // iframeOnMountedId.value = props.tests.id;
        // Close any open panel after saving
        closeOpenPanel();
        // Reset Tab active state
        tabResetTick.value++;
    }

    if (event.data.type === "UPDATE_TEST") {
        let testId = event.data.testId;
        // const url = route("survey2.update", [props.survey.id, testId]);

        // router.visit(url, {
        //     preserveState: true,
        //     preserveScroll: true,
        //     only: ["tests"],
        // });
        if (testId !== dataStore.testid) {
            const testObj = props.survey.tests.filter((t) => t.id === testId);
            dataStore.setTests(testObj);
        }
    }
    // Listen for style updates from AI execution (Chatbot)
    if (event.data.type === "STYLES_UPDATED") {
        // Forward the message to the editor iframe
        const editorIframeEl = document.getElementById("app1");
        if (editorIframeEl && editorIframeEl.contentWindow) {
            editorIframeEl.contentWindow.postMessage(
                {
                    type: "STYLES_UPDATED"
                },
                "*"
            );
        }
    }

    if (event.data.type === "TEST_DATA_UPDATED") {
        const testId = event.data.test_id;
        const surveyId = event.data.survey_id || props.survey?.id;

        // Get iframe element once
        const editorIframeEl = document.getElementById("app1");
        
        // Ensure preview stays visible immediately
        ensureIframeVisible();
        
        // Forward the message to the editor iframe
        if (editorIframeEl && editorIframeEl.contentWindow) {
            editorIframeEl.contentWindow.postMessage(
                {
                    type: "TEST_DATA_UPDATED",
                    test_id: testId,
                    survey_id: surveyId
                },
                "*"
            );
        }
        
        // If test data is already provided in the message, use it directly
        if (event.data.test_data) {
            // Only update if this is the currently selected test
            if (testId === dataStore.testid) {
                isSyncingFromProps.value = true;
                dataStore.setTests(event.data.test_data);
                Promise.resolve().then(() => (isSyncingFromProps.value = false));
                // Reload the preview to show updated data
                if (editorIframeEl && editorIframeEl.contentWindow) {
                    const testIndex = getTestIndexById(testId);
                    editorIframeEl.contentWindow.postMessage(
                        {
                            type: "RELOAD_PREVIEW",
                            testIndex: testIndex,
                            trialIndex: 0
                        },
                        "*"
                    );
                }
            }
        } else {
            // Otherwise, fetch fresh test data from the server
            fetch(`/api/surveys/${surveyId}/tests/${testId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch test data');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.test) {
                        // Only update if this is the currently selected test
                        if (testId === dataStore.testid) {
                            isSyncingFromProps.value = true;
                            dataStore.setTests(data.test);
                            Promise.resolve().then(() => (isSyncingFromProps.value = false));
                                                        // Ensure preview stays visible
                            ensureIframeVisible();
                            
                            // Reload the preview to show updated data
                            const editorIframeEl = document.getElementById("app1");
                            if (editorIframeEl && editorIframeEl.contentWindow) {
                                const testIndex = getTestIndexById(testId);
                                editorIframeEl.contentWindow.postMessage(
                                    {
                                        type: "RELOAD_PREVIEW",
                                        testIndex: testIndex,
                                        trialIndex: 0
                                    },
                                    "*"
                                );
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Failed to reload test data in sidebar:', error);
                });
        }
    }

    if (event.data.type === "CLOSE_OPEN_PANEL") {
        // Use the Tab component's proper closing mechanism
        if (active_tab.value && tabComponent.value) {
            // Access the Tab component's select method to trigger proper slide animation
            if (tabComponent.value.select) {
                tabComponent.value.select(active_tab.value);
            } else {
                // Fallback to immediate close if method not found
                closeOpenPanel();
            }
        } else {
            // No active tab or component not available
            closeOpenPanel();
        }
    }

    if (event.data.type === "DISCARD_DATA_CHANGES") {
        dataStore.setTests(props.tests);
        dataStore.setHasUnsavedChanges(false);
        dataStore.isDirty = false;
        closeOpenPanel();
        tabResetTick.value++;
    }

    if (event.data.type === "HAS_UNSAVED_CHANGES") {
        dataStore.setHasUnsavedChanges(event.data.status);
    }
};

onMounted(() => {
    window.addEventListener("message", handleMessage);
});

onBeforeUnmount(() => {
    window.removeEventListener("message", handleMessage);
});
</script>

<template>
    <Head :title="survey.name" />
    <div class="bg-slate-200 min-h-[100vh] text-black">
        <div class="flex flex-col max-w-full text-[#323232] mx-auto">
            <div class="sticky top-0 flex flex-col bg-slate-200">
                <div
                    class="flex items-center justify-between h-[70px] py-2 px-4 bg-white z-20 shadow-md border border-200"
                >
                    <div class="flex items-center gap-3 mx-1">
                        <div
                            class="active:scale-[0.85] transition-all duration-200"
                            @click="
                                dataStore.hasUnsavedChanges
                                    ? (showUnsavedChangesModal = true)
                                    : null
                            "
                        >
                            <Link
                                :href="route('survey2.index', survey)"
                                :class="{
                                    'pointer-events-none':
                                        dataStore.hasUnsavedChanges,
                                }"
                            >
                                <svg
                                    data-v-8cc99868=""
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="w-[60px] hover:stroke-[#257556] transition-colors duration-200"
                                >
                                    <path
                                        data-v-8cc99868=""
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m11.25 9-3 3m0 0 3 3m-3-3h7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    ></path>
                                </svg>

                                <!-- <svg
                                data-v-8cc99868=""
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="w-[60px] hover:stroke-[#257556] transition-colors duration-200 active:scale-[0.95] transition-all duration-200"
                            >
                                <path
                                    data-v-8cc99868=""
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m11.25 9-3 3m0 0 3 3m-3-3h7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                ></path>
                            </svg> -->

                                <!-- <svg data-v-8cc99868="" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-[60px] hover:stroke-[#257556] transition-colors duration-200"><path data-v-8cc99868="" stroke-linecap="round" stroke-linejoin="round" d="m11.25 9-3 3m0 0 3 3m-3-3h7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg> -->
                            </Link>
                        </div>
                        <div class="text-lg flex items-center ml-2">
                            <span class="text-[24px] font-bold text-[#323232]">
                                {{ survey.name }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="w-[50px] h-[50px] flex items-center justify-center rounded-full bg-[#25755630] text-[#257556] text-[20px] cursor-default"
                        :title="$page.props.auth.user.name"
                    >
                        {{ getInitials($page.props.auth.user.name) }}
                    </div>
                </div>

                <!-- <div
            class="flex items-center justify-end gap-2 w-full top-[4.50rem] h-[50px] absolute shadow-md p-2"
          >
            <div class="flex gap-2 item-center">
               <button
                type="button"
                @click="saveTest()"
                class="px-4 bg-[#257556] text-white font-bold rounded-full text-[20px] hover:bg-[#1d5e45] w-[110px] h-[44px] justify-center items-center flex"
              >
              
                Save
              </button> -->
                <!-- <Button
                class="px-4 e bg-[#257556] text-white font-bold rounded-full text-[20px] hover:bg-[#1d5e45] w-[110px] h-[44px] justify-center items-center flex"
                shadow="xl"
                @click="trialAction"
                v-if="userPermissions.canUpdateSurvey"
              >
                <svg
                  data-v-a46b6639=""
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="currentColor"
                  class="w-6 h-6 mr-1"
                >
                  <path
                    data-v-a46b6639=""
                    fill-rule="evenodd"
                    d="M10.5 3.75a6 6 0 00-5.98 6.496A5.25 5.25 0 006.75 20.25H18a4.5 4.5 0 002.206-8.423 3.75 3.75 0 00-4.133-4.303A6.001 6.001 0 0010.5 3.75zm2.03 5.47a.75.75 0 00-1.06 0l-3 3a.75.75 0 101.06 1.06l1.72-1.72v4.94a.75.75 0 001.5 0v-4.94l1.72 1.72a.75.75 0 101.06-1.06l-3-3z"
                    clip-rule="evenodd"
                  ></path>
                </svg>
                Save
              </Button> -->
                <!-- <JetButton variant="default" size="sm" class="px-4 uppercase" shadow="xl">
                              preview
                          </JetButton> 
            </div>
          </div> -->
            </div>

            <button
                @click="saveTest"
                class="save-changes-button"
                :style="{ display: 'hidden' }"
            ></button>

            <div class="flex h-[calc(99.5vh-70px)] w-full overflow-y-hidden">
                <div class="w-[6rem]">
                    <Tab
                        ref="tabComponent"
                        :tabs="tab_data"
                        :resetSignal="tabResetTick"
                        @selected="selected_tab"
                    >
                        <CustomForm @submitted="createTrial">
                            <template #form>
                                <Transition name="content-fade" mode="out-in">
                                    <div
                                        v-if="
                                            active_tab === 'primes' &&
                                            dataStore.tests?.config?.type ===
                                                'trial'
                                        "
                                        key="primes"
                                    >
                                        <TestPrimes
                                            v-model:tests="dataStore.tests"
                                        />
                                    </div>
                                </Transition>

                                <Transition name="content-fade" mode="out-in">
                                    <div
                                        v-if="
                                            active_tab === 'targets' &&
                                            dataStore.tests?.config?.type ===
                                                'trial'
                                        "
                                        key="targets"
                                    >
                                        <TestTargets
                                            v-model:tests="dataStore.tests"
                                        />
                                    </div>
                                </Transition>

                                <Transition name="content-fade" mode="out-in">
                                    <div
                                        v-if="active_tab === 'instruction'"
                                        key="instruction"
                                    >
                                        <TestInstruction
                                            :project="props.project"
                                            v-model:tests="dataStore.tests"
                                            :variables="props.variables"
                                            :config="InstructionData"
                                        />
                                    </div>
                                </Transition>

                                <Transition name="content-fade" mode="out-in">
                                    <div
                                        v-if="active_tab === 'settings'"
                                        key="settings"
                                    >
                                        <TestSettings
                                            :survey="survey"
                                            v-model:tests="dataStore.tests"
                                        />
                                    </div>
                                </Transition>

                                <Transition name="content-fade" mode="out-in">
                                    <div
                                        v-if="active_tab === 'advance'"
                                        key="advance"
                                    >
                                        <TestAdvance
                                            :survey="survey"
                                            v-model:tests="dataStore.tests"
                                        />
                                    </div>
                                </Transition>
                            </template>
                        </CustomForm>
                    </Tab>

                    <!-- Chatbot Start -->
                    <teleport to="body">
                        <div
                            class="z-[1000]"
                            :style="{
                                position: 'fixed',
                                left: dragPos.x + 'px',
                                top: dragPos.y + 'px',
                                touchAction: 'none',
                                userSelect: 'none'
                            }"
                        >
                            <button
                                @pointerdown="startPointerDrag"
                                :class="[
                                    'flex items-center justify-center w-12 h-12 rounded-full shadow-lg ring-2 ring-white select-none',
                                    dragging ? 'cursor-grabbing scale-[0.98]' : 'cursor-grab'
                                ]"
                                :style="isChatOpen ? 'background-color: #323232;' : 'background-color: #257556;'"
                                >
                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" class="text-white">
                                    <path fill="currentColor"
                                        d="m20.713 8.128l-.246.566a.506.506 0 0 1-.934 0l-.246-.566a4.36 4.36 0 0 0-2.22-2.25l-.759-.339a.53.53 0 0 1 0-.963l.717-.319a4.37 4.37 0 0 0 2.251-2.326l.253-.611a.506.506 0 0 1 .942 0l.253.61a4.37 4.37 0 0 0 2.25 2.327l.718.32a.53.53 0 0 1 0 .962l-.76.338a4.36 4.36 0 0 0-2.219 2.251M10 3h4v2h-4a6 6 0 0 0-6 6c0 3.61 2.462 5.966 8 8.48V17h2a6 6 0 0 0 6-6h2a8 8 0 0 1-8 8v3.5c-5-2-12-5-12-11.5a8 8 0 0 1 8-8" />
                                </svg>
                            </button>

                            <!-- keep Chatbot component next to the button so its position is relative to the teleported container -->
                            <Chatbot
                                :open="isChatOpen"
                                @update:open="handleChatbotToggle"
                                :selectedPlatform="selectedPlatform"
                                @open-chatbot-list="openChatbotList"
                            />
                        </div>
                    </teleport>
                    
                </div>
                <div class="flex-grow h-full w-full relative">
                    <!-- Loading indicator -->
                    <div
                        v-if="isEditorLoading"
                        class="absolute inset-0 flex items-center justify-center bg-gray-50 z-10"
                    >
                        <div class="flex flex-col items-center space-y-4">
                            <div class="loader-track">
                                <div class="loader-thumb"></div>
                            </div>
                            <p class="text-base text-xl mt-2">
                                Preparing assets...
                            </p>
                        </div>
                    </div>

                    <!-- Editor iframe with optimized loading -->
                    <iframe
                        id="app1"
                        :src="editorSrc"
                        class="h-full w-full transition-opacity duration-500"
                        :class="{
                            'opacity-0': isEditorLoading,
                            'opacity-100': !isEditorLoading,
                        }"
                        @load="handleEditorLoad"
                        loading="lazy"
                        allow="fullscreen"
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-presentation"
                    ></iframe>
                </div>
            </div>
        </div>
    </div>

    <WarningModal
        :is-open="showUnsavedChangesModal"
        @save="onModalSave"
        @discard="onModalDiscard"
        @cancel="onModalCancel"
    />
</template>

<style>
/* Remove any conflicting body styles for this component */

.left-slide-enter-active,
.left-slide-leave-active {
    position: absolute;
    z-index: 10;

    pointer-events: none;
}

.left-slide-enter-from {
    transform: translateX(-40%) scale(0.98);
    opacity: 0;
    filter: blur(6px);
}
.left-slide-enter-to {
    transform: translateX(0%) scale(1);
    opacity: 1;
    filter: blur(0);
}

.left-slide-leave-from {
    transform: translateX(0%) scale(1);
    opacity: 1;
    filter: blur(0);
}
.left-slide-leave-to {
    transform: translateX(-40%) scale(0.98);
    opacity: 0;
    filter: blur(6px);
}

/* Optimize iframe performance */
iframe {
    will-change: transform;
    transform: translateZ(0);
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
}

/* Smooth loading transitions */
.transition-opacity {
    transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.left-slide-leave-to {
    transform: translateX(-30%);
    opacity: 0;
}

/* Content fade transitions for smooth panel switching */
.content-fade-enter-active,
.content-fade-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.content-fade-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.content-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Inline loader: 300px track with a green bar moving left↔right */
.loader-track {
    position: relative;
    width: 300px;
    height: 2px;
    background: #e5e7eb; /* gray-200 */
    border-radius: 2px;
    overflow: hidden;
}

.loader-thumb {
    position: absolute;
    top: 0;
    left: 0;
    width: 90px;
    height: 100%;
    background: #08c6d4;
    border-radius: 2px;
    animation: loader-slide 1s ease-in-out infinite alternate;
    will-change: transform;
    transform: translateZ(0);
}

@keyframes loader-slide {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(210px);
    }
}
</style>
