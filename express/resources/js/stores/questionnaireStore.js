import { ref, reactive } from 'vue'

// Shared reactive store for questionnaire data
const questionnaireData = reactive({
    customCSS: '',
    customJS: ''
})

// Functions to manage the shared data
export const useQuestionnaireStore = () => {
    const setCustomCSS = (css) => {
        questionnaireData.customCSS = css || ''
    }
    
    const setCustomJS = (js) => {
        questionnaireData.customJS = js || ''
    }
    
    const getCustomCSS = () => {
        return questionnaireData.customCSS
    }
    
    const getCustomJS = () => {
        return questionnaireData.customJS
    }
    
    const updateFromForm = (form) => {
        if (form && form.settings && form.settings.setup) {
            questionnaireData.customCSS = form.settings.setup.customCSS || ''
            questionnaireData.customJS = form.settings.setup.customJS || ''
        }
    }
    
    const initializeFromPageData = (pageData) => {
        if (pageData && pageData.questionnaire && pageData.questionnaire.setup) {
            questionnaireData.customCSS = pageData.questionnaire.setup.customCSS || ''
            questionnaireData.customJS = pageData.questionnaire.setup.customJS || ''
        }
    }
    
    return {
        questionnaireData,
        setCustomCSS,
        setCustomJS,
        getCustomCSS,
        getCustomJS,
        updateFromForm,
        initializeFromPageData
    }
}
