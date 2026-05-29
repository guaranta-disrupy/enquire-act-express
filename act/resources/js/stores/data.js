import { defineStore } from 'pinia';
import { useForm, router } from '@inertiajs/vue3';

export const useDataStore = defineStore('data', {
  state: () => ({
    testid: null,
    surveyID: null,
    tests: {
      id: null,
      targets: [],
      primes: [],
              config: {
          title: '',
          main_header: '',
          bottom_text: '',
        main_trial: true,
          theme: 1,
          random: true,
          trail_unselect: false,
          default_trial_unselect: false,
          trail_unselect_label: 'None of these',
          empty_selection: 'Please select at least 1 item',
          main_header_timeout: 3000,
          type: 'trial',
          single_select: false,
          limit: 0,
          auto_next: false,
          display_by: 1,
          random_prime: false,
          random_prime_subsets: false,
          mobile_vertical_targets: false,
          prime_as_next: true,
          conditions: [],
          macros: [],
          header_position: { x: 0, y: 16 },
          header_properties: {
            fontSize: null,
            fontFamily: null,
            fontWeight: null,
            fontStyle: null,
            fontColor: null,
            fontAlignment: null,
            fontDecoration: null,
            fontBackground: null,
          },
          footer_position: { x: 0, y: 0 },
          footer_properties: {
            fontSize: null,
            fontFamily: null,
            fontWeight: null,
            fontStyle: null,
            fontColor: null,
            fontAlignment: null,
            fontDecoration: null,
            fontBackground: null,
          },
          properties: {
            Targets: {},
            Primes: {}
          }
        },
    },
  }),
  getters: {
    targets: (state) => state.tests.targets || [],
    primes: (state) => state.tests.primes || [],
    variables: (state) => state.tests.config?.variables || [],
    nextTargetIndex: (state) => {
      if (state.tests.targets.length === 0) return 1;
      const maxIndex = Math.max(...state.tests.targets.map(t => t.index));
      return maxIndex + 1;
    },
    nextPrimeIndex: (state) => {
      if (state.tests.primes.length === 0) return 1;
      const maxIndex = Math.max(...state.tests.primes.map(p => p.index));
      return maxIndex + 1;
    }
  },
  actions: {

   // Pinia Store

async saveTest() {
    try {
        const response = await router.post(
            route('survey2.saveTest'), {
                tests: {
                    testid: this.testid,
                    survey_id: this.surveyID, 
                    tests: {
                        targets: this.tests.targets,
                        primes: this.tests.primes,
                        config: this.tests.config,
                    }
                }
            }, {
                preserveState: true,
                preserveScroll: true,
            }
        );

        // console.log('Save successful', response);
        return response;

    } catch (error) {
        console.error('Error saving test:', error);
        throw error;
    }
},

    setTests(newTests) {
      // console.log("setTests called with:", newTests);
      // console.log("newTests type:", typeof newTests);
      // console.log("newTests is array:", Array.isArray(newTests));
      // console.log("newTests length:", newTests?.length);
      
      if (!newTests || (Array.isArray(newTests) && newTests.length === 0)) {
        console.log("Initializing with empty tests");
        this.testid = null;
        this.tests = {
          id: null,
          targets: [],
          primes: [],
          config: {}
        };
        // Auto-sync with defaults
        this.syncFormData();
        return;
      }

      // Ensure we have an array
      const testsArray = Array.isArray(newTests) ? newTests : [newTests];
      // console.log("Tests array:", testsArray);
      
      const firstTest = testsArray[0] || {};
      // console.log("First test:", firstTest);
      
      const targets = firstTest.targets || [];
      const primes = firstTest.primes || [];
      const config = firstTest.config || {};
      
      // console.log("Extracted data:", { targets, primes, config });
      
      this.testid = firstTest.id;
      // surveyID should be set separately from the survey object, not from test data
      // this.surveyID = firstTest.survey_id || firstTest.survey_id || null;

      this.tests = {
        id: firstTest.id,
        targets: targets.map((target, i) => ({
          ...target,
          index: target.index ?? i + 1,
          properties: {
            bg: target.properties?.bg || null,
            hoverColor: target.properties?.hoverColor || null,
            fontSize: target.properties?.fontSize || null,
            fontFamily: target.properties?.fontFamily || null,
            fontDecoration: target.properties?.fontDecoration || null,
            fontWeight: target.properties?.fontWeight || null,
            fontStyle: target.properties?.fontStyle || null,
            fontColor: target.properties?.fontColor || null,
            fontAlignment: target.properties?.fontAlignment || null,
            fontList: target.properties?.fontList || null,
            fontBlockquote: target.properties?.fontBlockquote || null,
            fontCodeBlock: target.properties?.fontCodeBlock || null,
            fontLink: target.properties?.fontLink || null,
            imagesUrl: target.properties?.imagesUrl || null,
            imageScale: target.properties?.imageScale || null,
            fontScript: target.properties?.fontScript || null,
            fontSubscript: target.properties?.fontSubscript || null,
            fontSuperscript: target.properties?.fontSuperscript || null,
            fontBackground: target.properties?.fontBackground || null,
          },
          rawTargetElement: target.rawTargetElement || target.target || null,
        })),

        primes: primes.map((prime, i) => ({
          ...prime,
          index: prime.index ?? i + 1,
          properties: {
            bg: prime.properties?.bg || null,
            hoverColor: prime.properties?.hoverColor || null,
              fontSize: prime.properties?.fontSize || null,
              fontFamily: prime.properties?.fontFamily || null,
              fontDecoration: prime.properties?.fontDecoration || null,
              fontWeight: prime.properties?.fontWeight || null,
              fontStyle: prime.properties?.fontStyle || null,
              fontColor: prime.properties?.fontColor || null,
              fontAlignment: prime.properties?.fontAlignment || null,
              fontList: prime.properties?.fontList || null,
              fontBlockquote: prime.properties?.fontBlockquote || null,
              fontCodeBlock: prime.properties?.fontCodeBlock || null,
              fontLink: prime.properties?.fontLink || null,
              imagesUrl: prime.properties?.imagesUrl || null,
              imageScale: prime.properties?.imageScale || null,
              fontScript: prime.properties?.fontScript || null,
              fontSubscript: prime.properties?.fontSubscript || null,
              fontSuperscript: prime.properties?.fontSuperscript || null,
              fontBackground: prime.properties?.fontBackground || null,
          },
          rawPrimeElement: prime.rawPrimeElement || prime.prime || '',
          prime: prime.prime || '',
        })),

        config: {
          ...config,
          title: config.title ?? '',
          type: config.type ?? 'trial',
          display_by: config.display_by ?? 1,
          main_header: config.main_header ?? '',
          bottom_text: config.bottom_text ?? '',
          trial_unselect: config.trial_unselect ?? false,
          trial_unselect_label: config.trial_unselect_label ?? 'None of these',
          target_delay: config.target_delay ?? 1500,
          limit_reach: config.limit_reach ?? 'Select limit',
          theme: config.theme ?? 1,
          unselect_message: 'Please confirm that none of these options apply.',
          unselect_cancel: 'Cancel',
          unselect_submit: 'Submit',
          button_settings: {
            next_button: {text: 'Next', delay: 0, bgColor: '', color: '', borderColor: '', borderSize: 0, radius: 0},
            final_button: {text: 'Final', delay: 0, bgColor: '', color: '', borderColor: '', borderSize: 0, radius: 0},
            button_delay: 0,
            is_countdown_visible: false,
          },
          unselect_settings: {
            unselect_message: 'Please confirm that none of these options apply.',
            unselect_cancel: 'Cancel',
            unselect_submit: 'Submit',
          },

          
          header_position: config.header_position ?? { x: 768, y: 22 },
          header_properties: {
            fontSize: config.header_properties?.fontSize || null,
            fontFamily: config.header_properties?.fontFamily || null,
            fontWeight: config.header_properties?.fontWeight || null,
            fontStyle: config.header_properties?.fontStyle || null,
            fontColor: config.header_properties?.fontColor || null,
            fontAlignment: config.header_properties?.fontAlignment || null,
            fontDecoration: config.header_properties?.fontDecoration || null,
            fontBackground: config.header_properties?.fontBackground || null,
          },
          footer_position: config.footer_position ?? { x: 569, y: 720 },
          footer_properties: {
            fontSize: config.footer_properties?.fontSize || null,
            fontFamily: config.footer_properties?.fontFamily || null,
            fontWeight: config.footer_properties?.fontWeight || null,
            fontStyle: config.footer_properties?.fontStyle || null,
            fontColor: config.footer_properties?.fontColor || null,
            fontAlignment: config.footer_properties?.fontAlignment || null,
            fontDecoration: config.footer_properties?.fontDecoration || null,
            fontBackground: config.footer_properties?.fontBackground || null,
          },
          properties: {
            Targets: {
              bg: config.properties?.Targets?.bg || null,
              borderColor: config.properties?.Targets?.borderColor || null,
              borderSize: config.properties?.Targets?.borderSize || null,
              CircleSize: config.properties?.Targets?.CircleSize || null,
              radius: config.properties?.Targets?.radius || null,
              hoverColor: config.properties?.Targets?.hoverColor || null,
            },
            Primes: {
              bg: config.properties?.Primes?.bg || null,
              borderColor: config.properties?.Primes?.borderColor || null,
              borderSize: config.properties?.Primes?.borderSize || null,
              CircleSize: config.properties?.Primes?.CircleSize || null,
              hoverColor: config.properties?.Primes?.hoverColor || null,
              top: config.properties?.Primes?.top || null,
            }
          }
        }
      };
      // Auto-sync with defaults to ensure all fields are present
      this.syncFormData();
      // console.log("Final tests state:", this.tests);
    },
    
    // Add the missing actions here
    updateTarget(index, updatedData) {
      if (index >= 0 && index < this.tests.targets.length) {
        this.tests.targets = this.tests.targets.map((target, i) => 
          i === index ? { ...target, ...updatedData } : target
        );
      }
      // console.log("this is targets", updatedData)
    },

    updateTargetByIndex(targetIndex, updatedData) {
      const arrayIndex = this.tests.targets.findIndex(t => t.index === targetIndex);
      if (arrayIndex !== -1) {
        this.updateTarget(arrayIndex, updatedData);
      }

      

    },

    updatePrime(arrayIndex, updatedData) {
      // console.log("updatePrime called with:", updatedData)
      if (arrayIndex >= 0 && arrayIndex < this.tests.primes.length) {
        this.tests.primes[arrayIndex] = {
          ...this.tests.primes[arrayIndex],
          ...updatedData,
          index: this.tests.primes[arrayIndex].index
        };
      }
    },

    updatePrimeByIndex(primeIndex, updatedData) {
      console.log(primeIndex,"this is updatedData", updatedData)
      const arrayIndex = this.tests.primes.findIndex(p => p.index === primeIndex);
      if (arrayIndex !== -1) {
        this.updatePrime(arrayIndex, updatedData);
      }
    },

    addTarget(targetData) {
      const newTarget = {
        target: '',
        equivalent: '',
        index: this.nextTargetIndex,
        can_unselect: false,
        colors: {
          normal: null,
          hover: null,
          active: null
        },
        properties: {
          bg: null,
          hoverColor: null,
          p: null,
          m: null,
          fontSize: null,
          imageScale: null,
        },
        value: this.nextTargetIndex,
        ...targetData
      };
      this.tests.targets.push(newTarget);
      // console.log(this.targets)
      return newTarget;
    },

    addPrime(primeData) {

      const newPrime = {
        prime: '',
        equivalent: '',
        index: this.nextPrimeIndex,
        config: {
          conditions: []
        },
        properties: {
          bg: null,
          hoverColor: null,
          p: null,
          m: null,
          fontSize: null,
          imageScale: null,
        },
        value: this.nextPrimeIndex,
        rawPrimeElement: '', // Add this property for Quill editor
        ...primeData
      };

      this.tests.primes.push(newPrime);
    
      return newPrime;
    },

    deleteTarget(indexToDelete) {
      if (indexToDelete >= 0 && indexToDelete < this.tests.targets.length) {
        this.tests.targets.splice(indexToDelete, 1);
        this.tests.targets.forEach((target, i) => {
          target.index = i + 1;
        });
      }
    },

    deletePrimes(indexToDelete) {
      if (indexToDelete >= 0 && indexToDelete < this.tests.primes.length) {
        this.tests.primes.splice(indexToDelete, 1);
        this.tests.primes.forEach((prime, i) => {
          prime.index = i + 1;
        });
      }
    },

    addPrimeCondition(primeIndex, condition) {
      if (primeIndex >= 0 && primeIndex < this.tests.primes.length) {
        this.tests.primes[primeIndex].config.conditions.push(condition);
      }
    },

    removePrimeCondition(primeIndex, conditionIndex) {
      if (primeIndex >= 0 && primeIndex < this.tests.primes.length) {
        this.tests.primes[primeIndex].config.conditions.splice(conditionIndex, 1);
      }
    },

    // -------------------------
    // Config updates
    // -------------------------
    setMainHeader(html) {
      this.tests.config.main_header = html ?? '';
    },

    setBottomText(html) {
      this.tests.config.bottom_text = html ?? '';
    },

    updateConfig(partial) {
      this.tests.config = {
        ...this.tests.config,
        ...partial,
      };
    },

    setHeaderPosition(pos) {
      const x = typeof pos?.x === 'number' ? pos.x : 0;
      const y = typeof pos?.y === 'number' ? pos.y : 16;
      if (!this.tests.config) this.tests.config = {};
      this.tests.config.header_position = { x, y };
    },

    setFooterPosition(pos) {
      const x = typeof pos?.x === 'number' ? pos.x : 0;
      const y = typeof pos?.y === 'number' ? pos.y : 0;
      if (!this.tests.config) this.tests.config = {};
      this.tests.config.footer_position = { x, y };
    },

    // -------------------------
    // Enhanced Config Update Functions
    // -------------------------
    
    // Basic config properties
    updateBasicConfig(updates) {
      const allowedFields = [
        'title', 'main_header', 'bottom_text', 'main_trial', 'random',
        'empty_selection', 'main_header_timeout', 'type', 'single_select',
        'limit', 'auto_next', 'display_by'
      ];
      
      const filteredUpdates = Object.keys(updates)
        .filter(key => allowedFields.includes(key))
        .reduce((obj, key) => {
          obj[key] = updates[key];
          return obj;
        }, {});
      
      this.updateConfig(filteredUpdates);
    },

    // Prime-related config
    updatePrimeConfig(updates) {
      const allowedFields = [
        'random_prime', 'random_prime_subsets', 'prime_as_next'
      ];
      
      const filteredUpdates = Object.keys(updates)
        .filter(key => allowedFields.includes(key))
        .reduce((obj, key) => {
          obj[key] = updates[key];
          return obj;
        }, {});
      
      this.updateConfig(filteredUpdates);
    },

    // Conditions management
    updateConditions(conditions) {
      this.tests.config.conditions = Array.isArray(conditions) ? conditions : [];
    },

    addCondition(condition) {
      if (!this.tests.config.conditions) {
        this.tests.config.conditions = [];
      }
      this.tests.config.conditions.push(condition);
    },

    removeCondition(index) {
      if (this.tests.config.conditions && index >= 0 && index < this.tests.config.conditions.length) {
        this.tests.config.conditions.splice(index, 1);
      }
    },

    updateCondition(index, condition) {
      if (this.tests.config.conditions && index >= 0 && index < this.tests.config.conditions.length) {
        this.tests.config.conditions[index] = condition;
      }
    },

    // Macros management
    updateMacros(macros) {
      this.tests.config.macros = Array.isArray(macros) ? macros : [];
    },

    addMacro(macro) {
      if (!this.tests.config.macros) {
        this.tests.config.macros = [];
      }
      this.tests.config.macros.push(macro);
    },

    removeMacro(index) {
      if (this.tests.config.macros && index >= 0 && index < this.tests.config.macros.length) {
        this.tests.config.macros.splice(index, 1);
      }
    },

    updateMacro(index, macro) {
      if (this.tests.config.macros && index >= 0 && index < this.tests.config.macros.length) {
        this.tests.config.macros[index] = macro;
      }
    },

    // Variables management
    setVariables(variables) {
      if (!this.tests.config) {
        this.tests.config = {};
      }
      this.tests.config.variables = Array.isArray(variables) ? variables : [];
    },

    addVariable(variable) {
      if (!this.tests.config.variables) {
        this.tests.config.variables = [];
      }
      this.tests.config.variables.push(variable);
    },

    removeVariable(index) {
      if (this.tests.config.variables && index >= 0 && index < this.tests.config.variables.length) {
        this.tests.config.variables.splice(index, 1);
      }
    },

    updateVariable(index, variable) {
      if (this.tests.config.variables && index >= 0 && index < this.tests.config.variables.length) {
        this.tests.config.variables[index] = variable;
      }
    },

    getVariableByName(name) {
      if (!this.tests.config.variables) {
        return null;
      }
      return this.tests.config.variables.find(v => v.name === name);
    },

    // Properties management
    updateTargetProperties(properties) {
      if (!this.tests.config.properties) {
        this.tests.config.properties = {};
      }
      if (!this.tests.config.properties.Targets) {
        this.tests.config.properties.Targets = {};
      }
      
      this.tests.config.properties.Targets = {
        ...this.tests.config.properties.Targets,
        ...properties
      };
    },

    updatePrimeProperties(properties) {
      if (!this.tests.config.properties) {
        this.tests.config.properties = {};
      }
      if (!this.tests.config.properties.Primes) {
        this.tests.config.properties.Primes = {};
      }
      
      this.tests.config.properties.Primes = {
        ...this.tests.config.properties.Primes,
        ...properties
      };
    },

    // Specific property setters
    setTargetBackground(bg) {
      this.updateTargetProperties({ bg });
    },

    setTargetBorderColor(borderColor) {
      this.updateTargetProperties({ borderColor });
    },

    setTargetBorderSize(borderSize) {
      this.updateTargetProperties({ borderSize });
    },

    setTargetCircleSize(CircleSize) {
      this.updateTargetProperties({ CircleSize });
    },

    setTargetRadius(radius) {
      this.updateTargetProperties({ radius });
    },

    setTargetHoverColor(hoverColor) {
      this.updateTargetProperties({ hoverColor });
    },

    setPrimeBackground(bg) {
      this.updatePrimeProperties({ bg });
    },

    setPrimeBorderColor(borderColor) {
      this.updatePrimeProperties({ borderColor });
    },

    setPrimeBorderSize(borderSize) {
      this.updatePrimeProperties({ borderSize });
    },

    setPrimeCircleSize(CircleSize) {
      this.updatePrimeProperties({ CircleSize });
    },

    setPrimeHoverColor(hoverColor) {
      this.updatePrimeProperties({ hoverColor });
    },

    setPrimeTop(top) {
      this.updatePrimeProperties({ top });
    },

    // Reset functions
    resetConfig() {
      this.tests.config = {
        title: '',
        main_header: '',
        bottom_text: '',
        header_position: { x: 0, y: 16 },
        footer_position: { x: 0, y: 0 },
        main_trial: true,
        random: true,
        trail_unselect: false,
        default_trial_unselect: false,
        trail_unselect_label: 'None of these',
        empty_selection: 'Please select at least 1 item',
        main_header_timeout: 3000,
        type: 'trial',
        single_select: false,
        limit: 0,
        auto_next: false,
        auto_next_when: 1,
        display_by: 1,
        random_prime: false,
        random_prime_subsets: false,
        mobile_vertical_targets: false,
        prime_as_next: true,
        conditions: [],
        macros: [],
        properties: {
          Targets: { bg: null, borderColor: null, borderSize: null, CircleSize: null, radius: null, hoverColor: null },
          Primes: { bg: null, borderColor: null, borderSize: null, CircleSize: null, hoverColor: null, top: null }
        }
      };
    },

    resetTargetProperties() {
      this.updateTargetProperties({
        bg: null,
        borderColor: null,
        borderSize: null,
        CircleSize: null,
        radius: null,
        hoverColor: null
      });
    },

    resetPrimeProperties() {
      this.updatePrimeProperties({
        bg: null,
        borderColor: null,
        borderSize: null,
        CircleSize: null,
        hoverColor: null
      });
    },

    // Validation functions
    validateConfig() {
      const errors = [];
      
      if (!this.tests.config.title && this.tests.config.title !== '') {
        errors.push('Title is required');
      }
      
      if (this.tests.config.limit < 0) {
        errors.push('Limit must be non-negative');
      }
      
      if (this.tests.config.main_header_timeout < 0) {
        errors.push('Main header timeout must be non-negative');
      }
      
      if (this.tests.config.display_by < 1) {
        errors.push('Display by must be at least 1');
      }
      
      return {
        isValid: errors.length === 0,
        errors
      };
    },

    // Get config snapshot for comparison
    getConfigSnapshot() {
      return JSON.parse(JSON.stringify(this.tests.config));
    },

    // Check if config has changed from a previous snapshot
    hasConfigChanged(snapshot) {
      return JSON.stringify(this.tests.config) !== JSON.stringify(snapshot);
    },

    // Auto-synchronize form data with default values
    syncFormData() {
      const theme = this.tests?.config?.theme ?? 1;
      const defaultTargetsProps = theme === 2
        ? { bg: '#ffffff', borderColor: '#D1D5DB', borderSize: 1.2, CircleSize: 208, radius: 327, hoverColor: null }
        : { bg: null, borderColor: null, borderSize: null, CircleSize: null, radius: null, hoverColor: null };
      const defaultPrimesProps = theme === 2
        ? { bg: '#ffffff', borderColor: '#D1D5DB', borderSize: 1.6, CircleSize: 42, hoverColor: null, top: '26%' }
        : { bg: null, borderColor: null, borderSize: null, CircleSize: null, hoverColor: null, top: null };

      const defaultConfig = {
        title: '',
        main_header: '',
        bottom_text: '',
        header_position: { x: 0, y: 16 },
        footer_position: { x: 0, y: 0 },
        main_trial: true,
        random: true,
        trail_unselect: false,
        default_trial_unselect: false,
        trail_unselect_label: 'None of these',
        empty_selection: 'Please select at least 1 item',
        main_header_timeout: 3000,
        type: 'trial',
        single_select: false,
        limit: 0,
        auto_next: false,
        auto_next_when: 1,
        display_by: 1,
        random_prime: false,
        random_prime_subsets: false,
        mobile_vertical_targets: false,
        prime_as_next: true,
        conditions: [],
        macros: [],
        variables: [],
        properties: {
          Targets: defaultTargetsProps,
          Primes: defaultPrimesProps
        }
      };

      // Ensure config exists
      if (!this.tests.config) {
        this.tests.config = {};
      }

      // Merge with defaults, preserving existing values
      this.tests.config = {
        ...defaultConfig,
        ...this.tests.config,
        // Ensure nested objects are properly merged
        properties: {
          ...defaultConfig.properties,
          ...this.tests.config.properties,
          Targets: {
            ...defaultConfig.properties.Targets,
            ...this.tests.config.properties?.Targets
          },
          Primes: {
            ...defaultConfig.properties.Primes,
            ...this.tests.config.properties?.Primes
          }
        }
      };

      // Ensure arrays exist
      if (!this.tests.config.conditions) {
        this.tests.config.conditions = [];
      }
      if (!this.tests.config.macros) {
        this.tests.config.macros = [];
      }
      if (!this.tests.config.variables) {
        this.tests.config.variables = [];
      }

      // Ensure targets and primes arrays exist
      if (!this.tests.targets) {
        this.tests.targets = [];
      }
      if (!this.tests.primes) {
        this.tests.primes = [];
      }

      return this.tests;
    },

    // Initialize form with proper defaults
    initializeForm() {
      this.syncFormData();
      return this.tests;
    },

    // Public method to ensure form is synchronized (can be called from any component)
    ensureFormSync() {
      this.syncFormData();
      return this.tests;
    },
  }
});