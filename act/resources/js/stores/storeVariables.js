import { defineStore } from "pinia";
import { router } from "@inertiajs/vue3";


export const useStoreVariablesStore = defineStore("storeVariables", {
    state: () => ({
        Variables: [],
        VariablesId: null,
       
    }),
    actions: {
        setVariablesId(id) {
            this.VariablesId = id;
        },
        setVariables(variables) {
            this.Variables = variables;
        },
        addVariable(variable) {
            this.Variables.push(variable);
        },
        updateVariable(variable) {
            this.Variables = this.Variables.map(v => v.id === variable.id ? variable : v);
        },
        deleteVariable(variable) {
            this.Variables = this.Variables.filter(v => v.id !== variable.id);
        },
        addCriteria(variable, criteria) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria.push(criteria);
                    return updatedVariable;
                }
                return v;
            });
        },
        updateCriteria(variable, criteria) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => 
                        c.id === criteria.id ? criteria : c
                    );
                    return updatedVariable;
                }
                return v;
            });
        },
        deleteCriteria(variable, criteria) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.filter(c => c.id !== criteria.id);
                    return updatedVariable;
                }
                return v;
            });
        },
        addCondition(variable, criteria, condition) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions.push(condition);
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        updateCondition(variable, criteria, condition) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => 
                                cond.id === condition.id ? condition : cond
                            );
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        deleteCondition(variable, criteria, condition) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.filter(cond => cond.id !== condition.id);
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        addEquivalent(variable, criteria, condition, equivalent) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => 
                                cond.id === condition.id ? { ...cond, equivalent } : cond
                            );
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        updateEquivalent(variable, criteria, condition, equivalent) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => 
                                cond.id === condition.id ? { ...cond, equivalent } : cond
                            );
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        deleteEquivalent(variable, criteria, condition, equivalent) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => {
                                if (cond.id === condition.id) {
                                    const updatedCondition = { ...cond };
                                    if (Array.isArray(updatedCondition.equivalent)) {
                                        updatedCondition.equivalent = updatedCondition.equivalent.filter(e => e.id !== equivalent.id);
                                    } else {
                                        updatedCondition.equivalent = null;
                                    }
                                    return updatedCondition;
                                }
                                return cond;
                            });
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        addAction(variable, criteria, condition, action) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => 
                                cond.id === condition.id ? { ...cond, action } : cond
                            );
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        updateAction(variable, criteria, condition, action) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => 
                                cond.id === condition.id ? { ...cond, action } : cond
                            );
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        deleteAction(variable, criteria, condition, action) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => {
                                if (cond.id === condition.id) {
                                    const updatedCondition = { ...cond };
                                    if (Array.isArray(updatedCondition.action)) {
                                        updatedCondition.action = updatedCondition.action.filter(a => a.id !== action.id);
                                    } else {
                                        updatedCondition.action = null;
                                    }
                                    return updatedCondition;
                                }
                                return cond;
                            });
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        addMethod(variable, criteria, condition, method) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => 
                                cond.id === condition.id ? { ...cond, method } : cond
                            );
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        updateMethod(variable, criteria, condition, method) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => 
                                cond.id === condition.id ? { ...cond, method } : cond
                            );
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        deleteMethod(variable, criteria, condition, method) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => {
                                if (cond.id === condition.id) {
                                    const updatedCondition = { ...cond };
                                    if (Array.isArray(updatedCondition.method)) {
                                        updatedCondition.method = updatedCondition.method.filter(m => m.id !== method.id);
                                    } else {
                                        updatedCondition.method = null;
                                    }
                                    return updatedCondition;
                                }
                                return cond;
                            });
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        addValue(variable, criteria, condition, value) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => 
                                cond.id === condition.id ? { ...cond, value } : cond
                            );
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        updateValue(variable, criteria, condition, value) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => 
                                cond.id === condition.id ? { ...cond, value } : cond
                            );
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        deleteValue(variable, criteria, condition, value) {
            this.Variables = this.Variables.map(v => {
                if (v.id === variable.id) {
                    const updatedVariable = { ...v };
                    if (!updatedVariable.config) updatedVariable.config = {};
                    if (!updatedVariable.config.criteria) updatedVariable.config.criteria = [];
                    updatedVariable.config.criteria = updatedVariable.config.criteria.map(c => {
                        if (c.id === criteria.id) {
                            const updatedCriteria = { ...c };
                            if (!updatedCriteria.conditions) updatedCriteria.conditions = [];
                            updatedCriteria.conditions = updatedCriteria.conditions.map(cond => {
                                if (cond.id === condition.id) {
                                    const updatedCondition = { ...cond };
                                    if (Array.isArray(updatedCondition.value)) {
                                        updatedCondition.value = updatedCondition.value.filter(v => v.id !== value.id);
                                    } else {
                                        updatedCondition.value = null;
                                    }
                                    return updatedCondition;
                                }
                                return cond;
                            });
                            return updatedCriteria;
                        }
                        return c;
                    });
                    return updatedVariable;
                }
                return v;
            });
        },
        SaveVariables(project) {
            console.log("Saving variables:", this.Variables);
         
        }
            
    },
   
   
});
