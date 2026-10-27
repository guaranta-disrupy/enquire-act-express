<script setup>
import { BASE_PATH, appBase } from "@/utils/basePath";
import { useTemplateVariablesStore } from "@/stores/template_variables";
import JetButton from "@/Componentsv2/Button.vue";
import JetLabel from "@/Jetstream/Label.vue";
import CustomCard from "@/Components/Card.vue";
import SmallButton from "@/Components/SmallButton.vue";
import Confirm from "@/Components/Confirm.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetSecondaryButton from "@/Components/SecondaryButton.vue";
import Dropdown from "@/Jetstream/Dropdown.vue";
import DropdownLink from "@/Jetstream/DropdownLink.vue";
import SettingsForm from "./SettingsForm.vue";
import AdvanceForm from "./AdvanceForm.vue";
import VariableForm from "./VariableForm.vue";
import AddRoundForm from "./AddRoundForm.vue";
import Templates from "./Templates.vue";
import Tooltip from "./Tooltip.vue";
import SetupDialog from "./FirstSetup/SetupDialog.vue";
import Helpers from "@/Mixins/Helpers";
import ThemesForm from "./ThemeForm.vue";
import Chatbot from "@/S4/AI/Chatbot.vue";
// Helper: rewrite cross-origin <img src> to proxy when in editor
function proxiedHtml(html) {
    try {
        if (!html || typeof html !== "string") return html;
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const imgs = doc.querySelectorAll("img[src]");
        imgs.forEach((img) => {
            try {
                const u = new URL(
                    img.getAttribute("src"),
                    window.location.origin
                );
                if (u.origin !== window.location.origin) {
                    img.setAttribute(
                        "src",
                        `${BASE_PATH}/gallery-proxy?u=${encodeURIComponent(u.href)}`
                    );
                }
            } catch (_) {}
        });
        return doc.body.innerHTML;
    } catch (_) {
        return html;
    }
}

// Helpbot escondido até ter backend próprio; VITE_HELPBOT_ENABLED=true religa.
const helpbotEnabled = import.meta.env.VITE_HELPBOT_ENABLED === 'true';

const hasRenderableContent = (html) => {
    if (!html || typeof html !== "string") return false;
    const normalized = html.replace(/&nbsp;/gi, " ").trim();
    if (!normalized) return false;

    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const textContent =
            doc.body?.textContent?.replace(/\s+/g, "").length || 0;
        if (textContent > 0) {
            return true;
        }
        return Boolean(
            doc.body?.querySelector(
                "img, picture, video, svg, iframe, object, canvas"
            )
        );
    } catch (_) {
        return normalized.length > 0;
    }
};

import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref, onBeforeMount, onBeforeUnmount, provide, watch, nextTick } from "vue";
import { storeToRefs } from "pinia";
import { onMounted, computed } from "vue";
import TestSettings from "./TestSettings.vue";
import { getVariablesFromTests } from "@/utils/testVariableGetter";
import {
    buildImageManifestFromTests,
    warmCacheSession,
    decodeImagesInBackground,
    warmAndDecodeSession,
} from "@/utils/cacheWarmup";

const props = defineProps([
    "survey",
    "tests",
    "surveys",
    "project",
    "templatePrimes",
    "templateTargets",
    "userPermissions",
    "variables",
]);

const { assign, toast } = Helpers();

const form = useForm({
    config: {},
    config2: {},
});

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
const isUnmounting = ref(false);

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
    if (!dragging.value || e.pointerId !== pointerId.value || isUnmounting.value) return;

    const dx = e.clientX - startPointer.value.x;
    const dy = e.clientY - startPointer.value.y;

    // 6px threshold before we consider it a drag (prevents jitter)
    if (!movedEnough.value && (Math.abs(dx) > 6 || Math.abs(dy) > 6)) {
        movedEnough.value = true;
    }

    // rAF for smoother dragging
    if (rafId.value) cancelAnimationFrame(rafId.value);
    rafId.value = requestAnimationFrame(() => {
        if (isUnmounting.value) return; // Don't update if unmounting
        const next = clampPos(startPos.value.x + dx, startPos.value.y + dy);
        dragPos.value = next;
    });
};

const stopPointerDrag = (e) => {
    if (e.pointerId !== pointerId.value || isUnmounting.value) return;

    // Remove event listeners first
    window.removeEventListener("pointermove", onPointerMove);
    window.removeEventListener("pointerup", stopPointerDrag);
    window.removeEventListener("pointercancel", stopPointerDrag);

    // Use nextTick to ensure state updates happen after current render cycle
    nextTick(() => {
        if (isUnmounting.value) return; // Don't update if unmounting
        
        dragging.value = false;
        pointerId.value = null;

        if (movedEnough.value) {
            // save EXACT position, do not snap
            dragPos.value = clampPos(dragPos.value.x, dragPos.value.y);
            savePos();

            setTimeout(() => {
                if (!isUnmounting.value) {
                    movedEnough.value = false;
                }
            }, 0);
        } else {
            movedEnough.value = false;
            handleChatbotToggle();
        }
    });
};

const startPointerDrag = (e) => {
    // Only left mouse button for mouse pointers
    if (e.pointerType === "mouse" && e.button !== 0) return;

    // Prevent if already dragging or unmounting
    if (dragging.value || isUnmounting.value) return;

    // Use nextTick to ensure state updates happen after current render cycle
    nextTick(() => {
        if (isUnmounting.value) return; // Don't start drag if unmounting
        
        pointerId.value = e.pointerId;
        dragging.value = true;
        movedEnough.value = false;

        startPointer.value = { x: e.clientX, y: e.clientY };
        startPos.value = { x: dragPos.value.x, y: dragPos.value.y };

        // Capture pointer so we keep receiving moves even if cursor leaves button
        try {
            e.currentTarget?.setPointerCapture?.(e.pointerId);
        } catch (err) {
            // Silently fail if pointer capture is not available
        }

        window.addEventListener("pointermove", onPointerMove, { passive: true });
        window.addEventListener("pointerup", stopPointerDrag, { passive: true });
        window.addEventListener("pointercancel", stopPointerDrag, { passive: true });
    });
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
    // Set unmounting flag to prevent any async updates
    isUnmounting.value = true;
    
    // Cancel any pending animation frame
    if (rafId.value) {
        cancelAnimationFrame(rafId.value);
        rafId.value = null;
    }
    
    // Remove all event listeners
    window.removeEventListener("resize", onResizeClampOnly);
    window.removeEventListener("pointermove", onPointerMove);
    window.removeEventListener("pointerup", stopPointerDrag);
    window.removeEventListener("pointercancel", stopPointerDrag);
    
    // Reset drag state
    dragging.value = false;
    pointerId.value = null;
    movedEnough.value = false;
});

//makes the chatbot interface adjusts when the chat button is dragged
const chatbotAnchor = computed(() => {

  return {

    x: dragPos.value.x,
    y: dragPos.value.y,
    width: BUTTON_SIZE,
    height: BUTTON_SIZE,

    cx: dragPos.value.x + BUTTON_SIZE / 2,
    cy: dragPos.value.y + BUTTON_SIZE / 2,
    right: dragPos.value.x + BUTTON_SIZE,
    left: dragPos.value.x,
    top: dragPos.value.y,
    bottom: dragPos.value.y + BUTTON_SIZE,
  };
});

const selectedTest = ref(false);

const testTemplateVariables = ref(getVariablesFromTests(props.tests || []));

const matchedVariables = computed(() => {
    if (!Array.isArray(props.variables)) return [];
    const names = new Set(testTemplateVariables.value || []);
    return props.variables.filter(
        (variable) =>
            variable &&
            typeof variable === "object" &&
            variable.name &&
            names.has(variable.name)
    );
});

// const app_url = ref(import.meta.env.VITE_APP_URL);

const engine_url = ref(
    appBase() + "/survey-test?preview=1" + "&survey_id="
);

const deleteTrial = () => {
    form.delete(route("tests.destroy", [props.survey, selectedTest.value]), {
        preserveScroll: true,
        onSuccess: () => {
            selectedTest.value = false;
            toast.success("Round successfully deleted");
        },
    });
};

const templatesModal = ref(false);

const testToBeCopied = ref(false);
const confirmingTrialCopyTo = ref(false);

const copyTrial = (copy = true) => {
    form.get(
        route("tests.copy", [
            selectedSurvey.value,
            confirmingTrialCopyTo.value,
        ]),
        {
            preserveScroll: true,
            onSuccess: () => {
                testToBeCopied.value = false;
                toast.success("Round successfully copied");
            },
        }
    );
};

const handleButtonDelayChange = (val) => {
    props.survey.config.buttons.button_delay = val;
    // console.log(props.survey)
};

const formatTestUpdated = (test) => {
    if (!test) return "";

    // Prefer raw timestamp if available, fall back to readable string
    const updatedAt = test.updated_at || null;
    if (!updatedAt) {
        return test.updated_readable || "";
    }

    const updatedDate = new Date(updatedAt);
    if (Number.isNaN(updatedDate.getTime())) {
        return test.updated_readable || "";
    }

    const now = new Date();
    const diffMs = now.getTime() - updatedDate.getTime();
    const diffMinutes = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    // Very Recent (Minutes, Hours)
    if (diffMinutes < 1) {
        return "just now";
    }
    if (diffHours < 1) {
        return `${diffMinutes} minute${diffMinutes === 1 ? "" : "s"} ago`;
    }
    if (diffHours < 24) {
        return `${diffHours} hour${diffHours === 1 ? "" : "s"} ago`;
    }

    // Recent (Days up to ~1 week)
    if (diffDays === 1) {
        return "yesterday";
    }
    if (diffDays < 7) {
        return `${diffDays} days ago`;
    }

    // Past (More than 1 week/month) – Absolute date
    // Example: "October 7, 2025"
    return updatedDate.toLocaleDateString(undefined, {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};
onBeforeMount(() => {
    form.config = assign(props.survey.config);
    if (form.config == null) {
        form.config = {
            css: "",
            js: "",
            forward_response: false,
            response_var: "ast",
            swipe: false,
            left_key: "f",
            right_key: "j",
            click_instructions: "Click Yes or No.",
            fullscreen_text:
                "For the best experience, please enter fullscreen.",
            fullscreen_button: "Enter Fullscreen",
            // dummy_trial : false,
            swipe_instructions:
                " Or SWIPE <strong>LEFT</strong> or <strong>RIGHT</strong>",
            keypress_instructions:
                "Press <strong>F</strong> if you disagree or <strong>J</strong> if otherwise.",
            keypress_click: false,
            theme: 0,
            redirect: null,
            download_as_values: false,
            buttons: {
                next: "",
                final: "",
            },
            texts: {
                saving: "",
                redirecting: "",
            },
        };
    }
    if (form.config?.theme == undefined) {
        form.config.theme = 0;
    }
    if (form.config?.redirect == undefined) {
        form.config.redirect = null;
    }
    if (form.config?.download_as_values == undefined) {
        form.config.download_as_values = false;
    }
    if (form.config?.buttons == undefined) {
        form.config.buttons = {
            next: "",
            final: "",
        };
    }
    if (form.config?.texts == undefined) {
        form.config.texts = {
            saving: "",
            redirecting: "",
        };
    }
    form.config2 = assign(props.project.config);

    if (form.config?.response_type == undefined) {
        form.config.response_type = "selection";
    }

    selectedSurvey.value = props.survey.id;
});

provide("form", form);

const saveSettings = () => {
    form.put(route("survey2.save_settings", [props.survey, props.project]), {
        preserveScroll: true,
        errorBag: "saveSettings",
        onSuccess: () => {
            console.log(props.survey);
            toast.success("Settings successfully saved");
            settingModal.value = false;
            setFirstSettings.value = false;
        },
    });
};

const settingModal = ref(false);
const specificTest = ref(false);
const selectedSpecificTest = ref(null);
const variablesModal = ref(false);
// const createVariable = () => {
//     form.post(route("variables.store", props.project), {
//         preserveScroll: true,
//         resetOnSuccess: true,
//         errorBag: "createVariable",
//         onSuccess: () => {
//             toast.success("Variable successfully created");
//             setFirstSettings.value = false;
//         },
//     });
// };

const createVariablesFromArray = (variables) => {
    const postNext = (index) => {
        if (index >= variables.length) {
            toast.success("Test settings saved and all variables created");
            return;
        }

        const payload = {
            name: variables[index].name,
            config: variables[index].config,
        };

        router.post(route("variables.store", props.project), payload, {
            preserveScroll: true,
            errorBag: "createVariable",
            onError: () => {},
            onFinish: () => {
                postNext(index + 1);
            },
        });
    };

    postNext(0);
};

const saveConfigurationVariables = (variables) => {
    form.put(route("survey2.save_settings", [props.survey, props.project]), {
        preserveScroll: true,
        errorBag: "saveSettings",
        onSuccess: () => {
            settingModal.value = false;
            setFirstSettings.value = false;
            createVariablesFromArray(variables);
        },
    });
};

const saveSettingsadvance = () => {
    form.put(route("survey2.save_settings", [props.survey, props.project]), {
        preserveScroll: true,
        errorBag: "saveSettings",
        onSuccess: () => {
            toast.success("Adavance successfully saved");
            advanceModal.value = false;
        },
    });
};

const advanceModal = ref(false);

const saveSettingsthemes = () => {
    form.put(route("survey2.save_settings", [props.survey, props.project]), {
        preserveScroll: true,
        errorBag: "saveSettings",
        onSuccess: () => {
            toast.success("Themes successfully saved");
            themesModal.value = false;
        },
    });
};

const themesModal = ref(false);

const saveSettingsaddround = () => {
    form.put(route("survey2.save_settings", [props.survey, props.project]), {
        preserveScroll: true,
        errorBag: "saveSettings",
        onSuccess: () => {
            toast.success("Settings successfully saved");
            addRoundModal.value = false;
        },
    });
};

const addRoundModal = ref(false);

const showTrialModeModal = ref(false);
const selectedTrialMode = ref(null);

// Function to handle trial mode selection and show Add Round modal
const selectTrialMode = (mode) => {
    selectedTrialMode.value = mode;
    showTrialModeModal.value = false;

    // Show the Add Round modal with the selected trial mode
    addRoundModal.value = true;
};

// Function to open trial mode selection modal
const openTrialModeSelection = () => {
    showTrialModeModal.value = true;
    selectedTrialMode.value = null;
};

const saveSettingsvariable = () => {
    form.put(route("survey2.save_settings", [props.survey, props.project]), {
        preserveScroll: true,
        errorBag: "saveSettings",
        onSuccess: () => {
            toast.success("Settings successfully saved");
            variableModal.value = false;
        },
    });
};
const variableModal = ref(false);

const selectedSurvey = ref(false);

const normalizeTheme = (theme) => {
    if (theme === undefined || theme === null || theme === "") {
        return null;
    }

    if (typeof theme === "number") {
        return theme;
    }

    if (typeof theme === "string") {
        const trimmed = theme.trim();
        if (!trimmed) {
            return null;
        }

        const numeric = Number(trimmed);
        if (!Number.isNaN(numeric)) {
            return numeric;
        }

        const lowered = trimmed.toLowerCase();
        if (["likert", "likert scale"].includes(lowered)) {
            return 2;
        }
        if (
            [
                "attribute choice",
                "attribute-choice",
                "attribute_choice",
                "attributechoice",
            ].includes(lowered)
        ) {
            return 1;
        }

        return lowered;
    }

    return null;
};

const filteredSurveysForCopy = computed(() => {
    const currentTheme = normalizeTheme(props.survey?.config?.theme);
    const restrictThemes = [1, 2];

    if (!restrictThemes.includes(currentTheme)) {
        return props.surveys;
    }

    return props.surveys.filter((survey) => {
        return normalizeTheme(survey?.config?.theme) === currentTheme;
    });
});

// watch(() => (confirmingTrialCopyTo.value), (new_value, old_value) => {
//     if (new_value) {
//         selectedSurvey.value = new_value.id;
//     }
// });

const confirmingTrialMove = ref(false);
const selectedPart = ref(false);

const moveTrialDialog = (test) => {
    confirmingTrialMove.value = assign(test);
    selectedPart.value = confirmingTrialMove.value.part;
};

const moveTrial = () => {
    form.get(
        route("tests.move", [
            props.survey,
            confirmingTrialMove.value,
            selectedPart.value,
        ]),
        {
            preserveScroll: true,
            onSuccess: () => {
                confirmingTrialMove.value = false;
                selectedPart.value = null;
                toast.success("Trial successfully moved");
            },
        }
    );
};

function getInitials(name) {
    if (!name) return "";
    const parts = name.trim().split(" ");
    const first = parts[0]?.[0] || "";
    const last = parts.length > 1 ? parts[parts.length - 1][0] : "";
    return (first + last).toUpperCase();
}

const isOpen = ref(false);

// Optional: close dropdown when clicking outside
document.addEventListener("click", (e) => {
    if (!e.target.closest(".relative")) {
        isOpen.value = false;
    }
});

const openDropdown = ref(null); // store index or id of open dropdown

function toggleDropdown(index) {
    openDropdown.value = openDropdown.value === index ? null : index;
}

// close on outside click
function handleClickOutside(e) {
    if (!e.target.closest(".dropdown-container")) {
        openDropdown.value = null;
    }
}
const setFirstSettings = ref(false);

const closeFirstSetup = () => {
    setFirstSettings.value = false;
    window.location.href = route(
        "redirect.fluent",
        props.survey.config.survey_id
    );
};
onMounted(() => {
    if (props.tests.length === 0 && props.survey.config.theme === 0) {
        setFirstSettings.value = true;
    }
    try {
        const urls = buildImageManifestFromTests(props.tests, props.survey);
        warmCacheSession(urls);
        decodeImagesInBackground(urls);
    } catch (_) {}
    document.addEventListener("click", handleClickOutside);

    // Add entrance animation classes
    setTimeout(() => {
        document.querySelector(".nav-container")?.classList.add("nav-enter");
        document
            .querySelector(".test-list-container")
            ?.classList.add("test-list-enter");
    }, 50);

    // Preload heavy editor and create page chunks in the background
    // This warms JS/CSS for Create2 and Engine editor before navigation
    queueMicrotask(() => {
        // Dynamically import modules to fetch their chunks without rendering
        import("@/Pages/Surveys2/Create2.vue").catch(() => {});
        import("@/Pages/Engine/SurveyEditor.vue").catch(() => {});
        import("@/Pages/Engine/EngineTest.vue").catch(() => {});
    });

    // Listen for ROUNDS_LIST_UPDATED message from Chatbot
    const handleRoundsListUpdate = (event) => {
        if (event.data?.type === "ROUNDS_LIST_UPDATED") {
            const surveyId = event.data?.survey_id;
            if (surveyId === props.survey.id) {
                // Reload the page to refresh the rounds list
                router.reload({ only: ['tests'] });
            }
        }
    };

    window.addEventListener("message", handleRoundsListUpdate);

    // Clean up listener on unmount
    onBeforeUnmount(() => {
        window.removeEventListener("message", handleRoundsListUpdate);
        document.removeEventListener("click", handleClickOutside);
    });
});

watch(
    () => props.tests,
    () => {
        try {
            const urls = buildImageManifestFromTests(props.tests, props.survey);
            warmAndDecodeSession(urls);
        } catch (_) {}
    },
    { deep: true }
);

const trialAction = (type = "trial") => {
    modalType.value = type; // so modal knows what to show
    openModal.value = true;
};
</script>

<template>
    <Head :title="survey.name" />
    <div class="bg-slate-200 min-h-[100vh] text-[#323232] page-container">
        <div class="flex flex-col max-w-full mx-auto">
            <div class="sticky top-0 flex flex-col bg-slate-200">
                <div
                    class="flex items-center justify-between h-[70px] py-2 px-4 bg-white shadow-md header-container"
                >
                    <div class="flex items-center gap-5 mx-1">
                        <!-- <div>
                            <a
                                :href="
                                    route(
                                        'redirect.fluent',
                                        survey.config.survey_id
                                    )
                                "
                            >
                                <button
                                    class="bg-[#257556] text-white rounded-full text-[16px] hover:bg-[#1d5e45] w-[200px] h-[44px] justify-center items-center flex gap-2"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2.5"
                                        stroke="currentColor"
                                        class="w-5 h-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M19.5 12h-15m0 0l6.75 6.75M4.5 12l6.75-6.75"
                                        />
                                    </svg>
                                    Back to Fluent
                                </button>
                            </a>
                        </div> -->

                        <div
                            class="mt-2 active:scale-[0.85] transition-all duration-200"
                        >
                            <a
                                :href="
                                    route(
                                        'redirect.fluent',
                                        survey.config.survey_id
                                    )
                                "
                                class="mt-2"
                            >
                                <button>
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
                                </button>
                            </a>
                        </div>

                        <div class="text-lg flex items-center gap-2">
                            <!-- {{ survey.name }} -->
                            <Dropdown align="left" width="60">
                                <template #trigger>
                                    <div class="flex items-center gap-2">
                                        <button
                                            class="text-[24px] rounded-lg w-auto h-[40px] justify-center items-center flex font-bold text-[#323232]"
                                        >
                                            {{ props.project.name }}&nbsp;/
                                        </button>
                                        <button
                                            class="text-[24px] rounded-lg w-auto h-[40px] justify-center items-center flex font-bold text-[#323232]"
                                        >
                                            {{ survey.name }}&nbsp;
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="5"
                                                stroke="currentColor"
                                                class="w-3 h-3 font-bold"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </template>

                                <template #content>
                                    <div
                                        v-for="survey in surveys"
                                        :key="survey.id"
                                        class="hover:bg-[#25755620] transition-all duration-200"
                                    >
                                        <DropdownLink
                                            :href="
                                                route('survey2.index', survey)
                                            "
                                        >
                                            {{ survey.name }}
                                        </DropdownLink>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- <div class="text-lg tracking-wide">
                        Logged in as {{ $page.props.auth.user.name }}
                    </div> -->

                    <div
                        class="w-[50px] h-[50px] flex items-center justify-center rounded-full bg-[#25755630] text-[#257556] text-[20px] cursor-default"
                        :title="$page.props.auth.user.name"
                    >
                        {{ getInitials($page.props.auth.user.name) }}
                    </div>
                </div>
            </div>

            <!-- Body Start -->
            <div class="flex h-[92.5vh]">
                <!-- floating button add round -->
                <JetButton
                    v-if="props.survey.tests.length > 0"
                    class="fixed right-8 top-20 mt-2 z-50 rounded-md py-2 px-4 italic text-sm shadow-xl"
                    @click.prevent="openTrialModeSelection()"
                    variant="primary"
                    size="md"
                >
                    <svg
                        data-v-2da10074=""
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="w-3.5 h-3.5 mr-1 self-center"
                    >
                        <path
                            data-v-2da10074=""
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4.5v15m7.5-7.5h-15"
                        ></path>
                    </svg>
                    Add Round
                </JetButton>
                <div class="flex flex-row gap-2 overflow-y-auto item">
                    <!-- Sidebar Start -->
                    <div
                        v-show="props.survey.config.theme !== 0"
                        class="w-[6rem] bg-white flex-shrink-0 flex flex-col h-[92.5vh] overflow-y-auto nav-container nav-initial"
                    >
                        <!-- ADD ROUND -->
                        <!-- <Link :href="route('survey2.create', props.survey)" v-if="userPermissions.canUpdateSurvey"> -->
                        <!-- <Link :href="route('survey2.create', props.survey)" v-if="userPermissions.canUpdateSurvey">
                        <button
                            class="w-full py-6 gap-2 border text-[14px] font-bold border-gray-200 flex flex-col rounded-none bg-white hover:text-white hover:bg-[#257556] text-[#323232] items-center"
                            shadow="xl"
                        >
                            <svg
                                width="31"
                                height="30"
                                viewBox="0 0 31 30"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <g clip-path="url(#clip0_380_13269)">
                                    <path
                                        d="M16 0C7.72909 0 1 6.72909 1 15C1 23.2709 7.72909 30 16 30C24.2709 30 31 23.2709 31 15C31 6.72909 24.2709 0 16 0ZM21.7692 16.1538H17.1538V20.7692C17.1538 21.0752 17.0323 21.3687 16.8159 21.5851C16.5995 21.8015 16.306 21.9231 16 21.9231C15.694 21.9231 15.4005 21.8015 15.1841 21.5851C14.9677 21.3687 14.8462 21.0752 14.8462 20.7692V16.1538H10.2308C9.92475 16.1538 9.63127 16.0323 9.41488 15.8159C9.19849 15.5995 9.07692 15.306 9.07692 15C9.07692 14.694 9.19849 14.4005 9.41488 14.1841C9.63127 13.9677 9.92475 13.8462 10.2308 13.8462H14.8462V9.23077C14.8462 8.92475 14.9677 8.63127 15.1841 8.41488C15.4005 8.19849 15.694 8.07692 16 8.07692C16.306 8.07692 16.5995 8.19849 16.8159 8.41488C17.0323 8.63127 17.1538 8.92475 17.1538 9.23077V13.8462H21.7692C22.0752 13.8462 22.3687 13.9677 22.5851 14.1841C22.8015 14.4005 22.9231 14.694 22.9231 15C22.9231 15.306 22.8015 15.5995 22.5851 15.8159C22.3687 16.0323 22.0752 16.1538 21.7692 16.1538Z"
                                        fill="currentColor"
                                    />
                                </g>
                                <defs>
                                    <clipPath id="clip0_380_13269">
                                        <rect
                                            width="30"
                                            height="30"
                                            fill="white"
                                            transform="translate(0.887695)"
                                        />
                                    </clipPath>
                                </defs>
                            </svg>

                            Add Round
                        </button>
                        </Link>
 -->

                        <!-- Add Round -->
                        <!-- <button
                            class="w-full py-6 gap-2 text-[14px] font-bold flex flex-col rounded-none border border-gray-200 bg-white hover:text-white hover:bg-[#257556] text-[#323232] items-center"
                            shadow="xl"
                            @click.prevent="openTrialModeSelection()"
                        >
                            <svg
                                width="31"
                                height="30"
                                viewBox="0 0 31 30"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <g clip-path="url(#clip0_380_13269)">
                                    <path
                                        d="M16 0C7.72909 0 1 6.72909 1 15C1 23.2709 7.72909 30 16 30C24.2709 30 31 23.2709 31 15C31 6.72909 24.2709 0 16 0ZM21.7692 16.1538H17.1538V20.7692C17.1538 21.0752 17.0323 21.3687 16.8159 21.5851C16.5995 21.8015 16.306 21.9231 16 21.9231C15.694 21.9231 15.4005 21.8015 15.1841 21.5851C14.9677 21.3687 14.8462 21.0752 14.8462 20.7692V16.1538H10.2308C9.92475 16.1538 9.63127 16.0323 9.41488 15.8159C9.19849 15.5995 9.07692 15.306 9.07692 15C9.07692 14.694 9.19849 14.4005 9.41488 14.1841C9.63127 13.9677 9.92475 13.8462 10.2308 13.8462H14.8462V9.23077C14.8462 8.92475 14.9677 8.63127 15.1841 8.41488C15.4005 8.19849 15.694 8.07692 16 8.07692C16.306 8.07692 16.5995 8.19849 16.8159 8.41488C17.0323 8.63127 17.1538 8.92475 17.1538 9.23077V13.8462H21.7692C22.0752 13.8462 22.3687 13.9677 22.5851 14.1841C22.8015 14.4005 22.9231 14.694 22.9231 15C22.9231 15.306 22.8015 15.5995 22.5851 15.8159C22.3687 16.0323 22.0752 16.1538 21.7692 16.1538Z"
                                        fill="currentColor"
                                    />
                                </g>
                                <defs>
                                    <clipPath id="clip0_380_13269">
                                        <rect
                                            width="30"
                                            height="30"
                                            fill="white"
                                            transform="translate(0.887695)"
                                        />
                                    </clipPath>
                                </defs>
                            </svg>

                            Add Round
                        </button> -->
                        <!-- Add round 2.0 -->
                        <button
                            class="w-full py-6 gap-1 text-[14px] font-bold flex flex-col rounded-none border border-gray-200 text-white bg-[#257556] text-[#323232] items-center duration-300 transition-all ease-in-out cursor-default"
                            shadow="xl"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="w-[40px] h-[40px]"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"
                                ></path>
                            </svg>
                            Round ({{ props.survey.tests.length }})
                        </button>

                        <!-- Variables -->
                        <Link
                            v-if="props.survey.config.theme !== 0"
                            :href="route('variables2.index', [project, survey])"
                        >
                            <button
                                size="sm"
                                class="w-full py-5 gap-2 flex flex-col rounded-none border text-[14px] font-bold border-gray-200 bg-white hover:text-white hover:bg-[#257556] text-[#323232] items-center duration-300 transition-all ease-in-out"
                                shadow="xl"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="32"
                                    height="32"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-variable-icon lucide-variable"
                                >
                                    <path d="M8 21s-4-3-4-9 4-9 4-9" />
                                    <path d="M16 3s4 3 4 9-4 9-4 9" />
                                    <line x1="15" x2="9" y1="9" y2="15" />
                                    <line x1="9" x2="15" y1="9" y2="15" />
                                </svg>

                                Variables
                            </button>
                        </Link>

                        <Link
                            v-if="props.survey.config.theme !== 0"
                            :href="
                                route('templates.index', [
                                    project.team_id,
                                    project.uuid,
                                    survey.id,
                                ])
                            "
                            class="w-full py-6 gap-2 text-[14px] font-bold flex flex-col rounded-none border border-gray-200 bg-white hover:text-white hover:bg-[#257556] text-[#323232] items-center duration-300 transition-all ease-in-out"
                            shadow="xl"
                        >
                            <svg
                                width="30"
                                height="30"
                                viewBox="0 0 30 30"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <g clip-path="url(#clip0_573_11282)">
                                    <path
                                        d="M28 2V9H2V2H28ZM27.5 0H2.5C1.83696 0 1.20107 0.289731 0.732233 0.805456C0.263392 1.32118 0 2.02065 0 2.75V8.25C0 8.97935 0.263392 9.67882 0.732233 10.1945C1.20107 10.7103 1.83696 11 2.5 11H27.5C28.163 11 28.7989 10.7103 29.2678 10.1945C29.7366 9.67882 30 8.97935 30 8.25V2.75C30 2.02065 29.7366 1.32118 29.2678 0.805456C28.7989 0.289731 28.163 0 27.5 0Z"
                                        fill="currentColor"
                                    />
                                    <path
                                        d="M9 14V28H2V14H9ZM8.25 12H2.75C2.02065 12 1.32118 12.2709 0.805456 12.7532C0.289731 13.2354 0 13.8894 0 14.5714V27.4286C0 28.1106 0.289731 28.7646 0.805456 29.2468C1.32118 29.7291 2.02065 30 2.75 30H8.25C8.97935 30 9.67882 29.7291 10.1945 29.2468C10.7103 28.7646 11 28.1106 11 27.4286V14.5714C11 13.8894 10.7103 13.2354 10.1945 12.7532C9.67882 12.2709 8.97935 12 8.25 12Z"
                                        fill="currentColor"
                                    />
                                    <path
                                        d="M28 14V28H15V14H28ZM27.5714 12H15.4286C14.7845 12 14.1668 12.2709 13.7113 12.7532C13.2559 13.2354 13 13.8894 13 14.5714V27.4286C13 28.1106 13.2559 28.7646 13.7113 29.2468C14.1668 29.7291 14.7845 30 15.4286 30H27.5714C28.2155 30 28.8332 29.7291 29.2887 29.2468C29.7441 28.7646 30 28.1106 30 27.4286V14.5714C30 13.8894 29.7441 13.2354 29.2887 12.7532C28.8332 12.2709 28.2155 12 27.5714 12Z"
                                        fill="currentColor"
                                    />
                                </g>
                                <defs>
                                    <clipPath id="clip0_573_11282">
                                        <rect
                                            width="30"
                                            height="30"
                                            fill="white"
                                        />
                                    </clipPath>
                                </defs>
                            </svg>

                            <button class="w-full">Templates</button>
                        </Link>

                        <!-- Settings -->
                        <button
                            v-if="props.survey.config.theme !== 0"
                            class="w-full py-6 gap-2 text-[14px] font-bold flex flex-col rounded-none border border-gray-200 bg-white hover:text-white hover:bg-[#257556] text-[#323232] items-center duration-300 transition-all ease-in-out"
                            shadow="xl"
                            @click.prevent="settingModal = true"
                        >
                            <svg
                                width="34"
                                height="34"
                                viewBox="0 0 30 30"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M15 10.3125C12.4112 10.3125 10.3125 12.4112 10.3125 15C10.3125 17.5889 12.4112 19.6875 15 19.6875C17.5889 19.6875 19.6875 17.5889 19.6875 15C19.6875 12.4112 17.5889 10.3125 15 10.3125ZM12.1875 15C12.1875 13.4468 13.4468 12.1875 15 12.1875C16.5532 12.1875 17.8125 13.4468 17.8125 15C17.8125 16.5532 16.5532 17.8125 15 17.8125C13.4468 17.8125 12.1875 16.5532 12.1875 15Z"
                                    fill="currentColor"
                                />
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M14.9685 1.5625C14.413 1.56249 13.9491 1.56249 13.5684 1.58846C13.172 1.61551 12.7976 1.67381 12.4346 1.82416C11.5924 2.17305 10.9232 2.84224 10.5743 3.68453C10.3926 4.12303 10.3435 4.58515 10.3247 5.08745C10.3096 5.49124 10.1058 5.82814 9.80506 6.00176C9.50434 6.17539 9.11069 6.18341 8.75345 5.9946C8.30905 5.75971 7.88424 5.57124 7.41366 5.50929C6.50979 5.39029 5.59565 5.63524 4.87236 6.19024C4.56066 6.42941 4.32296 6.7245 4.10136 7.05425C3.88847 7.37101 3.65659 7.77269 3.3788 8.25384L3.34724 8.30851C3.06944 8.78965 2.83754 9.1913 2.66965 9.53404C2.49489 9.89084 2.35817 10.2442 2.30689 10.6338C2.18789 11.5377 2.43284 12.4518 2.98782 13.1751C3.27672 13.5515 3.65229 13.8253 4.07784 14.0926C4.42006 14.3076 4.60997 14.6526 4.60995 15C4.60994 15.3472 4.42002 15.6922 4.07784 15.9072C3.65224 16.1746 3.27664 16.4484 2.98771 16.8249C2.43271 17.5481 2.18777 18.4622 2.30676 19.3661C2.35805 19.7556 2.49476 20.1091 2.66952 20.4659C2.83741 20.8086 3.06931 21.2102 3.3471 21.6914L3.37867 21.7461C3.65646 22.2273 3.88835 22.6289 4.10124 22.9456C4.32284 23.2754 4.56054 23.5705 4.87224 23.8096C5.59552 24.3646 6.50966 24.6096 7.41354 24.4906C7.88409 24.4286 8.30887 24.2402 8.75325 24.0054C9.11054 23.8165 9.50424 23.8245 9.805 23.9983C10.1058 24.1719 10.3096 24.5087 10.3247 24.9126C10.3435 25.4149 10.3927 25.877 10.5743 26.3155C10.9232 27.1578 11.5924 27.827 12.4346 28.1759C12.7976 28.3263 13.172 28.3845 13.5684 28.4115C13.9491 28.4375 14.413 28.4375 14.9685 28.4375H15.0316C15.5872 28.4375 16.051 28.4375 16.4319 28.4115C16.8282 28.3845 17.2026 28.3263 17.5656 28.1759C18.4079 27.827 19.0771 27.1578 19.426 26.3155C19.6076 25.877 19.6567 25.4149 19.6755 24.9125C19.6906 24.5088 19.8944 24.1719 20.1951 23.9981C20.4959 23.8245 20.8896 23.8165 21.2469 24.0053C21.6912 24.2401 22.116 24.4286 22.5866 24.4905C23.4905 24.6095 24.4046 24.3646 25.1279 23.8096C25.4396 23.5704 25.6772 23.2754 25.8989 22.9456C26.1117 22.6289 26.3436 22.2273 26.6214 21.7461L26.653 21.6914C26.9307 21.2102 27.1628 20.8085 27.3306 20.4658C27.5054 20.109 27.642 19.7556 27.6934 19.3661C27.8124 18.4621 27.5674 17.548 27.0124 16.8247C26.7235 16.4482 26.3479 16.1746 25.9224 15.9072C25.5801 15.6922 25.3902 15.3472 25.3902 14.9999C25.3902 14.6526 25.5801 14.3078 25.9222 14.0928C26.348 13.8254 26.7236 13.5518 27.0125 13.1751C27.5675 12.4519 27.8125 11.5377 27.6935 10.6339C27.6421 10.2443 27.5055 9.89093 27.3307 9.53413C27.1629 9.19141 26.931 8.78979 26.6531 8.30869L26.6216 8.25398C26.3437 7.77281 26.1119 7.3711 25.899 7.05434C25.6774 6.72459 25.4398 6.4295 25.128 6.19031C24.4048 5.63533 23.4906 5.39038 22.5866 5.50938C22.1161 5.57133 21.6914 5.75978 21.247 5.99465C20.8897 6.18349 20.496 6.17545 20.1952 6.0018C19.8945 5.82816 19.6906 5.49121 19.6755 5.08739C19.6567 4.5851 19.6076 4.123 19.426 3.68453C19.0771 2.84224 18.4079 2.17305 17.5656 1.82416C17.2026 1.67381 16.8282 1.61551 16.4319 1.58846C16.051 1.56249 15.5872 1.56249 15.0316 1.5625H14.9685ZM13.1521 3.55644C13.2486 3.51649 13.3951 3.47965 13.696 3.45911C14.0054 3.43801 14.4049 3.4375 15.0001 3.4375C15.5954 3.4375 15.9949 3.43801 16.3041 3.45911C16.6051 3.47965 16.7516 3.51649 16.848 3.55644C17.2309 3.71503 17.5351 4.0192 17.6936 4.40206C17.7437 4.52283 17.7851 4.71109 17.8019 5.15746C17.8389 6.14794 18.3501 7.10161 19.2577 7.6256C20.1652 8.1496 21.2469 8.1155 22.1231 7.65235C22.518 7.44363 22.7019 7.3854 22.8314 7.36834C23.2422 7.31425 23.6577 7.42559 23.9865 7.67785C24.0694 7.7414 24.1745 7.84986 24.3427 8.1002C24.5157 8.35754 24.7159 8.70325 25.0135 9.21875C25.3111 9.73426 25.5105 10.0805 25.6469 10.3589C25.7795 10.6298 25.8209 10.7751 25.8345 10.8786C25.8886 11.2894 25.7773 11.705 25.525 12.0337C25.4454 12.1374 25.3031 12.2675 24.9247 12.5051C24.0856 13.0325 23.5154 13.952 23.5152 14.9999C23.5152 16.0479 24.0855 16.9675 24.9247 17.4949C25.303 17.7325 25.4452 17.8625 25.5249 17.9663C25.7771 18.295 25.8885 18.7105 25.8344 19.1214C25.8207 19.2249 25.7794 19.3701 25.6467 19.641C25.5104 19.9194 25.311 20.2656 25.0134 20.7811C24.7157 21.2966 24.5156 21.6424 24.3426 21.8998C24.1744 22.15 24.0692 22.2585 23.9864 22.322C23.6576 22.5744 23.2421 22.6856 22.8312 22.6316C22.7017 22.6145 22.5179 22.5562 22.123 22.3476C21.2467 21.8844 20.1651 21.8504 19.2575 22.3744C18.3501 22.8984 17.8389 23.852 17.8019 24.8425C17.7851 25.2889 17.7437 25.4771 17.6936 25.598C17.5351 25.9807 17.2309 26.285 16.848 26.4436C16.7516 26.4835 16.6051 26.5204 16.3041 26.5409C15.9949 26.562 15.5954 26.5625 15.0001 26.5625C14.4049 26.5625 14.0054 26.562 13.696 26.5409C13.3951 26.5204 13.2486 26.4835 13.1521 26.4436C12.7694 26.285 12.4651 25.9807 12.3065 25.598C12.2565 25.4771 12.2151 25.2889 12.1984 24.8425C12.1613 23.8521 11.6501 22.8984 10.7425 22.3744C9.83492 21.8504 8.7534 21.8845 7.8771 22.3476C7.48219 22.5564 7.29841 22.6146 7.16881 22.6316C6.75795 22.6858 6.34244 22.5744 6.01367 22.3221C5.93085 22.2586 5.82569 22.1501 5.65745 21.8998C5.48451 21.6425 5.28432 21.2968 4.9867 20.7812C4.68906 20.2657 4.48976 19.9195 4.35337 19.6411C4.2207 19.3703 4.17935 19.2249 4.16572 19.1214C4.11164 18.7105 4.22297 18.295 4.47525 17.9663C4.55482 17.8626 4.69714 17.7325 5.07537 17.4949C5.9146 16.9676 6.4849 16.048 6.48495 15.0001C6.48501 13.952 5.91469 13.0324 5.07539 12.505C4.69724 12.2674 4.55494 12.1373 4.47536 12.0336C4.2231 11.7049 4.11176 11.2894 4.16585 10.8785C4.17947 10.775 4.22082 10.6297 4.3535 10.3588C4.48989 10.0804 4.68919 9.73418 4.98681 9.21866C5.28445 8.70316 5.48462 8.35745 5.65757 8.10011C5.82581 7.84979 5.93097 7.74131 6.01379 7.67778C6.34256 7.4255 6.75807 7.31416 7.16892 7.36825C7.29854 7.38531 7.48232 7.44354 7.87729 7.6523C8.75354 8.11544 9.83501 8.14953 10.7425 7.62556C11.6501 7.1016 12.1613 6.14796 12.1984 5.15753C12.2151 4.71111 12.2565 4.52284 12.3065 4.40206C12.4651 4.0192 12.7694 3.71503 13.1521 3.55644Z"
                                    fill="currentColor"
                                />
                            </svg>

                            Global Settings
                        </button>

                        <!-- Themes -->
                        <!-- <button
                            v-if="props.survey.config.theme !== 0"
                            class="w-full py-6 gap-2 text-[14px] font-bold flex flex-col rounded-none border border-gray-200 bg-white hover:text-white hover:bg-[#257556] text-[#323232] items-center duration-300 transition-all ease-in-out"
                            shadow="xl"
                            @click.prevent="themesModal = true"
                        >
                            <svg
                                width="40"
                                height="40"
                                viewBox="0 0 30 30"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M16.7502 2.6203C14.6891 2.3382 12.5902 2.57079 10.6409 3.29734C8.69162 4.02388 6.95254 5.22176 5.57891 6.78405C4.40329 8.13803 3.53102 9.72791 3.02069 11.4469C2.51036 13.1659 2.3738 14.9742 2.62016 16.7503C3.28266 21.6178 6.94266 25.7591 11.7264 27.0578C12.7928 27.3496 13.8933 27.4984 14.9989 27.5003L15.1764 27.4991C15.8083 27.4909 16.428 27.3233 16.9778 27.0116C17.5276 26.7 17.9898 26.2545 18.3214 25.7166C18.6542 25.1815 18.8462 24.571 18.8796 23.9418C18.913 23.3126 18.7868 22.6851 18.5127 22.1178L18.2639 21.5978C18.0726 21.224 17.9827 20.8065 18.0034 20.3871C18.024 19.9677 18.1543 19.561 18.3814 19.2078C18.7063 18.68 19.2153 18.2914 19.81 18.1171C20.4048 17.9428 21.043 17.9952 21.6014 18.2641L22.1164 18.5103C22.6314 18.7578 23.1789 18.8841 23.7427 18.8841C24.7293 18.879 25.6747 18.4881 26.3769 17.7951C27.0791 17.102 27.4822 16.1617 27.5002 15.1753C27.5156 14.0103 27.3671 12.8489 27.0589 11.7253C25.7589 6.94281 21.6177 3.2828 16.7502 2.6203ZM23.1977 16.2566L22.6827 16.0103C20.3977 14.9128 17.5952 15.7628 16.2639 17.8778C15.3414 19.3478 15.2477 21.0978 16.0089 22.6803L16.2577 23.2003C16.3509 23.3892 16.394 23.5989 16.3828 23.8093C16.3717 24.0196 16.3066 24.2236 16.1939 24.4016C16.0841 24.5834 15.9294 24.7339 15.7446 24.8387C15.5599 24.9436 15.3513 24.9992 15.1389 25.0003H14.9989C14.1142 24.9979 13.2338 24.8789 12.3802 24.6466C8.55141 23.6078 5.62641 20.2978 5.09766 16.4141C4.69641 13.4703 5.53766 10.6353 7.46391 8.42781C8.55983 7.17059 9.95273 6.20737 11.5159 5.62578C13.079 5.04419 14.7627 4.86269 16.4139 5.09781C20.2977 5.62656 23.6077 8.55281 24.6464 12.3803C24.8939 13.2928 25.0127 14.2228 24.9989 15.1391C24.9839 16.1478 23.9427 16.6178 23.1977 16.2566Z"
                                    fill="currentColor"
                                />
                                <path
                                    d="M9.375 20C10.4105 20 11.25 19.1605 11.25 18.125C11.25 17.0895 10.4105 16.25 9.375 16.25C8.33947 16.25 7.5 17.0895 7.5 18.125C7.5 19.1605 8.33947 20 9.375 20Z"
                                    fill="currentColor"
                                />
                                <path
                                    d="M9.375 15C10.4105 15 11.25 14.1605 11.25 13.125C11.25 12.0895 10.4105 11.25 9.375 11.25C8.33947 11.25 7.5 12.0895 7.5 13.125C7.5 14.1605 8.33947 15 9.375 15Z"
                                    fill="currentColor"
                                />
                                <path
                                    d="M13.125 11.25C14.1605 11.25 15 10.4105 15 9.375C15 8.33947 14.1605 7.5 13.125 7.5C12.0895 7.5 11.25 8.33947 11.25 9.375C11.25 10.4105 12.0895 11.25 13.125 11.25Z"
                                    fill="currentColor"
                                />
                                <path
                                    d="M18.125 11.25C19.1605 11.25 20 10.4105 20 9.375C20 8.33947 19.1605 7.5 18.125 7.5C17.0895 7.5 16.25 8.33947 16.25 9.375C16.25 10.4105 17.0895 11.25 18.125 11.25Z"
                                    fill="currentColor"
                                />
                            </svg>
                            Themes
                        </button> -->

                        <!-- Advance -->
                        <button
                            v-if="props.survey.config.theme !== 0"
                            class="w-full py-6 gap-2 text-[14px] font-bold flex flex-col rounded-none border border-gray-200 bg-white hover:text-white hover:bg-[#257556] text-[#323232] items-center duration-300 transition-all ease-in-out"
                            shadow="xl"
                            @click.prevent="advanceModal = true"
                        >
                            <svg
                                width="30"
                                height="30"
                                viewBox="0 0 30 30"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <g clip-path="url(#clip0_573_11258)">
                                    <path
                                        d="M3.52371 29.8838C2.16402 29.5275 1.06868 28.4532 0.675723 27.0904L0.540039 26.6197V15.0298C0.540039 5.51465 0.554182 3.38203 0.619239 3.11638C0.662824 2.93841 0.818266 2.5413 0.964752 2.2339C1.19241 1.75612 1.3047 1.60231 1.73858 1.17417C2.31917 0.601307 2.83572 0.306564 3.594 0.115529C4.04961 0.000748672 4.26231 -0.00166302 13.7425 0.000368408L23.4256 0.00251336L26.444 2.98615L29.4623 5.9698L29.4443 16.3536L29.4263 26.7375L29.2872 27.1493C28.808 28.5683 27.7924 29.5354 26.4102 29.889C25.9786 29.9993 25.6495 30.0025 14.959 29.9994C4.32257 29.9964 3.93784 29.9923 3.52371 29.8838ZM26.5 28C26.8912 27.8079 27.3997 27.3973 27.5 27C27.5424 26.8316 27.5 22.9093 27.5 16.5V7.3817L24.5876 7.39708L22.1902 7.3817L22.1748 4.92546V2L13 2H4H3.5C3.33821 2.0755 3.11953 2.36742 3 2.5C2.57027 2.97672 2.5 2.66194 2.5 15.0805C2.5 27.1346 2.18003 26.5267 2.5 27C2.67931 27.2652 3.1562 27.907 3.5 28C3.72033 28.0596 6.5067 28.0089 15 28H26H26.5ZM11.6665 22.6014C11.6493 22.5737 12.5475 19.431 13.6623 15.6177C14.7771 11.8044 15.6892 8.6759 15.6892 8.66549C15.6892 8.65508 16.0731 8.6466 16.5423 8.6466C17.0115 8.6466 17.3948 8.6532 17.3941 8.6613C17.3941 8.6694 16.4717 11.8132 15.3459 15.6476L13.2989 22.6192L12.4983 22.6355C12.0579 22.6445 11.6836 22.6292 11.6665 22.6014ZM7.74981 18.3794C6.27908 17.4241 5.04141 16.6139 4.99941 16.5791C4.94511 16.534 4.923 16.3255 4.923 15.8582V15.2008L7.63521 13.3503C9.12694 12.3326 10.3713 11.4911 10.4003 11.4803C10.4303 11.4692 10.4532 11.8917 10.4532 12.4558V13.4508L8.48515 14.6501C7.40275 15.3097 6.52247 15.8544 6.52902 15.8606C6.53545 15.8667 7.4013 16.3657 8.45293 16.9694C9.50455 17.5731 10.3855 18.0872 10.4106 18.1119C10.4354 18.1366 10.4487 18.5976 10.4401 19.1365L10.4238 20.1163L7.74981 18.3794ZM18.7618 18.9927L18.7777 17.9843L20.71 16.8802L22.6424 15.776L22.3279 15.5769C22.155 15.4673 21.2788 14.9309 20.3809 14.3849L18.7483 13.3921V12.4019C18.7483 11.8573 18.762 11.4117 18.7783 11.4117C18.7946 11.4117 20.0258 12.2372 21.5139 13.2461L24.2196 15.0805V15.7641V16.4477L21.6491 18.1214C20.2352 19.042 19.0036 19.8415 18.912 19.8982L18.7455 20.0011L18.7618 18.9927Z"
                                        fill="currentColor"
                                    />
                                </g>
                                <defs>
                                    <clipPath id="clip0_573_11258">
                                        <rect
                                            width="30"
                                            height="30"
                                            fill="white"
                                        />
                                    </clipPath>
                                </defs>
                            </svg>

                            Advanced
                        </button>

                        <!-- Variable 2.0 -->
                        <!-- <button
                            class="w-full py-6 gap-2 text-[14px] font-bold flex flex-col rounded-none border border-gray-200 bg-white hover:text-white hover:bg-[#257556] text-[#323232] items-center"
                            shadow="xl"
                            @click.prevent="variableModal = true"
                        >
                            <svg
                                width="30"
                                height="30"
                                viewBox="0 0 30 30"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M15 30C23.2843 30 30 23.2843 30 15C30 6.71573 23.2843 0 15 0C6.71573 0 0 6.71573 0 15C0 23.2843 6.71573 30 15 30ZM8 8.5L13 23H17L22 8.5H18.5L15 19.5L11.5 8.5H8Z"
                                    fill="currentColor"
                                />
                            </svg>

                            Variable 2.0
                        </button> -->

                        <!-- Preview -->
                        <a
                            @click="
                                matchedVariables.length > 0
                                    ? (variablesModal = true)
                                    : null
                            "
                            :href="
                                matchedVariables.length === 0
                                    ? engine_url + survey.id + '&id=test'
                                    : null
                            "
                            target="_blank"
                            v-if="props.survey.config.theme !== 0"
                            class="flex flex-col w-full uppercase"
                            :class="{
                                'opacity-50 pointer-events-none':
                                    tests.length === 0,
                            }"
                        >
                            <button
                                class="w-full py-6 gap-2 border text-[14px] font-bold border-gray-200 flex flex-col rounded-none bg-white hover:text-white hover:bg-[#257556] text-[#323232] items-center duration-300 transition-all ease-in-out"
                                shadow="xl"
                            >
                                <svg
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

                                <span>
                                    Full <br />
                                    Preview
                                </span>
                            </button>
                        </a>

                        <!-- Live Editor -->
                        <!-- <a
                            :href="`${app_url}/surveys/${survey.id}/editor`"
                            target="_blank"
                            class="flex flex-col w-full uppercase"
                        >
                            <button
                                class="w-full py-6 gap-2 border text-[14px] font-bold border-gray-200 flex flex-col rounded-none bg-white hover:text-white hover:bg-[#257556] text-[#323232] items-center"
                                shadow="xl"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="30"
                                    height="30"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-brush-cleaning-icon lucide-brush-cleaning"
                                >
                                    <path d="m16 22-1-4" />
                                    <path
                                        d="M19 13.99a1 1 0 0 0 1-1V12a2 2 0 0 0-2-2h-3a1 1 0 0 1-1-1V4a2 2 0 0 0-4 0v5a1 1 0 0 1-1 1H6a2 2 0 0 0-2 2v.99a1 1 0 0 0 1 1"
                                    />
                                    <path
                                        d="M5 14h14l1.973 6.767A1 1 0 0 1 20 22H4a1 1 0 0 1-.973-1.233z"
                                    />
                                    <path d="m8 22 1-4" />
                                </svg>

                                Live Editor
                            </button>
                        </a> -->

                        <!-- <Link :href="route('analysis.index2', survey)" class="mr-1 text-xs text-blue-600 uppercase" v-if="userPermissions.canUpdateSurvey">
                        <JetButton variant="amber" size="xs" class="px-4 uppercase" shadow="xl">
                            responses
                        </JetButton>
                    </Link> -->
                    </div>
                    <!-- Sidebar End -->

                    <!-- Content Start -->
                     <!-- Chatbot Start -->
                    <teleport to="body" v-if="helpbotEnabled">
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
                                :anchor="chatbotAnchor"
                            />
                        </div>
                    </teleport>

                    <div
                        class="overflow-y-auto flex justify-center p-2 w-[1880vh]"
                    >
                        <div class="w-[120vh]">
                            <div
                                class="h-[70vh] flex items-center"
                                v-if="
                                    tests.length === 0 &&
                                    props.survey.config.theme !== 0
                                "
                            >
                                <!-- hide round button -->

                                <div
                                    class="flex flex-col items-center justify-center w-full mx-auto font-medium prose-2xl text-gray-600"
                                >
                                    <!-- Add Round Start -->
                                    <div
                                        class="flex flex-col items-center w-full p-4 italic text-center rounded-lg text-bold"
                                    >
                                        <svg
                                            width="100"
                                            height="100"
                                            viewBox="0 0 84 88"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <g
                                                clip-path="url(#clip0_281_11416)"
                                            >
                                                <mask
                                                    id="mask0_281_11416"
                                                    style="mask-type: luminance"
                                                    maskUnits="userSpaceOnUse"
                                                    x="0"
                                                    y="0"
                                                    width="84"
                                                    height="88"
                                                >
                                                    <path
                                                        d="M83.8379 0H0V87.756H83.8379V0Z"
                                                        fill="white"
                                                    />
                                                </mask>
                                                <g mask="url(#mask0_281_11416)">
                                                    <path
                                                        d="M41.9189 87.756C65.0702 87.756 83.8379 68.9207 83.8379 45.6861C83.8379 22.4516 65.0702 3.61621 41.9189 3.61621C18.7677 3.61621 0 22.4516 0 45.6861C0 68.9207 18.7677 87.756 41.9189 87.756Z"
                                                        fill="#F7F6F4"
                                                    />
                                                    <path
                                                        d="M41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839ZM20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075ZM39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671ZM64.264 57.1595L58.6824 11.9727C58.6272 11.6103 58.4319 11.285 58.1372 11.0705L44.4724 0.296028C44.3767 0.222078 44.2661 0.159221 44.1482 0.118549C43.9346 0.0150196 43.6951 -0.0182577 43.4593 0.0113221L14.7298 3.58679C10.9682 4.05638 8.29718 7.49504 8.76139 11.2776L15.1829 63.2678C15.6545 67.0466 19.0809 69.7273 22.8424 69.2651L58.2919 64.8503C62.0535 64.3733 64.7282 60.9347 64.264 57.1595ZM54.4272 11.1518L50.3414 11.6584C48.0793 11.9394 46.0198 10.331 45.7398 8.06445L45.2351 3.96025L54.4272 11.1518ZM57.953 62.1179L22.5072 66.5252C20.2488 66.8063 18.1893 65.1979 17.9093 62.9276L11.4877 10.9374C11.2077 8.67083 12.814 6.60394 15.0688 6.32293L43.7024 2.76225L44.3767 8.23453C44.3803 8.60058 44.4282 8.96293 44.513 9.31789C45.143 12.0023 47.8288 13.6661 50.5072 13.0265L55.9598 12.3498L61.5377 57.5034C61.8177 59.77 60.2114 61.8368 57.953 62.1179ZM52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972ZM21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839ZM20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075ZM39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349ZM41.4367 51.6688C41.3446 50.9108 40.6593 50.3747 39.904 50.4671L23.5424 52.5044C22.7909 52.5968 22.2567 53.2883 22.3488 54.0426C22.4409 54.7968 23.1261 55.3367 23.8814 55.2405L40.243 53.2069C40.9946 53.1108 41.5324 52.4231 41.4367 51.6688ZM53.8893 40.3952C53.7935 39.6372 53.1082 39.101 52.3567 39.1972L22.3598 42.9279C21.6082 43.0204 21.0703 43.7081 21.1661 44.4661C21.2582 45.2204 21.9435 45.7565 22.6988 45.6641L52.6956 41.9333C53.4472 41.8409 53.9814 41.1495 53.8893 40.3952ZM52.7067 30.8187C52.6109 30.0607 51.9256 29.5246 51.174 29.6171L21.1771 33.3515C20.4256 33.4439 19.8877 34.1317 19.9835 34.8897C20.0756 35.6439 20.7609 36.1763 21.5161 36.0839L51.5093 32.3532C52.2646 32.257 52.7988 31.573 52.7067 30.8187ZM51.524 21.2349C51.4282 20.4806 50.743 19.9481 49.9914 20.0406L19.9946 23.7713C19.243 23.8675 18.705 24.5515 18.8008 25.3058C18.8929 26.0638 19.5782 26.5999 20.3335 26.5075L50.3267 22.7767C51.0819 22.6806 51.6161 21.9928 51.524 21.2349Z"
                                                        fill="#9CA3B7"
                                                    />
                                                    <path
                                                        d="M54.4265 11.1516L50.3407 11.6581C48.0786 11.9391 46.0191 10.3307 45.7391 8.06416L45.2344 3.95996L54.4265 11.1516Z"
                                                        fill="#E2E1E0"
                                                    />
                                                    <path
                                                        d="M61.5378 57.5029L55.9599 12.3493L50.5073 13.0259C47.8289 13.6656 45.1431 12.0017 44.5131 9.31735C44.4284 8.9624 44.3805 8.60004 44.3768 8.23399L43.7026 2.76172L15.0688 6.32239C12.8141 6.6034 11.2078 8.67029 11.4878 10.9368L17.9093 62.9271C18.1893 65.1973 20.2488 66.8057 22.5072 66.5247L57.9531 62.1173C60.2115 61.8363 61.8178 59.7694 61.5378 57.5029ZM18.8009 25.3053C18.7051 24.551 19.243 23.8669 19.9946 23.7708L49.9915 20.04C50.7431 19.9476 51.4284 20.48 51.5241 21.2343C51.6162 21.9923 51.082 22.68 50.3268 22.7762L20.3335 26.5069C19.5783 26.5994 18.893 26.0632 18.8009 25.3053ZM19.9835 34.8891C19.8878 34.1311 20.4256 33.4434 21.1772 33.351L51.1741 29.6165C51.9257 29.5241 52.611 30.0602 52.7068 30.8182C52.7989 31.5725 52.2647 32.2565 51.5094 32.3526L21.5162 36.0834C20.7609 36.1758 20.0756 35.6434 19.9835 34.8891ZM40.2431 53.2064L23.8814 55.24C23.1262 55.3361 22.4409 54.7963 22.3488 54.042C22.2567 53.2877 22.7909 52.5963 23.5425 52.5039L39.9041 50.4666C40.6594 50.3741 41.3447 50.9103 41.4368 51.6682C41.5326 52.4225 40.9947 53.1103 40.2431 53.2064ZM52.6957 41.9328L22.6988 45.6635C21.9435 45.756 21.2583 45.2198 21.1662 44.4656C21.0704 43.7076 21.6083 43.0198 22.3599 42.9274L52.3568 39.1966C53.1084 39.1005 53.7936 39.6366 53.8894 40.3946C53.9815 41.1489 53.4473 41.8403 52.6957 41.9328Z"
                                                        fill="#E2E1E0"
                                                    />
                                                    <path
                                                        d="M62.178 35.9689H31.9527C31.1938 35.9689 30.5785 36.5826 30.5785 37.3443C30.5785 38.106 31.1938 38.7234 31.9527 38.7234H62.178C62.937 38.7234 63.5522 38.106 63.5522 37.3443C63.5522 36.5826 62.937 35.9689 62.178 35.9689ZM62.178 45.6192H31.9527C31.1938 45.6192 30.5785 46.2367 30.5785 46.9984C30.5785 47.7601 31.1938 48.3739 31.9527 48.3739H62.178C62.937 48.3739 63.5522 47.7601 63.5522 46.9984C63.5522 46.2367 62.937 45.6192 62.178 45.6192ZM62.178 55.2697H31.9527C31.1938 55.2697 30.5785 55.8871 30.5785 56.6488C30.5785 57.4105 31.1938 58.028 31.9527 58.028H62.178C62.937 58.028 63.5522 57.4105 63.5522 56.6488C63.5522 55.8871 62.937 55.2697 62.178 55.2697ZM48.4396 64.9201H31.9527C31.1938 64.9201 30.5785 65.5376 30.5785 66.2992C30.5785 67.0609 31.1938 67.6784 31.9527 67.6784H48.4396C49.1985 67.6784 49.8138 67.0609 49.8138 66.2992C49.8138 65.5376 49.1985 64.9201 48.4396 64.9201ZM62.178 35.9689H31.9527C31.1938 35.9689 30.5785 36.5826 30.5785 37.3443C30.5785 38.106 31.1938 38.7234 31.9527 38.7234H62.178C62.937 38.7234 63.5522 38.106 63.5522 37.3443C63.5522 36.5826 62.937 35.9689 62.178 35.9689ZM62.178 45.6192H31.9527C31.1938 45.6192 30.5785 46.2367 30.5785 46.9984C30.5785 47.7601 31.1938 48.3739 31.9527 48.3739H62.178C62.937 48.3739 63.5522 47.7601 63.5522 46.9984C63.5522 46.2367 62.937 45.6192 62.178 45.6192ZM62.178 55.2697H31.9527C31.1938 55.2697 30.5785 55.8871 30.5785 56.6488C30.5785 57.4105 31.1938 58.028 31.9527 58.028H62.178C62.937 58.028 63.5522 57.4105 63.5522 56.6488C63.5522 55.8871 62.937 55.2697 62.178 55.2697ZM48.4396 64.9201H31.9527C31.1938 64.9201 30.5785 65.5376 30.5785 66.2992C30.5785 67.0609 31.1938 67.6784 31.9527 67.6784H48.4396C49.1985 67.6784 49.8138 67.0609 49.8138 66.2992C49.8138 65.5376 49.1985 64.9201 48.4396 64.9201ZM48.4396 64.9201H31.9527C31.1938 64.9201 30.5785 65.5376 30.5785 66.2992C30.5785 67.0609 31.1938 67.6784 31.9527 67.6784H48.4396C49.1985 67.6784 49.8138 67.0609 49.8138 66.2992C49.8138 65.5376 49.1985 64.9201 48.4396 64.9201ZM62.178 55.2697H31.9527C31.1938 55.2697 30.5785 55.8871 30.5785 56.6488C30.5785 57.4105 31.1938 58.028 31.9527 58.028H62.178C62.937 58.028 63.5522 57.4105 63.5522 56.6488C63.5522 55.8871 62.937 55.2697 62.178 55.2697ZM62.178 45.6192H31.9527C31.1938 45.6192 30.5785 46.2367 30.5785 46.9984C30.5785 47.7601 31.1938 48.3739 31.9527 48.3739H62.178C62.937 48.3739 63.5522 47.7601 63.5522 46.9984C63.5522 46.2367 62.937 45.6192 62.178 45.6192ZM62.178 35.9689H31.9527C31.1938 35.9689 30.5785 36.5826 30.5785 37.3443C30.5785 38.106 31.1938 38.7234 31.9527 38.7234H62.178C62.937 38.7234 63.5522 38.106 63.5522 37.3443C63.5522 36.5826 62.937 35.9689 62.178 35.9689ZM62.178 35.9689H31.9527C31.1938 35.9689 30.5785 36.5826 30.5785 37.3443C30.5785 38.106 31.1938 38.7234 31.9527 38.7234H62.178C62.937 38.7234 63.5522 38.106 63.5522 37.3443C63.5522 36.5826 62.937 35.9689 62.178 35.9689ZM62.178 45.6192H31.9527C31.1938 45.6192 30.5785 46.2367 30.5785 46.9984C30.5785 47.7601 31.1938 48.3739 31.9527 48.3739H62.178C62.937 48.3739 63.5522 47.7601 63.5522 46.9984C63.5522 46.2367 62.937 45.6192 62.178 45.6192ZM62.178 55.2697H31.9527C31.1938 55.2697 30.5785 55.8871 30.5785 56.6488C30.5785 57.4105 31.1938 58.028 31.9527 58.028H62.178C62.937 58.028 63.5522 57.4105 63.5522 56.6488C63.5522 55.8871 62.937 55.2697 62.178 55.2697ZM48.4396 64.9201H31.9527C31.1938 64.9201 30.5785 65.5376 30.5785 66.2992C30.5785 67.0609 31.1938 67.6784 31.9527 67.6784H48.4396C49.1985 67.6784 49.8138 67.0609 49.8138 66.2992C49.8138 65.5376 49.1985 64.9201 48.4396 64.9201ZM48.4396 64.9201H31.9527C31.1938 64.9201 30.5785 65.5376 30.5785 66.2992C30.5785 67.0609 31.1938 67.6784 31.9527 67.6784H48.4396C49.1985 67.6784 49.8138 67.0609 49.8138 66.2992C49.8138 65.5376 49.1985 64.9201 48.4396 64.9201ZM62.178 55.2697H31.9527C31.1938 55.2697 30.5785 55.8871 30.5785 56.6488C30.5785 57.4105 31.1938 58.028 31.9527 58.028H62.178C62.937 58.028 63.5522 57.4105 63.5522 56.6488C63.5522 55.8871 62.937 55.2697 62.178 55.2697ZM62.178 45.6192H31.9527C31.1938 45.6192 30.5785 46.2367 30.5785 46.9984C30.5785 47.7601 31.1938 48.3739 31.9527 48.3739H62.178C62.937 48.3739 63.5522 47.7601 63.5522 46.9984C63.5522 46.2367 62.937 45.6192 62.178 45.6192ZM62.178 35.9689H31.9527C31.1938 35.9689 30.5785 36.5826 30.5785 37.3443C30.5785 38.106 31.1938 38.7234 31.9527 38.7234H62.178C62.937 38.7234 63.5522 38.106 63.5522 37.3443C63.5522 36.5826 62.937 35.9689 62.178 35.9689ZM31.9527 38.7234H62.178C62.937 38.7234 63.5522 38.106 63.5522 37.3443C63.5522 36.5826 62.937 35.9689 62.178 35.9689H31.9527C31.1938 35.9689 30.5785 36.5826 30.5785 37.3443C30.5785 38.106 31.1938 38.7234 31.9527 38.7234ZM62.178 45.6192H31.9527C31.1938 45.6192 30.5785 46.2367 30.5785 46.9984C30.5785 47.7601 31.1938 48.3739 31.9527 48.3739H62.178C62.937 48.3739 63.5522 47.7601 63.5522 46.9984C63.5522 46.2367 62.937 45.6192 62.178 45.6192ZM62.178 55.2697H31.9527C31.1938 55.2697 30.5785 55.8871 30.5785 56.6488C30.5785 57.4105 31.1938 58.028 31.9527 58.028H62.178C62.937 58.028 63.5522 57.4105 63.5522 56.6488C63.5522 55.8871 62.937 55.2697 62.178 55.2697ZM48.4396 64.9201H31.9527C31.1938 64.9201 30.5785 65.5376 30.5785 66.2992C30.5785 67.0609 31.1938 67.6784 31.9527 67.6784H48.4396C49.1985 67.6784 49.8138 67.0609 49.8138 66.2992C49.8138 65.5376 49.1985 64.9201 48.4396 64.9201ZM71.3627 28.071L59.1238 15.6919C59.0354 15.6068 58.9359 15.5329 58.8217 15.4774C58.6227 15.348 58.3906 15.2852 58.1512 15.2852H29.2043C25.4132 15.2889 22.3406 18.3726 22.3369 22.181V74.5705C22.3406 78.3789 25.4132 81.4626 29.2043 81.4663H64.9227C68.7175 81.4552 71.7901 78.3715 71.7938 74.5668V29.0361C71.7827 28.67 71.628 28.3225 71.3627 28.071ZM59.4296 19.4226L67.6712 27.6939H63.5522C61.2754 27.6939 59.4296 25.8415 59.4296 23.5601V19.4226ZM69.0454 74.5705C69.0454 76.8555 67.1996 78.708 64.9227 78.708H29.2043C26.9311 78.708 25.0853 76.8555 25.0853 74.5705V22.181C25.0853 19.8959 26.9311 18.0435 29.2043 18.0435H58.0554V23.5601C58.0148 23.9225 58.0148 24.2885 58.0554 24.6509C58.3538 27.3944 60.8148 29.3762 63.5522 29.0731H69.0454V74.5705ZM62.178 35.9689H31.9527C31.1938 35.9689 30.5785 36.5826 30.5785 37.3443C30.5785 38.106 31.1938 38.7234 31.9527 38.7234H62.178C62.937 38.7234 63.5522 38.106 63.5522 37.3443C63.5522 36.5826 62.937 35.9689 62.178 35.9689ZM62.178 45.6192H31.9527C31.1938 45.6192 30.5785 46.2367 30.5785 46.9984C30.5785 47.7601 31.1938 48.3739 31.9527 48.3739H62.178C62.937 48.3739 63.5522 47.7601 63.5522 46.9984C63.5522 46.2367 62.937 45.6192 62.178 45.6192ZM62.178 55.2697H31.9527C31.1938 55.2697 30.5785 55.8871 30.5785 56.6488C30.5785 57.4105 31.1938 58.028 31.9527 58.028H62.178C62.937 58.028 63.5522 57.4105 63.5522 56.6488C63.5522 55.8871 62.937 55.2697 62.178 55.2697ZM48.4396 64.9201H31.9527C31.1938 64.9201 30.5785 65.5376 30.5785 66.2992C30.5785 67.0609 31.1938 67.6784 31.9527 67.6784H48.4396C49.1985 67.6784 49.8138 67.0609 49.8138 66.2992C49.8138 65.5376 49.1985 64.9201 48.4396 64.9201ZM48.4396 64.9201H31.9527C31.1938 64.9201 30.5785 65.5376 30.5785 66.2992C30.5785 67.0609 31.1938 67.6784 31.9527 67.6784H48.4396C49.1985 67.6784 49.8138 67.0609 49.8138 66.2992C49.8138 65.5376 49.1985 64.9201 48.4396 64.9201ZM62.178 55.2697H31.9527C31.1938 55.2697 30.5785 55.8871 30.5785 56.6488C30.5785 57.4105 31.1938 58.028 31.9527 58.028H62.178C62.937 58.028 63.5522 57.4105 63.5522 56.6488C63.5522 55.8871 62.937 55.2697 62.178 55.2697ZM62.178 45.6192H31.9527C31.1938 45.6192 30.5785 46.2367 30.5785 46.9984C30.5785 47.7601 31.1938 48.3739 31.9527 48.3739H62.178C62.937 48.3739 63.5522 47.7601 63.5522 46.9984C63.5522 46.2367 62.937 45.6192 62.178 45.6192ZM62.178 35.9689H31.9527C31.1938 35.9689 30.5785 36.5826 30.5785 37.3443C30.5785 38.106 31.1938 38.7234 31.9527 38.7234H62.178C62.937 38.7234 63.5522 38.106 63.5522 37.3443C63.5522 36.5826 62.937 35.9689 62.178 35.9689ZM62.178 35.9689H31.9527C31.1938 35.9689 30.5785 36.5826 30.5785 37.3443C30.5785 38.106 31.1938 38.7234 31.9527 38.7234H62.178C62.937 38.7234 63.5522 38.106 63.5522 37.3443C63.5522 36.5826 62.937 35.9689 62.178 35.9689ZM62.178 45.6192H31.9527C31.1938 45.6192 30.5785 46.2367 30.5785 46.9984C30.5785 47.7601 31.1938 48.3739 31.9527 48.3739H62.178C62.937 48.3739 63.5522 47.7601 63.5522 46.9984C63.5522 46.2367 62.937 45.6192 62.178 45.6192ZM62.178 55.2697H31.9527C31.1938 55.2697 30.5785 55.8871 30.5785 56.6488C30.5785 57.4105 31.1938 58.028 31.9527 58.028H62.178C62.937 58.028 63.5522 57.4105 63.5522 56.6488C63.5522 55.8871 62.937 55.2697 62.178 55.2697ZM48.4396 64.9201H31.9527C31.1938 64.9201 30.5785 65.5376 30.5785 66.2992C30.5785 67.0609 31.1938 67.6784 31.9527 67.6784H48.4396C49.1985 67.6784 49.8138 67.0609 49.8138 66.2992C49.8138 65.5376 49.1985 64.9201 48.4396 64.9201ZM48.4396 64.9201H31.9527C31.1938 64.9201 30.5785 65.5376 30.5785 66.2992C30.5785 67.0609 31.1938 67.6784 31.9527 67.6784H48.4396C49.1985 67.6784 49.8138 67.0609 49.8138 66.2992C49.8138 65.5376 49.1985 64.9201 48.4396 64.9201ZM62.178 55.2697H31.9527C31.1938 55.2697 30.5785 55.8871 30.5785 56.6488C30.5785 57.4105 31.1938 58.028 31.9527 58.028H62.178C62.937 58.028 63.5522 57.4105 63.5522 56.6488C63.5522 55.8871 62.937 55.2697 62.178 55.2697ZM62.178 45.6192H31.9527C31.1938 45.6192 30.5785 46.2367 30.5785 46.9984C30.5785 47.7601 31.1938 48.3739 31.9527 48.3739H62.178C62.937 48.3739 63.5522 47.7601 63.5522 46.9984C63.5522 46.2367 62.937 45.6192 62.178 45.6192ZM62.178 35.9689H31.9527C31.1938 35.9689 30.5785 36.5826 30.5785 37.3443C30.5785 38.106 31.1938 38.7234 31.9527 38.7234H62.178C62.937 38.7234 63.5522 38.106 63.5522 37.3443C63.5522 36.5826 62.937 35.9689 62.178 35.9689ZM62.178 35.9689H31.9527C31.1938 35.9689 30.5785 36.5826 30.5785 37.3443C30.5785 38.106 31.1938 38.7234 31.9527 38.7234H62.178C62.937 38.7234 63.5522 38.106 63.5522 37.3443C63.5522 36.5826 62.937 35.9689 62.178 35.9689ZM62.178 45.6192H31.9527C31.1938 45.6192 30.5785 46.2367 30.5785 46.9984C30.5785 47.7601 31.1938 48.3739 31.9527 48.3739H62.178C62.937 48.3739 63.5522 47.7601 63.5522 46.9984C63.5522 46.2367 62.937 45.6192 62.178 45.6192ZM62.178 55.2697H31.9527C31.1938 55.2697 30.5785 55.8871 30.5785 56.6488C30.5785 57.4105 31.1938 58.028 31.9527 58.028H62.178C62.937 58.028 63.5522 57.4105 63.5522 56.6488C63.5522 55.8871 62.937 55.2697 62.178 55.2697ZM48.4396 64.9201H31.9527C31.1938 64.9201 30.5785 65.5376 30.5785 66.2992C30.5785 67.0609 31.1938 67.6784 31.9527 67.6784H48.4396C49.1985 67.6784 49.8138 67.0609 49.8138 66.2992C49.8138 65.5376 49.1985 64.9201 48.4396 64.9201Z"
                                                        fill="#9CA3B7"
                                                    />
                                                    <path
                                                        d="M67.6713 27.6941H63.5523C61.2755 27.6941 59.4297 25.8417 59.4297 23.5603V19.4229L67.6713 27.6941Z"
                                                        fill="white"
                                                    />
                                                    <path
                                                        d="M63.5529 29.0735C60.8155 29.3767 58.3544 27.3949 58.056 24.6513C58.0155 24.289 58.0155 23.923 58.056 23.5606V18.0439H29.2049C26.9317 18.0439 25.0859 19.8964 25.0859 22.1815V74.571C25.0859 76.856 26.9317 78.7085 29.2049 78.7085H64.9234C67.2002 78.7085 69.046 76.856 69.046 74.571V29.0735H63.5529ZM48.4402 67.6789H31.9533C31.1944 67.6789 30.5791 67.0614 30.5791 66.2997C30.5791 65.5381 31.1944 64.9206 31.9533 64.9206H48.4402C49.1992 64.9206 49.8144 65.5381 49.8144 66.2997C49.8144 67.0614 49.1992 67.6789 48.4402 67.6789ZM62.1786 58.0285H31.9533C31.1944 58.0285 30.5791 57.411 30.5791 56.6493C30.5791 55.8876 31.1944 55.2702 31.9533 55.2702H62.1786C62.9376 55.2702 63.5529 55.8876 63.5529 56.6493C63.5529 57.411 62.9376 58.0285 62.1786 58.0285ZM62.1786 48.3744H31.9533C31.1944 48.3744 30.5791 47.7606 30.5791 46.9989C30.5791 46.2372 31.1944 45.6197 31.9533 45.6197H62.1786C62.9376 45.6197 63.5529 46.2372 63.5529 46.9989C63.5529 47.7606 62.9376 48.3744 62.1786 48.3744ZM62.1786 38.7239H31.9533C31.1944 38.7239 30.5791 38.1065 30.5791 37.3448C30.5791 36.5831 31.1944 35.9693 31.9533 35.9693H62.1786C62.9376 35.9693 63.5529 36.5831 63.5529 37.3448C63.5529 38.1065 62.9376 38.7239 62.1786 38.7239Z"
                                                        fill="white"
                                                    />
                                                    <path
                                                        d="M67.1001 63.6563C66.0685 61.2899 64.828 62.6701 64.3369 63.6563V72.1605C64.7053 74.3789 67.1001 73.6394 67.1001 72.1605V63.6563Z"
                                                        fill="#FCCA26"
                                                        stroke="#FCCA26"
                                                    />
                                                    <path
                                                        d="M67.1738 77.2067C67.1738 77.9906 66.5401 78.6303 65.7553 78.6303C64.9706 78.6303 64.3369 77.9943 64.3369 77.2067C64.3369 76.4192 64.9706 75.7832 65.7553 75.7832C66.5401 75.7832 67.1738 76.4192 67.1738 77.2067Z"
                                                        fill="#FCCA26"
                                                    />
                                                </g>
                                                <rect
                                                    x="53"
                                                    y="58"
                                                    width="26"
                                                    height="26"
                                                    rx="13"
                                                    fill="white"
                                                />
                                                <rect
                                                    x="53"
                                                    y="58"
                                                    width="26"
                                                    height="26"
                                                    rx="13"
                                                    stroke="#257556"
                                                    stroke-width="2"
                                                />
                                                <path
                                                    d="M66 64.8125V77.1875M72.1875 71H59.8125"
                                                    stroke="#257556"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_281_11416">
                                                    <rect
                                                        width="84"
                                                        height="88"
                                                        fill="white"
                                                    />
                                                </clipPath>
                                            </defs>
                                        </svg>

                                        <span class="mt-4 text-xl"
                                            >The round is empty!</span
                                        >

                                        <!-- Add round button start -->
                                        <!-- <Link
                                            :href="
                                                route(
                                                    'survey2.create',
                                                    props.survey
                                                )
                                            "
                                            v-if="
                                                userPermissions.canUpdateSurvey
                                            "
                                        > -->
                                        <div class="relative group">
                                            <button
                                                @click.prevent="
                                                    openTrialModeSelection()
                                                "
                                                class="mt-2 bg-[#257556] text-white hover:bg-[#1d5e45] text-[20px] flex items-center rounded-lg text-center px-4 py-2 gap-2 group overflow-hidden relative hidden"
                                            >
                                                Add Round
                                            </button>
                                            <JetButton
                                                class="mt-4 rounded-md py-2 px-4 italic text-sm shadow-xl"
                                                @click.prevent="
                                                    openTrialModeSelection()
                                                "
                                                variant="primary"
                                                size="md"
                                            >
                                                <svg
                                                    data-v-2da10074=""
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke-width="2"
                                                    stroke="currentColor"
                                                    class="w-3.5 h-3.5 mr-1 self-center"
                                                >
                                                    <path
                                                        data-v-2da10074=""
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M12 4.5v15m7.5-7.5h-15"
                                                    ></path>
                                                </svg>
                                                Add Round
                                            </JetButton>
                                            <div
                                                class="absolute bottom-full left-[-153px] mb-1.5 px-4 leading-none rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                                role="tooltip"
                                            >
                                                <p>
                                                    Rounds represent blocks of
                                                    trials like levels or
                                                    segments in your flow.
                                                </p>
                                                <p>
                                                    Each round can contain
                                                    unique primes, targets, and
                                                    behaviors.
                                                </p>
                                            </div>
                                        </div>
                                        <!-- </Link> -->
                                        <!-- Add round button start -->
                                    </div>
                                    <!-- Add Round End -->
                                </div>
                            </div>

                            <!-- Round Display modal -->
                            <div
                                class="my-3 bg-white border shadow rounded-xl px-4"
                                v-for="(test, i) in tests"
                                :key="i"
                            >
                                <div
                                    class="py-3 px-2 lg:flex lg:items-center lg:justify-between"
                                >
                                    <!-- Round Number and Type -->
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="flex flex-col mt-1 text-gray-700 sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-2"
                                        >
                                            <div
                                                class="flex items-center text-sm rounded-lg bg-slate-700 text-white px-2 py-1 font-medium shadow-md"
                                            >
                                                R# {{ i + 1 }}
                                            </div>

                                            <template
                                                v-if="
                                                    test.config.type ==
                                                    'descriptive'
                                                "
                                            >
                                                <span
                                                    class="flex items-center text-sm rounded-lg bg-green-500/20 text-green-500 px-2 font-medium"
                                                >
                                                    Descriptive
                                                </span>
                                            </template>

                                            <template
                                                v-if="
                                                    test.config.type == 'trial'
                                                "
                                            >
                                                <span
                                                    class="flex items-center px-2 text-sm font-medium rounded-lg"
                                                    :class="
                                                        test.config.main_trial
                                                            ? 'bg-blue-500/20 text-blue-500'
                                                            : 'bg-red-500/20 text-red-500'
                                                    "
                                                >
                                                    {{
                                                        test.config.main_trial
                                                            ? "Main Round"
                                                            : "Practice Round"
                                                    }}
                                                </span>
                                            </template>

                                            <!-- <div class="flex items-center text-sm rounded-lg bg-[#257556] text-white  px-2 font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-auto h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    &nbsp;<em>{{ test.editor.name }}</em>&nbsp;{{ test.updated_readable }}
                                </div> -->
                                        </div>
                                    </div>

                                    <!-- label if round has skip logic -->
                                    <div
                                        v-if="
                                            test.config.conditions &&
                                            test.config.conditions.length > 0
                                        "
                                        class="items-center bg-[#25755620] px-2 py-1 flex justify-center rounded-lg mr-4 mb-0.5"
                                    >
                                        <span class="text-sm text-[#257556]">
                                            {{ test.config.conditions[0].var }}
                                            {{
                                                test.config.conditions[0]
                                                    .operator
                                            }}
                                            {{
                                                test.config.conditions[0].val
                                            }}</span
                                        >
                                    </div>

                                    <!-- Dropdown Button -->
                                    <div
                                        class="relative inline-block text-left"
                                    >
                                        <button
                                            @click.stop="toggleDropdown(i)"
                                            class="p-1 rounded-lg bg-gray-100 hover:bg-gray-200"
                                        >
                                            <svg
                                                width="25"
                                                height="25"
                                                viewBox="0 0 25 25"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <path
                                                    d="M4.29688 7.42188H20.7031M4.29688 12.5H20.7031M4.29688 17.5781H20.7031"
                                                    stroke="black"
                                                    stroke-width="2"
                                                    stroke-miterlimit="10"
                                                    stroke-linecap="round"
                                                />
                                            </svg>
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div
                                            v-show="openDropdown === i"
                                            class="absolute right-0 z-50 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg w-44"
                                        >
                                            <!-- Update -->
                                            <Link
                                                :href="
                                                    route('survey2.update', [
                                                        survey,
                                                        test,
                                                    ])
                                                "
                                                v-if="
                                                    userPermissions.canUpdateSurvey
                                                "
                                                class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                                    />
                                                </svg>
                                                Update
                                            </Link>

                                            <!-- Copy -->
                                            <button
                                                @click="
                                                    confirmingTrialCopyTo = test
                                                "
                                                v-if="
                                                    userPermissions.canUpdateSurvey
                                                "
                                                class="flex items-center w-full gap-2 px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                                    />
                                                </svg>
                                                Duplicate
                                            </button>

                                            <!-- Move -->
                                            <button
                                                @click="moveTrialDialog(test)"
                                                v-if="
                                                    userPermissions.canUpdateSurvey
                                                "
                                                class="flex items-center w-full gap-2 px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-4"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                                Move
                                            </button>

                                            <!-- Preview -->
                                            <a
                                                @click="
                                                    matchedVariables.length > 0
                                                        ? ((variablesModal = true),
                                                          (specificTest = true),
                                                          (selectedSpecificTest =
                                                              test.id))
                                                        : null
                                                "
                                                :href="
                                                    matchedVariables.length > 0
                                                        ? null
                                                        : engine_url +
                                                          survey.id +
                                                          '&id=test' +
                                                          '&p_tid=' +
                                                          test.id
                                                "
                                                target="_blank"
                                                class="flex flex-col w-full uppercase"
                                            >
                                                <button
                                                    class="flex items-center w-full gap-2 px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100"
                                                >
                                                    <svg
                                                        width="21"
                                                        height="20"
                                                        viewBox="0 0 21 20"
                                                        fill="none"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                    >
                                                        <g
                                                            clip-path="url(#clip0_620_14998)"
                                                        >
                                                            <path
                                                                d="M10.8246 4.375C7.78012 4.375 4.65707 6.13711 2.19847 9.66133C2.1283 9.76303 2.08984 9.88325 2.08796 10.0068C2.08609 10.1303 2.12087 10.2517 2.18793 10.3555C4.07699 13.3125 7.15824 15.625 10.8246 15.625C14.4512 15.625 17.5957 13.3055 19.4883 10.3418C19.5538 10.24 19.5886 10.1216 19.5886 10.0006C19.5886 9.87958 19.5538 9.76113 19.4883 9.65937C17.5914 6.72969 14.4239 4.375 10.8246 4.375Z"
                                                                stroke="#394353"
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                            />
                                                            <path
                                                                d="M10.8379 13.125C12.5638 13.125 13.9629 11.7259 13.9629 10C13.9629 8.27411 12.5638 6.875 10.8379 6.875C9.112 6.875 7.71289 8.27411 7.71289 10C7.71289 11.7259 9.112 13.125 10.8379 13.125Z"
                                                                stroke="#394353"
                                                                stroke-miterlimit="10"
                                                            />
                                                        </g>
                                                        <defs>
                                                            <clipPath
                                                                id="clip0_620_14998"
                                                            >
                                                                <rect
                                                                    width="20"
                                                                    height="20"
                                                                    fill="white"
                                                                    transform="translate(0.837891)"
                                                                />
                                                            </clipPath>
                                                        </defs>
                                                    </svg>

                                                    Preview
                                                </button>
                                            </a>

                                            <!-- Delete -->
                                            <button
                                                @click="selectedTest = test"
                                                v-if="
                                                    userPermissions.canUpdateSurvey
                                                "
                                                class="flex items-center w-full gap-2 px-4 py-2 text-sm text-left text-red-600 hover:bg-gray-100"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    />
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Round Title start -->
                                <div
                                    class="mx-2 overflow-auto border lg:overflow-visible sm:rounded-lg"
                                    v-if="test.config.title"
                                    :class="{
                                        'mb-4': hasRenderableContent(
                                            test.config.main_header
                                        ),
                                    }"
                                >
                                    <div
                                        class="text-sm font-bold text-base mb-3 bg-[#25755610] py-1.5 rounded-t-md text-center uppercase"
                                    >
                                        Round Title
                                    </div>
                                    <div
                                        class="text-sm header pt-2 p-6 text-gray-800 font-normal set-strong"
                                        v-html="proxiedHtml(test.config.title)"
                                    ></div>
                                </div>
                                <!-- Round Ttitle end -->

                                <!-- Header start -->
                                <div
                                    class="mx-2 pb-6 overflow-auto border mb-3 lg:overflow-visible sm:rounded-lg"
                                    v-if="
                                        hasRenderableContent(
                                            test.config.main_header
                                        )
                                    "
                                >
                                    <div
                                        class="text-sm font-bold text-base mb-3 bg-[#25755610] py-1.5 rounded-t-md text-center uppercase"
                                    >
                                        Header
                                    </div>
                                    <div
                                        class="text-sm header min-h-[3vmin] font-normal pt-2 px-6 text-gray-800 set-strong"
                                        v-html="
                                            proxiedHtml(test.config.main_header)
                                        "
                                    ></div>
                                </div>
                                <!-- Header end -->

                                <!-- Primes Start -->
                                <div
                                    class="m-2 my-0 text-center border mb-3 rounded-lg"
                                    v-if="test.primes.length > 0"
                                >
                                    <div
                                        class="text-sm font-bold text-base mb-3 bg-[#25755610] py-1.5 uppercase"
                                    >
                                        Primes
                                        <span
                                            class="text-sm font-normal text-red-500"
                                            >({{ test.primes.length }})</span
                                        >
                                    </div>

                                    <div
                                        class="p-3 mt-2 max-h-[20vh] overflow-auto"
                                    >
                                        <table
                                            class="min-w-full text-left divide-y divide-gray-100 table-fixed"
                                            v-if="test.primes.length > 0"
                                        >
                                            <thead>
                                                <tr>
                                                    <th
                                                        class="px-6 py-3 w-1/12 text-xs font-normal tracking-wider text-left text-gray-500 uppercase"
                                                    >
                                                        #
                                                    </th>
                                                    <th
                                                        class="px-6 py-3 w-1/12 text-xs font-normal tracking-wider text-left text-gray-500 uppercase"
                                                    >
                                                        Index
                                                    </th>
                                                    <th
                                                        class="px-6 py-3 w-2/12 text-xs font-normal tracking-wider text-left text-gray-500 uppercase"
                                                    >
                                                        Prime
                                                    </th>
                                                    <th
                                                        class="px-6 py-3 w-2/12 text-xs font-normal tracking-wider text-left text-gray-500 uppercase"
                                                    >
                                                        Equivalent
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody
                                                class="bg-white divide-y divide-gray-100"
                                            >
                                                <tr
                                                    class="hover:bg-gray-50"
                                                    v-for="(
                                                        prime, i
                                                    ) in test.primes"
                                                    :key="i"
                                                >
                                                    <td
                                                        class="px-6 py-2 text-sm font-medium text-gray-500 whitespace-nowrap"
                                                    >
                                                        {{ i + 1 }}
                                                    </td>
                                                    <td
                                                        class="px-6 py-2 text-sm font-medium text-gray-500 whitespace-nowrap"
                                                    >
                                                        {{ prime.index }}
                                                    </td>
                                                    <td
                                                        class="px-6 py-3 text-sm font-normal text-gray-600"
                                                        :class="{
                                                            'w-[7rem]':
                                                                hasImage(
                                                                    prime.prime
                                                                ),

                                                            'flex justify-center items-start':
                                                                prime.config
                                                                    .conditions
                                                                    .length > 0,
                                                        }"
                                                    >
                                                        <div
                                                            class="pr-2 -mt-0.5"
                                                            v-if="
                                                                prime.hasOwnProperty(
                                                                    'config'
                                                                )
                                                            "
                                                        >
                                                            <div
                                                                v-if="
                                                                    prime
                                                                        ?.config
                                                                        ?.conditions
                                                                        .length >
                                                                    0
                                                                "
                                                                aria-label="status"
                                                                class="status status-secondary"
                                                            ></div>
                                                        </div>
                                                        <span
                                                            class="font-normal text-gray-800 set-strong [&>*]:inline [&>*]:m-0"
                                                            v-html="
                                                                proxiedHtml(
                                                                    prime.prime
                                                                )
                                                            "
                                                        >
                                                        </span>
                                                    </td>
                                                    <td
                                                        class="px-6 py-2 text-sm font-normal text-gray-700"
                                                    >
                                                        {{ prime.equivalent }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- Primes End -->

                                <!-- Targets Start -->
                                <div
                                    class="m-2 my-0 text-center border sm:rounded-lg"
                                    v-if="test.targets.length > 0"
                                >
                                    <div
                                        class="text-sm font-bold text-base mb-3 bg-[#25755610] py-1.5 uppercase"
                                    >
                                        Targets
                                        <span
                                            class="text-sm font-normal text-red-500"
                                            >({{ test.targets.length }})</span
                                        >
                                    </div>

                                    <div
                                        class="overflow-y-auto overflow-x-hidden mt-3 pt-2 px-2 max-h-[30vh]"
                                    >
                                        <table
                                            class="min-w-full text-left divide-y divide-gray-100 table-fixed"
                                            v-if="test.targets.length > 0"
                                        >
                                            <thead>
                                                <tr>
                                                    <th
                                                        class="px-6 py-3 w-1/12 text-xs font-normal tracking-wider text-left text-gray-500 uppercase"
                                                    >
                                                        #
                                                    </th>
                                                    <th
                                                        class="px-6 py-3 w-1/12 text-xs font-normal tracking-wider text-left text-gray-500 uppercase"
                                                    >
                                                        Index
                                                    </th>
                                                    <th
                                                        class="px-6 py-3 w-2/12 text-xs font-normal tracking-wider text-left text-gray-500 uppercase"
                                                    >
                                                        Target
                                                    </th>
                                                    <th
                                                        class="px-6 py-3 w-2/12 text-xs font-normal tracking-wider text-left text-gray-500 uppercase"
                                                    >
                                                        Equivalent
                                                    </th>
                                                    <th
                                                        class="px-6 py-3 w-1/12 text-xs font-normal tracking-wider text-left text-gray-500 uppercase"
                                                    >
                                                        Value
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody
                                                class="bg-white divide-y divide-gray-100"
                                            >
                                                <tr
                                                    class="hover:bg-gray-50"
                                                    v-for="(
                                                        target, i
                                                    ) in test.targets"
                                                    :key="'tr' + i"
                                                >
                                                    <td
                                                        class="px-6 py-3 text-sm font-medium text-gray-500 whitespace-nowrap"
                                                    >
                                                        {{ i + 1 }}
                                                    </td>

                                                    <td
                                                        class="px-6 py-3 text-sm font-medium text-gray-500 whitespace-nowrap"
                                                    >
                                                        {{ target.index }}
                                                    </td>

                                                    <td
                                                        class="px-6 py-3 text-sm font-medium"
                                                        :class="{
                                                            'w-[7rem]':
                                                                hasImage(
                                                                    target.target
                                                                ),
                                                        }"
                                                    >
                                                        <div
                                                            class="text-gray-800 font-normal set-strong [&>*]:inline [&>*]:m-0"
                                                            v-html="
                                                                proxiedHtml(
                                                                    target.target
                                                                )
                                                            "
                                                        ></div>
                                                    </td>

                                                    <td
                                                        class="px-6 py-3 text-sm font-normal text-gray-700"
                                                    >
                                                        {{ target.equivalent }}
                                                        <span
                                                            class="ml-1"
                                                            v-if="
                                                                target.can_unselect
                                                            "
                                                        >
                                                            <span
                                                                class="text-xs py-0.5 px-2 bg-[#257556] rounded-full text-white"
                                                                >Unselect</span
                                                            >
                                                        </span>
                                                    </td>

                                                    <td
                                                        class="px-6 py-3 text-sm font-medium text-gray-600"
                                                    >
                                                        {{ target.value }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- Targets End -->

                                <div
                                    class="flex items-center p-3 text-xs font-normal text-gray-400 rounded-lg"
                                >
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
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    &nbsp;Last Modified by&nbsp;<span
                                        class="font-bold"
                                        >{{ test.editor.name }}</span
                                    >&nbsp;{{ formatTestUpdated(test) }}
                                </div>

                                <!-- bottom text start -->
                                <!-- <div class="px-4 pb-4 m-2 mx-2 my-1 mt-2 mb-2 overflow-auto text-center border border-gray-500 lg:overflow-visible sm:rounded-lg" v-if="test.config.bottom_text">
                        <u>Bottom text</u><br>
                        <span class="text-xs header" v-html="proxiedHtml(test.config.bottom_text)"></span>
                    </div> -->
                                <!-- bottom text end -->
                            </div>
                        </div>
                    </div>
                    <!-- Content End -->
                </div>
            </div>

            <!-- Body End -->

            <!--     
            <div class="h-[70vh] flex items-center" v-if="tests.length === 0">
                <div class="mx-auto font-medium prose-2xl text-gray-600">
                    No trial found
                </div>
            </div> -->
        </div>
        <Confirm
            :show="selectedTest"
            @confirmed="deleteTrial"
            @cancelled="selectedTest = false"
        >
            <template #header> Delete Round </template>
            <template #body>
                Are you sure you want to delete this round?
            </template>
        </Confirm>
        <JetDialogModal :show="testToBeCopied" @close="testToBeCopied = false">
            <template #title> Copy Trial </template>

            <template #content>
                Are you sure you want to copy this trial?
            </template>

            <template #footer>
                <JetButton
                    variant="default"
                    class="uppercase"
                    @click="testToBeCopied = false"
                    size="sm"
                >
                    nevermind
                </JetButton>

                <jet-button
                    size="sm"
                    type="button"
                    @click="copyTrial"
                    class="ml-2 uppercase"
                    variant="primary"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Copy Trial
                </jet-button>
            </template>
        </JetDialogModal>

        <JetDialogModal
            :show="confirmingTrialCopyTo"
            @close="confirmingTrialCopyTo = false"
        >
            <template #title> Copy to other survey </template>

            <template #content>
                <jet-label value="Survey"></jet-label>
                <select
                    class="block w-full px-2 text-gray-900 bg-transparent border-gray-300 rounded-lg appearance-none border-1 focus:outline-none focus:ring-0 focus:border-blue-600 peer xs:w-full"
                    v-model="selectedSurvey"
                >
                    <option
                        v-for="survey in filteredSurveysForCopy"
                        :value="survey.id"
                        :key="survey.id"
                    >
                        {{ survey.name }}
                    </option>
                </select>
            </template>
            <template #footer>
                <jet-button
                    variant="default"
                    size="sm"
                    @click="confirmingTrialCopyTo = false"
                    class="uppercase"
                >
                    nevermind
                </jet-button>

                <jet-button
                    variant="primary"
                    size="sm"
                    @click="copyTrial(false)"
                    class="ml-2 uppercase"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    copy trial
                </jet-button>
            </template>
        </JetDialogModal>

        <JetDialogModal
            :show="confirmingTrialMove"
            @close="confirmingTrialMove = false"
        >
            <template #title> Move round order to </template>

            <template #content>
                <select
                    class="w-full form-select xs:w-full"
                    v-model="selectedPart"
                >
                    <option
                        v-for="test in tests"
                        :value="test.part"
                        :key="test.id"
                    >
                        Round {{ test.part }}
                    </option>
                </select>
            </template>
            <template #footer>
                <jet-secondary-button @click="confirmingTrialMove = false">
                    NEVERMIND
                </jet-secondary-button>

                <jet-button
                    type="button"
                    @click="moveTrial"
                    class="ml-2"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    MOVE ROUND
                </jet-button>
            </template>
        </JetDialogModal>

        <!-- Trial Mode Selection Modal -->
        <JetDialogModal
            :show="showTrialModeModal"
            @close="showTrialModeModal = false"
            :closeable="true"
        >
            <template #title>Select Round Mode</template>

            <template #content>
                <div class="space-y-4">
                    <p class="text-gray-600 text-sm">
                        Choose the type of trial you want to create:
                    </p>

                    <div class="grid grid-cols-1 gap-3">
                        <button
                            @click="selectTrialMode('descriptive')"
                            class="p-4 text-left border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors"
                        >
                            <div class="font-semibold text-green-600">
                                Descriptive Round
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                Add a round with just a title-providing context
                                or information. No interactive elements
                                included.
                            </div>
                        </button>

                        <button
                            @click="selectTrialMode('practice')"
                            class="p-4 text-left border-2 border-gray-200 rounded-lg hover:border-red-500 hover:bg-red-50 transition-colors"
                        >
                            <div class="font-semibold text-red-600">
                                Practice Round
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                Set up a practice round with all configuration
                                options and interactions-perfect for testing
                                before the main round.
                            </div>
                        </button>

                        <button
                            @click="selectTrialMode('main')"
                            class="p-4 text-left border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors"
                        >
                            <div class="font-semibold text-blue-600">
                                Main Round
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                Create a main round with full configuration
                                options and interactive elements for the actual
                                run.
                            </div>
                        </button>
                    </div>
                </div>
            </template>

            <template #footer>
                <JetButton
                    variant="default"
                    @click="showTrialModeModal = false"
                    size="sm"
                >
                    CANCEL
                </JetButton>
            </template>
        </JetDialogModal>

        <!-- <JetDialogModal
            :show="settingModal"
            @close="settingModal = false"
            maxWidth="7xl"
        >
            <template #title> Settings </template>
            <template #content>
                <SettingsForm :survey="survey" />
            </template>
            <template #footer>
                <jet-secondary-button @click="settingModal = false">
                    nevermind
                </jet-secondary-button>

                <jet-button
                    type="button"
                    @click="saveSettings"
                    class="ml-2"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    v-if="userPermissions.canUpdateSurvey"
                >
                    save settings
                </jet-button>
            </template>
        </JetDialogModal> -->

        <!-- Settings Round -->
        <!-- Header content -->
        <JetDialogModal
            :show="settingModal"
            @close="settingModal = false"
            maxWidth="7xl"
        >
            <template #title> Global Settings </template>

            <!-- Body content -->
            <template #content>
                <SettingsForm
                    :survey="survey"
                    @update:btn_delay="
                        (val) => {
                            handleButtonDelayChange(val);
                        }
                    "
                />
            </template>

            <!-- Footer content -->
            <template #footer>
                <JetButton
                    @click="settingModal = false"
                    size="sm"
                    variant="default"
                    class="px-6 uppercase"
                >
                    nevermind
                </JetButton>
                <jet-button
                    type="button"
                    v-if="userPermissions.canUpdateSurvey"
                    variant="primary"
                    size="sm"
                    @click="saveSettings"
                    class="px-6 ml-2 uppercase"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    save settings
                </jet-button>
            </template>
        </JetDialogModal>

        <!-- Test Variables Preview Modal -->
        <JetDialogModal
            :show="variablesModal"
            @close="variablesModal = false"
            maxWidth="2xl"
        >
            <template #title> Select Variables Preview </template>

            <!-- Body content -->
            <template #content>
                <div
                    class="space-y-4 overflow-y-auto pr-1 text-sm max-h-[70vmin]"
                >
                    <div
                        v-for="(variable, indexVariable) in matchedVariables"
                        :key="variable.id"
                        class="border border-gray-200 rounded-lg p-3 bg-gray-50"
                    >
                        <div>
                            <div class="font-semibold text-left mb-3 mt-2">
                                <span
                                    class="bg-[#257556] text-white text-md py-1 px-4 rounded-lg"
                                    >{{ variable.name }}</span
                                >
                            </div>
                            <ul class="list-disc list-inside text-gray-700">
                                <a
                                    class="my-1 block hover:text-[#257556] hover:underline cursor-pointer text-lg"
                                    v-for="(
                                        criterion, index
                                    ) in (variable.config &&
                                        variable.config.criteria) ||
                                    []"
                                    :key="criterion.id || criterion.label"
                                    :href="
                                        engine_url +
                                        survey.id +
                                        '&id=test&brc=' +
                                        (index + 1) +
                                        '&p_tid=' +
                                        (specificTest
                                            ? selectedSpecificTest
                                            : 0)
                                    "
                                    target="_blank"
                                >
                                    <div class="flex items-baseline gap-2">
                                        <span
                                            class="text-gray-400 text-md font-mono"
                                        >
                                            (brc={{ index + 1 }})
                                        </span>
                                        <span>
                                            {{ criterion.equivalent }}
                                        </span>
                                    </div>
                                </a>
                            </ul>
                        </div>
                    </div>
                </div>
            </template>
            <!-- Footer content -->
            <template #footer>
                <JetButton
                    @click="variablesModal = false"
                    size="sm"
                    variant="default"
                    class="uppercase"
                >
                    nevermind
                </JetButton>
            </template>
        </JetDialogModal>

        <!-- Themes Modal -->
        <!-- Header content -->
        <JetDialogModal
            :show="themesModal"
            @close="themesModal = false"
            maxWidth="7xl"
        >
            <template #title> Themes </template>

            <!-- Body content -->
            <template #content>
                <ThemesForm :survey="survey" />
            </template>

            <!-- Footer content -->
            <template #footer>
                <JetButton
                    @click="themesModal = false"
                    size="sm"
                    variant="default"
                    class="px-6 uppercase"
                >
                    nevermind
                </JetButton>
                <jet-button
                    type="button"
                    v-if="userPermissions.canUpdateSurvey"
                    variant="primary"
                    size="sm"
                    @click="saveSettingsthemes"
                    class="px-6 ml-2 uppercase"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    save themes
                </jet-button>
            </template>
        </JetDialogModal>

        <!-- Advance Modal -->
        <!-- Header content -->
        <JetDialogModal
            :show="advanceModal"
            @close="advanceModal = false"
            maxWidth="7xl"
        >
            <template #title> Advance </template>

            <!-- Body content -->
            <template #content>
                <AdvanceForm :survey="survey" />
            </template>

            <!-- Footer content -->
            <template #footer>
                <JetButton
                    @click="advanceModal = false"
                    size="sm"
                    variant="default"
                    class="px-6 uppercase"
                >
                    nevermind
                </JetButton>
                <jet-button
                    type="button"
                    v-if="userPermissions.canUpdateSurvey"
                    variant="primary"
                    size="sm"
                    @click="saveSettingsadvance"
                    class="px-6 ml-2 uppercase"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    save advance
                </jet-button>
            </template>
        </JetDialogModal>

        <!-- Add Round Modal -->
        <!-- Header content -->
        <JetDialogModal
            :show="addRoundModal"
            @close="addRoundModal = false"
            maxWidth="7xl"
        >
            <template #title></template>

            <!-- Body content -->
            <template #content>
                <AddRoundForm
                    :templatePrimes="templatePrimes"
                    :templateTargets="templateTargets"
                    :project="project"
                    :survey="survey"
                    :selectedTrialMode="selectedTrialMode"
                />
            </template>

            <!-- Footer content -->
            <template #footer>
                <!-- <JetButton
                    @click="addRoundModal = false"
                    size="sm"
                    variant="default"
                    class="px-6 uppercase"
                >
                    nevermind
                </JetButton> -->
                <!-- <jet-button
                    type="button"
                    v-if="userPermissions.canUpdateSurvey"
                    variant="primary"
                    size="sm"
                    @click="saveSettings"
                    class="px-6 ml-2 uppercase"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    save round
                </jet-button> -->
            </template>
        </JetDialogModal>

        <!-- Variable Modal -->
        <!-- Header content -->
        <JetDialogModal
            :show="variableModal"
            @close="variableModal = false"
            maxWidth="7xl"
        >
            <template #title> Variable </template>

            <!-- Body content -->
            <template #content>
                <VariableForm :survey="survey" />
            </template>

            <!-- Footer content -->
            <template #footer>
                <JetButton
                    @click="variableModal = false"
                    size="sm"
                    variant="default"
                    class="px-6 uppercase"
                >
                    nevermind
                </JetButton>
                <jet-button
                    type="button"
                    v-if="userPermissions.canUpdateSurvey"
                    variant="primary"
                    size="sm"
                    @click="saveSettingsvariable"
                    class="px-6 ml-2 uppercase"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    save settings
                </jet-button>
            </template>
        </JetDialogModal>

        <!-- First Setup Dialog -->
        <SetupDialog
            :show="setFirstSettings"
            @close-first-setup="closeFirstSetup"
            :survey="survey"
            :projectVariables="variables"
            @save-configuration="saveSettings"
            @save-configuration-variables="saveConfigurationVariables"
        />

        
    </div>
</template>
<style>
.set-strong strong {
    font-weight: bolder !important;
    color: #1f2937;
}
body {
    overflow: visible !important;
}

.header img {
    width: 5% !important;
    display: inline !important;
}

/* Entrance transitions */
.nav-initial {
    opacity: 0;
    transform: translateX(-20px);
    transition: opacity 0.2s ease-out, transform 0.2s ease-out;
}

.nav-enter {
    opacity: 1;
    transform: translateX(0);
}

.test-list-initial {
    opacity: 0;
    transform: translateY(5px);
    transition: opacity 0.2s ease-out, transform 0.2s ease-out;
}

.test-list-enter {
    opacity: 1;
    transform: translateY(0);
}

.status-secondary {
    background-color: rgb(252, 55, 20) !important;
    color: rgb(252, 55, 20) !important;
}

.status {
    background-image: unset !important;
    box-shadow: unset !important;
}
:deep(td p) {
    display: inline-flex !important;
}

img {
    display: inline-block;
    max-width: 100px;
    max-height: 100px;
    margin: 0 4px;
    vertical-align: middle;
    border-radius: 4px;
}
</style>
