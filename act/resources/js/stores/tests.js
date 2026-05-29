import { defineStore } from "pinia";
import axios from "axios";

export const useTestsStore = defineStore("tests", {
    state: () => ({
        testid: null,
        surveyID: null,
        isDirty: false,
        hasUnsavedChanges: false,

        tests: {
            id: null,
            targets: [],
            primes: [],
            config: {
                dummy_trial: false,
                title: "",
                main_header: "",
                bottom_text: "",
                main_trial: true,
                random: true,
                empty_selection: "Please select at least 1 item",
                main_header_timeout: 3000,
                type: "trial",
                single_select: false,
                limit: 0,
                auto_next: false,
                display_by: 1,
                random_prime: false,
                random_prime_subsets: false,
                prime_as_next: true,
                conditions: [],
                analysis_options: [],
                // variables: [],
                target_delay: 1500,
                limit_reach: "Select Limit",
            },
        },
    }),
    getters: {
        targets: (state) => state.tests.targets || [],
        primes: (state) => state.tests.primes || [],
        // variables: (state) => state.tests.config?.variables || [],
        nextTargetIndex: (state) => {
            if (!state.tests.targets || state.tests.targets.length === 0)
                return 1;
            const maxIndex = Math.max(
                ...state.tests.targets.map((t) => t.index)
            );
            return Number.isFinite(maxIndex) ? maxIndex + 1 : 1;
        },
        nextPrimeIndex: (state) => {
            if (!state.tests.primes || state.tests.primes.length === 0)
                return 1;
            const maxIndex = Math.max(
                ...state.tests.primes.map((p) => p.index)
            );
            return Number.isFinite(maxIndex) ? maxIndex + 1 : 1;
        },
    },
    actions: {
        // -------- Persistence --------
        async saveTest() {
            try {
                const response = await axios.post(route("survey2.saveTest"), {
                    testid: this.testid,
                    survey_id: this.surveyID,
                    tests: {
                        targets: this.tests.targets,
                        primes: this.tests.primes,
                        config: this.tests.config,
                    },
                });
                this.isDirty = false;
                return response.data;
            } catch (error) {
                console.error("Error saving test:", error);
                throw error;
            }
        },

        // -------- Initialization / Sync --------
        syncDefaults() {
            // const theme = this.tests?.config?.theme ?? 1;
            // const defaultTargetsProps =
            //     theme === 2
            //         ? {
            //               bg: "#ffffff",
            //               borderColor: "#D1D5DB",
            //               borderSize: 1.2,
            //               CircleSize: 208,
            //               radius: 327,
            //               hoverColor: null,
            //           }
            //         : {
            //               bg: null,
            //               borderColor: null,
            //               borderSize: null,
            //               CircleSize: null,
            //               radius: null,
            //               hoverColor: null,
            //           };
            // const defaultPrimesProps =
            //     theme === 2
            //         ? {
            //               bg: "#ffffff",
            //               borderColor: "#D1D5DB",
            //               borderSize: 1.6,
            //               CircleSize: 42,
            //               hoverColor: null,
            //               top: "26%",
            //           }
            //         : {
            //               bg: null,
            //               borderColor: null,
            //               borderSize: null,
            //               CircleSize: null,
            //               hoverColor: null,
            //               top: null,
            //           };

            const defaultConfig = {
                title: "",
                main_header: "",
                bottom_text: "",
                main_trial: true,
                random: true,
                empty_selection: "Please select at least 1 item",
                main_header_timeout: 3000,
                type: "trial",
                single_select: false,
                limit: 0,
                auto_next: false,
                display_by: 1,
                random_prime: false,
                random_prime_subsets: false,
                prime_as_next: true,
                conditions: [],
                analysis_options: [],
                // variables: [],
                target_delay: 1500,
                limit_reach: "Select Limit",
            };

            this.tests.config = {
                ...defaultConfig,
                ...this.tests.config,
            };

            if (!Array.isArray(this.tests.targets)) this.tests.targets = [];
            if (!Array.isArray(this.tests.primes)) this.tests.primes = [];
            // if (!Array.isArray(this.tests.config.variables))
            //     this.tests.config.variables = [];
            if (!Array.isArray(this.tests.config.conditions))
                this.tests.config.conditions = [];
            if (!Array.isArray(this.tests.config.analysis_options))
                this.tests.config.analysis_options = [];
            return this.tests;
        },

        initialize() {
            return this.syncDefaults();
        },

        setTests(newTests) {
            if (
                !newTests ||
                (Array.isArray(newTests) && newTests.length === 0)
            ) {
                this.testid = null;
                this.tests = {
                    id: null,
                    targets: [],
                    primes: [],
                    config: {},
                };
                this.syncDefaults();
                this.isDirty = false;
                return;
            }

            const testsArray = Array.isArray(newTests) ? newTests : [newTests];
            const firstTest = testsArray[0] || {};

            const targets = firstTest.targets || [];
            const primes = firstTest.primes || [];
            const config = firstTest.config || {};

            this.testid = firstTest.id ?? null;

            this.tests = {
                id: firstTest.id ?? null,
                targets: targets.map((target, i) => {
                    const computedIndex = target?.index ?? i + 1;
                    const numericValue = Number(target?.value);
                    return {
                        ...target,
                        index: computedIndex,
                        value:
                            Number.isFinite(numericValue) && numericValue > 0
                                ? numericValue
                                : computedIndex,
                    };
                }),
                primes: primes.map((prime, i) => {
                    const computedIndex = prime?.index ?? i + 1;
                    const numericValue = Number(prime?.value);
                    return {
                        ...prime,
                        index: computedIndex,
                        prime: prime.prime || "",
                        value:
                            Number.isFinite(numericValue) && numericValue > 0
                                ? numericValue
                                : computedIndex,
                    };
                }),
                config: { ...config },
            };

            this.syncDefaults();
            this.isDirty = false;
        },

        // -------- Targets --------
        addTargetsBulk(targetList = []) {
            if (!Array.isArray(targetList) || targetList.length === 0)
                return [];
            const added = [];
            for (const t of targetList) {
                const next = this.nextTargetIndex;
                const newTarget = {
                    target: "",
                    equivalent: "",
                    can_unselect: false,
                    colors: { normal: null, hover: null, active: null },
                    properties: {
                        bg: null,
                        hoverColor: null,
                        p: null,
                        m: null,
                        fontSize: null,
                        imageScale: null,
                    },
                    index: next,
                    value: next,
                    ...(t || {}),
                };
                // Ensure index/value stay aligned if omitted
                newTarget.index = Number.isFinite(newTarget.index)
                    ? newTarget.index
                    : next;
                if (newTarget.value == null || newTarget.value === "") {
                    newTarget.value = newTarget.index;
                }
                this.tests.targets.push(newTarget);
                added.push(newTarget);
            }
            this.isDirty = true;
            return added;
        },
        addTarget(targetData = {}) {
            const base = {
                target: "",
                equivalent: "",
                index: this.nextTargetIndex,
                can_unselect: false,
                colors: { normal: null, hover: null, active: null },
                properties: {
                    bg: null,
                    hoverColor: null,
                    p: null,
                    m: null,
                    fontSize: null,
                    imageScale: null,
                },
                value: this.nextTargetIndex,
            };
            const merged = { ...base, ...targetData };
            const numericValue = Number(merged.value);
            if (!Number.isFinite(numericValue) || numericValue <= 0) {
                merged.value = merged.index;
            }
            const newTarget = merged;
            this.tests.targets.push(newTarget);
            this.isDirty = true;
            return newTarget;
        },
        updateTarget(arrayIndex, updatedData) {
            if (arrayIndex >= 0 && arrayIndex < this.tests.targets.length) {
                this.tests.targets = this.tests.targets.map((t, i) =>
                    i === arrayIndex ? { ...t, ...updatedData } : t
                );
                this.isDirty = true;
            }
        },
        updateTargetByIndex(targetIndex, updatedData) {
            const arrayIndex = this.tests.targets.findIndex(
                (t) => t.index === targetIndex
            );
            if (arrayIndex !== -1) this.updateTarget(arrayIndex, updatedData);
        },
        deleteTarget(arrayIndex) {
            if (arrayIndex >= 0 && arrayIndex < this.tests.targets.length) {
                this.tests.targets.splice(arrayIndex, 1);
                this.tests.targets.forEach((t, i) => {
                    t.index = i + 1;
                });
                this.isDirty = true;
            }
        },

        // -------- Primes --------
        addPrimesBulk(primeList = []) {
            if (!Array.isArray(primeList) || primeList.length === 0) return [];
            const added = [];
            for (const p of primeList) {
                const next = this.nextPrimeIndex;
                const newPrime = {
                    prime: "",
                    equivalent: "",
                    config: { conditions: [] },
                    properties: {
                        bg: null,
                        hoverColor: null,
                        p: null,
                        m: null,
                        fontSize: null,
                        imageScale: null,
                    },
                    rawPrimeElement: "",
                    index: next,
                    value: next,
                    ...(p || {}),
                };
                newPrime.index = Number.isFinite(newPrime.index)
                    ? newPrime.index
                    : next;
                if (newPrime.value == null || newPrime.value === "") {
                    newPrime.value = newPrime.index;
                }
                this.tests.primes.push(newPrime);
                added.push(newPrime);
            }
            this.isDirty = true;
            return added;
        },
        addPrime(primeData = {}) {
            const base = {
                prime: "",
                equivalent: "",
                index: this.nextPrimeIndex,
                config: { conditions: [] },
                properties: {
                    bg: null,
                    hoverColor: null,
                    p: null,
                    m: null,
                    fontSize: null,
                    imageScale: null,
                },
                value: this.nextPrimeIndex,
                rawPrimeElement: "",
            };
            const merged = { ...base, ...primeData };
            const numericValue = Number(merged.value);
            if (!Number.isFinite(numericValue) || numericValue <= 0) {
                merged.value = merged.index;
            }
            const newPrime = merged;
            this.tests.primes.push(newPrime);
            this.isDirty = true;
            return newPrime;
        },
        updatePrime(arrayIndex, updatedData) {
            if (arrayIndex >= 0 && arrayIndex < this.tests.primes.length) {
                this.tests.primes[arrayIndex] = {
                    ...this.tests.primes[arrayIndex],
                    ...updatedData,
                    index: this.tests.primes[arrayIndex].index,
                };
                this.isDirty = true;
            }
        },
        updatePrimeByIndex(primeIndex, updatedData) {
            const arrayIndex = this.tests.primes.findIndex(
                (p) => p.index === primeIndex
            );
            if (arrayIndex !== -1) this.updatePrime(arrayIndex, updatedData);
        },
        deletePrime(arrayIndex) {
            if (arrayIndex >= 0 && arrayIndex < this.tests.primes.length) {
                this.tests.primes.splice(arrayIndex, 1);
                this.tests.primes.forEach((p, i) => {
                    p.index = i + 1;
                });
            }
        },
        addPrimeCondition(arrayIndex, condition) {
            if (arrayIndex >= 0 && arrayIndex < this.tests.primes.length) {
                this.tests.primes[arrayIndex].config.conditions.push(condition);
            }
        },
        removePrimeCondition(arrayIndex, conditionIndex) {
            if (arrayIndex >= 0 && arrayIndex < this.tests.primes.length) {
                this.tests.primes[arrayIndex].config.conditions.splice(
                    conditionIndex,
                    1
                );
            }
        },

        // -------- Config: Variables --------
        // setVariables(variables) {
        //     if (!this.tests.config) this.tests.config = {};
        //     this.tests.config.variables = Array.isArray(variables)
        //         ? variables
        //         : [];
        // },

        // -------- Config: Analysis options --------
        addAnalysisOption(option) {
            if (!this.tests.config) this.tests.config = {};
            if (!Array.isArray(this.tests.config.analysis_options)) {
                this.tests.config.analysis_options = [];
            }
            const newOption = option || {};
            this.tests.config.analysis_options.push(newOption);
            return newOption;
        },
        updateAnalysisOption(index, partial) {
            if (
                !this.tests.config ||
                !Array.isArray(this.tests.config.analysis_options)
            ) {
                return;
            }
            if (
                index < 0 ||
                index >= this.tests.config.analysis_options.length
            ) {
                return;
            }
            const current = this.tests.config.analysis_options[index] || {};
            this.tests.config.analysis_options[index] = {
                ...current,
                ...(partial || {}),
            };
        },
        deleteAnalysisOption(index) {
            if (
                !this.tests.config ||
                !Array.isArray(this.tests.config.analysis_options)
            ) {
                return;
            }
            if (
                index < 0 ||
                index >= this.tests.config.analysis_options.length
            ) {
                return;
            }
            this.tests.config.analysis_options.splice(index, 1);
        },
        // addVariable(variable) {
        //     if (!this.tests.config.variables) this.tests.config.variables = [];
        //     this.tests.config.variables.push(variable);
        //     this.isDirty = true;
        // },
        // removeVariable(index) {
        //     if (
        //         this.tests.config.variables &&
        //         index >= 0 &&
        //         index < this.tests.config.variables.length
        //     ) {
        //         this.tests.config.variables.splice(index, 1);
        //         this.isDirty = true;
        //     }
        // },
        // updateVariable(index, variable) {
        //     if (
        //         this.tests.config.variables &&
        //         index >= 0 &&
        //         index < this.tests.config.variables.length
        //     ) {
        //         this.tests.config.variables[index] = variable;
        //         this.isDirty = true;
        //     }
        // },
        // getVariableByName(name) {
        //     if (!this.tests.config.variables) return null;
        //     return (
        //         this.tests.config.variables.find((v) => v?.name === name) ||
        //         null
        //     );
        // },

        // -------- Config: Generic helpers --------
        updateConfig(partial) {
            this.tests.config = { ...this.tests.config, ...partial };
            this.isDirty = true;
            this.hasUnsavedChanges = true;
        },
        setHasUnsavedChanges(value) {
            this.hasUnsavedChanges = value;
        },
    },
});
