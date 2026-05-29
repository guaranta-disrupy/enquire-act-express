import { defineStore } from "pinia";
import { router } from "@inertiajs/vue3";


export const useTemplateVariablesStore = defineStore("templateVariables", {
    state: () => ({
        templatePrimes: [],
        templateTargets: [],
        survey: null,
    }),
    actions: {
       setSurvey(survey) {
        this.survey = survey;
       },
       updateSurvey(partial) {
        if (this.survey && partial && typeof partial === 'object') {
            this.survey = { ...this.survey, ...partial };
        }
       },
       clearSurvey() {
        this.survey = null;
       },
       setTemplatePrimes(primes) {
        this.templatePrimes = Array.isArray(primes) ? [...primes] : [];
        },
        updateTemplatePrimes(primes) {
            if (Array.isArray(primes)) {
                this.templatePrimes = [...primes];
            } else {
                this.templatePrimes = [];
            }
        },
        addTemplatePrime(prime) {
            if (prime && typeof prime === 'object' && prime.id !== undefined) {
                // Prevent duplicates by id
                if (!this.templatePrimes.some(p => p.id === prime.id)) {
                    this.templatePrimes.push(prime);
                }
            }
        },
        updateTemplatePrimeById(id, partial) {
            if (id === undefined || id === null) return;
            if (!partial || typeof partial !== 'object') return;
            const index = this.templatePrimes.findIndex(p => p.id === id);
            if (index !== -1) {
                const existing = this.templatePrimes[index];
                this.templatePrimes[index] = { ...existing, ...partial };
            }
        },
        deleteTemplatePrime(prime) {
            if (prime && prime.id !== undefined) {
                this.templatePrimes = this.templatePrimes.filter(p => p.id !== prime.id);
            }
        },
        // Row-level operations for primes.data
        updatePrimeRow(id, rowIndex, partial) {
            const prime = this.templatePrimes.find(p => p.id === id);
            if (!prime) return;
            const data = Array.isArray(prime.data) ? prime.data : [];
            if (rowIndex < 0 || rowIndex >= data.length) return;
            const updated = data.map((row, i) => i === rowIndex ? { ...row, ...partial } : row);
            this.updateTemplatePrimeById(id, { data: updated });
        },
        deletePrimeRow(id, rowIndex) {
            const prime = this.templatePrimes.find(p => p.id === id);
            if (!prime) return;
            const data = Array.isArray(prime.data) ? prime.data : [];
            const updated = data.filter((_, i) => i !== rowIndex);
            this.updateTemplatePrimeById(id, { data: updated });
        },
        addPrimeRow(id, row) {
            const prime = this.templatePrimes.find(p => p.id === id);
            const data = Array.isArray(prime?.data) ? prime.data : [];
            const updated = data.concat([row ?? { prime: "", equivalent: "", value: "" }]);
            this.updateTemplatePrimeById(id, { data: updated });
        },
        
       setTemplateTargets(targets) {
        this.templateTargets = targets;
       },
       updateTemplateTargets(targets) {
        if (Array.isArray(targets)) {
            this.templateTargets = [...targets];
        } else {
            this.templateTargets = [];
        }
       },
       addTemplateTarget(target) {
        if (target && typeof target === 'object' && target.id !== undefined) {
            // Prevent duplicates by id
            if (!this.templateTargets.some(t => t.id === target.id)) {
                this.templateTargets.push(target);
            }
        }
       },
       updateTemplateTargetById(id, partial) {
        if (id === undefined || id === null) return;
        if (!partial || typeof partial !== 'object') return;
        const index = this.templateTargets.findIndex(t => t.id === id);
        if (index !== -1) {
            const existing = this.templateTargets[index];
            this.templateTargets[index] = { ...existing, ...partial };
        }
       },
       deleteTemplateTarget(target) {
        if (target && target.id !== undefined) {
            this.templateTargets = this.templateTargets.filter(t => t.id !== target.id);
        }
       },
       // Row-level operations for targets.data
       updateTargetRow(id, rowIndex, partial) {
        const target = this.templateTargets.find(t => t.id === id);
        if (!target) return;
        const data = Array.isArray(target.data) ? target.data : [];
        if (rowIndex < 0 || rowIndex >= data.length) return;
        const updated = data.map((row, i) => i === rowIndex ? { ...row, ...partial } : row);
        this.updateTemplateTargetById(id, { data: updated });
       },
       deleteTargetRow(id, rowIndex) {
        const target = this.templateTargets.find(t => t.id === id);
        if (!target) return;
        const data = Array.isArray(target.data) ? target.data : [];
        const updated = data.filter((_, i) => i !== rowIndex);
        this.updateTemplateTargetById(id, { data: updated });
       },
       addTargetRow(id, row) {
        const target = this.templateTargets.find(t => t.id === id);
        const data = Array.isArray(target?.data) ? target.data : [];
        const updated = data.concat([row ?? { target: "", equivalent: "", value: "" }]);
        this.updateTemplateTargetById(id, { data: updated });
       },
       resetAllTemplateVariables() {
        this.templatePrimes = [];
        this.templateTargets = [];
        this.survey = null;
        },
       
       
       async saveTemplatesToServer() {
        if (!this.survey || !this.survey.id) {
            console.error('No survey in store to save templates against');
            return;
        }
        try {
            await router.post(
                route('templates.save', { team: this.survey.group.project.team_id, project: this.survey.group.project.uuid, survey: this.survey.id }),
                {
                    templatePrimes: this.templatePrimes,
                    templateTargets: this.templateTargets,
                },
                { preserveScroll: true, preserveState: true }
            );
        } catch (e) {
            console.error('Failed to save templates', e);
        }
       },
 
       validateTemplates() {
        const isValidList = (list, type) => {
            if (!Array.isArray(list)) return true;
            for (const item of list) {
                if (item == null) return false;
                if (item.id === undefined) return false;
                if (typeof item.name !== 'string') return false;
                if (item.data != null && !Array.isArray(item.data)) return false;
                if (Array.isArray(item.data)) {
                    for (const row of item.data) {
                        if (type === 'prime' && typeof row.prime !== 'string') return false;
                        if (type === 'target' && typeof row.target !== 'string') return false;
                    }
                }
            }
            return true;
        };
        return isValidList(this.templatePrimes, 'prime') && isValidList(this.templateTargets, 'target');
       },
        
    },
});
