import { useToast } from "vue-toastification";
import "vue-toastification/dist/index.css";
import moment from "moment";
import { ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { imageWithHeaders } from "./utils/imageUtils.js";
import { videoWithHeaders } from "./utils/videoUtils.js";

export const changes = ref(false);

export default function helpers() {
    const assign = (data) => {
        return JSON.parse(JSON.stringify(data));
    };

    const toast = useToast({
        timeout: 1500
    });

    const apply_disabled = (apply) => {
        return apply ? 'opacity-50 cursor-not-allowed': ''
    }

    const val_exists = (arr, data) => {
        return arr.some(function(el) {
            return el[data.key] == data.value;
        }); 
    }

    const random_str = (length) => {
        var result           = '';
        var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var charactersLength = characters.length;
        for ( var i = 0; i < length; i++ ) {
          result += characters.charAt(Math.floor(Math.random() * 
            charactersLength));
        }
       return result;
    }

    const date_string = (date) => {let newDate = new Date(date);return newDate.toDateString();}

    const initProperty = (obj,check,val = {}) => {
        if(!obj[check]){
            obj[check] = val;
        }
    
    };

    // Use the globalized utility functions (maintains backward compatibility)
    const ImageWithHeaders = imageWithHeaders;
    const VideoWithHeaders = videoWithHeaders;

    /**
     * Safely apply language translations to questions
     * @param {Object} project - Project object with questions array
     * @param {string} language - Language code (e.g., 'he', 'ja', etc.)
     */
    const applyQuestionTranslations = (project, language) => {
        if (!project || !project.questions || !Array.isArray(project.questions)) {
            console.warn('applyQuestionTranslations: Invalid project or questions array');
            return;
        }
        
        if (!language) {
            console.warn('applyQuestionTranslations: Language code is required');
            return;
        }

        project.questions.forEach(question => {
            // Safely access question translation with null checks
            if (question.setup && question.setup.language && question.setup.language[language]) {
                const translatedQuestion = question.setup.language[language].question;
                if (translatedQuestion) {
                    question.question = translatedQuestion;
                }
            }
            
            // Handle choices for question types 2, 3, and 20
            if ((question.question_type_id == 2 || question.question_type_id == 3 || question.question_type_id == 20)) {
                if (question.choices && Array.isArray(question.choices)) {
                    question.choices.forEach(choice => {
                        if (choice?.language?.[language]?.name) {
                            choice.name = choice.language[language].name;
                        }
                    });
                }
            }
        });
    }

    return {
        assign,
        toast,
        moment,
        apply_disabled,
        ImageWithHeaders,
        VideoWithHeaders,
        val_exists,
        random_str,
        date_string,
        initProperty,
        applyQuestionTranslations
    }

}