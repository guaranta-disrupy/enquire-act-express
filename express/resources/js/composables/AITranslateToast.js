import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import helpers from '@/helpers.js';

const { toast } = helpers();

export function AITranslateToast() {
    const showTranslateToast = async (questionnaireCode, questionId, questionOrder, action = 'added') => {
        const page = usePage();
        let untranslatedCount = 1;
        let untranslatedQuestionIds = [questionId];
        let totalLanguages = 0;
        let questionKey = '';
        
        const variableStart = page.props.questionnaire?.setup?.variable_start || 1;
        questionKey = 'Q' + (questionOrder + (variableStart - 1));
        
        try {
            const detectResponse = await axios.get(
                route('questions.detectUntranslated', [questionnaireCode])
            );
            
            if (detectResponse.data.success) {
                const detectedIds = detectResponse.data.untranslated_questions.map(q => q.id);
                
                if (action === 'updated') {
                    untranslatedQuestionIds = [questionId];
                    untranslatedCount = 1;
                } else {
                    untranslatedCount = detectResponse.data.count;
                    untranslatedQuestionIds = detectedIds.length > 0 ? detectedIds : [questionId];
                }
                
                if (detectResponse.data.untranslated_questions.length > 0) {
                    const firstQuestion = detectResponse.data.untranslated_questions[0];
                    totalLanguages = firstQuestion.missing_languages?.length || 0;
                }
            }
            
            if (totalLanguages === 0 && page.props.questionnaire?.setup?.language) {
                const languages = page.props.questionnaire.setup.language;
                totalLanguages = Object.keys(languages).filter(code => code !== ' ' && code.trim() !== '').length;
            }
            
            if (untranslatedQuestionIds.length === 0) {
                untranslatedQuestionIds = [questionId];
            }
        } catch (error) {
            console.error('Failed to detect untranslated questions:', error);
            untranslatedQuestionIds = [questionId];
        }
        
        const questions = page.props.questions || [];
        const totalQuestions = questions.length;
        
        if (totalLanguages < 1 || totalQuestions < 1) {
            return;
        }
        
        const translateQuestions = async () => {
            const progressToastId = `progress-toast-${Date.now()}`;
            let progressValue = 0;
            let progressInterval = null;
            
            const loadingToast = toast.info(`Translating ${untranslatedCount} question(s) to all languages...`, {
                timeout: false,
                closeButton: false,
                hideProgressBar: true
            });
            
            const updateProgressToast = () => {
                setTimeout(() => {
                    const toastContainer = document.querySelector('.Vue-Toastification__toast--info:last-child');
                    if (toastContainer) {
                        const toastBody = toastContainer.querySelector('.Vue-Toastification__toast-body');
                        if (toastBody) {
                            const progressContainer = document.createElement('div');
                            progressContainer.id = progressToastId;
                            progressContainer.style.cssText = 'width: 100%;';
                            
                            const messageDiv = document.createElement('div');
                            messageDiv.textContent = `Translating ${untranslatedCount} question(s) to all languages...`;
                            messageDiv.style.cssText = 'font-size: 0.875rem; color: white; margin-bottom: 0.5rem;';
                            
                            const progressBarContainer = document.createElement('div');
                            progressBarContainer.style.cssText = 'width: 100%; height: 6px; background-color: rgba(255, 255, 255, 0.3); border-radius: 3px; overflow: hidden;';
                            
                            const progressBar = document.createElement('div');
                            progressBar.id = `${progressToastId}-bar`;
                            progressBar.style.cssText = 'height: 100%; background-color: white; border-radius: 3px; transition: width 0.3s ease; width: 0%;';
                            
                            const progressText = document.createElement('div');
                            progressText.id = `${progressToastId}-text`;
                            progressText.textContent = '0%';
                            progressText.style.cssText = 'font-size: 0.75rem; color: white; margin-top: 0.25rem; text-align: center;';
                            
                            progressBarContainer.appendChild(progressBar);
                            progressContainer.appendChild(messageDiv);
                            progressContainer.appendChild(progressBarContainer);
                            progressContainer.appendChild(progressText);
                            
                            toastBody.innerHTML = '';
                            toastBody.appendChild(progressContainer);
                        }
                    }
                }, 100);
            };
            
            updateProgressToast();
            
            const updateProgress = (value) => {
                progressValue = Math.min(100, Math.max(0, value));
                const progressBar = document.getElementById(`${progressToastId}-bar`);
                const progressText = document.getElementById(`${progressToastId}-text`);
                if (progressBar) {
                    progressBar.style.width = `${progressValue}%`;
                }
                if (progressText) {
                    progressText.textContent = `${Math.round(progressValue)}%`;
                }
            };
            
            const startProgressAnimation = () => {
                const totalTranslations = untranslatedCount * Math.max(totalLanguages, 1);
                const estimatedTimePerTranslation = 3000;
                const estimatedDuration = totalTranslations * estimatedTimePerTranslation;
                const targetProgress = 90;
                const steps = 200;
                const increment = targetProgress / steps;
                const stepDuration = Math.max(estimatedDuration / steps, 50);
                
                let currentProgress = 0;
                progressInterval = setInterval(() => {
                    currentProgress += increment;
                    if (currentProgress >= targetProgress) {
                        currentProgress = targetProgress;
                        clearInterval(progressInterval);
                    }
                    updateProgress(currentProgress);
                }, stepDuration);
            };
            
            setTimeout(() => {
                startProgressAnimation();
            }, 200);
            
            try {
                if (!untranslatedQuestionIds || untranslatedQuestionIds.length === 0) {
                    untranslatedQuestionIds = [questionId];
                }
                
                const response = await axios.post(
                    route('questions.translateMultiple', [questionnaireCode]),
                    { 
                        question_ids: untranslatedQuestionIds,
                        force_retranslate: action === 'updated'
                    }
                );
                
                if (progressInterval) {
                    clearInterval(progressInterval);
                }
                updateProgress(100);
                
                setTimeout(() => {
                    toast.dismiss(loadingToast);
                    
                    if (response.data.success) {
                        toast.success(
                            `${untranslatedCount} question(s) successfully translated to ${response.data.total_translated} language(s)`,
                            { timeout: 5000 }
                        );
                        
                        router.reload({ only: ['questionnaire', 'questions'] });
                    } else {
                        toast.error(
                            response.data.error || 'Translation failed',
                            { timeout: 5000 }
                        );
                    }
                }, 500);
            } catch (error) {
                if (progressInterval) {
                    clearInterval(progressInterval);
                }
                toast.dismiss(loadingToast);
                console.error('Translation error:', error);
                const errorMessage = error.response?.data?.error || error.message || 'Translation failed';
                toast.error(errorMessage, { timeout: 5000 });
            }
        };
        
        const messageText = untranslatedCount === 1 
            ? action === 'added' 
                ? 'New question added! Translate to all languages?'
                : `${questionKey} has been edited, do you want to update the translation of all language?`
            : `${untranslatedCount} questions need translation. Translate all?`;
        
        const toastId = toast.info(messageText, {
            timeout: 10000,
            position: 'bottom-right',
            closeOnClick: false,
            pauseOnFocusLoss: false,
            pauseOnHover: false,
            draggable: false,
            closeButton: true,
            hideProgressBar: true
        });
        
        setTimeout(() => {
            const toastContainer = document.querySelector('.Vue-Toastification__toast--info:last-child');
            if (toastContainer) {
                const toastBody = toastContainer.querySelector('.Vue-Toastification__toast-body');
                if (toastBody) {
                    const container = document.createElement('div');
                    container.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;';
                    
                    const messageSpan = document.createElement('span');
                    messageSpan.textContent = messageText;
                    messageSpan.style.cssText = 'font-size: 0.875rem; color: white;';
                    
                    const yesLink = document.createElement('span');
                    yesLink.textContent = 'Yes';
                    yesLink.style.cssText = 'font-size: 0.875rem; font-weight: 600; text-decoration: underline; cursor: pointer; color: white; margin-left: 0.25rem;';
                    yesLink.addEventListener('mouseover', () => {
                        yesLink.style.opacity = '0.8';
                    });
                    yesLink.addEventListener('mouseout', () => {
                        yesLink.style.opacity = '1';
                    });
                    yesLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        translateQuestions();
                        toast.dismiss(toastId);
                    });
                    
                    container.appendChild(messageSpan);
                    container.appendChild(yesLink);
                    toastBody.innerHTML = '';
                    toastBody.appendChild(container);
                }
            }
        }, 100);
    };

    return {
        showTranslateToast
    };
}

