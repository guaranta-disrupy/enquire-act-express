import { surveyTestState } from "./surveyTestStore";
import { useLogger } from "./logger.js";
const { _log } = useLogger();

const isTesting = () => {
    return (
        surveyTestState.query.id === "test" ||
        surveyTestState.query?.preview ||
        (!("list_index" in surveyTestState.query) &&
            !("status_index" in surveyTestState.query))
    );
};

/**
 * WHEN DONE TAKING THE SURVEY, SAVE IT TO THE SERVER.
 * @param {Object} response - RESPONSES / RESULTS
 * @returns {Promise}
 */
export const onSurveyItemEnd = async (response) => {
    _log("response: ", response);

    // If no detail in response, just increment survey index and redirect
    if (response.detail === null) {
        surveyTestState.surveyIndex++;
        surveyTestState.testState = "redirecting";
        requestRedirect();
        return;
    }

    try {
        // SEND REQUEST ON FINISHED ONE SURVEY
        surveyTestState.testState = isTesting() ? "survey_completed" : "saving";
        const apiResponse = await fetchWithRetry(
            "/api/v1/save",
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${surveyTestState.token}`,
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(response.detail),
            },
            2,
            1000,
            response
        );

        const result = await apiResponse.json();
        _log(result);

        // AFTER A SUCCESS SAVE, CLEAR LOCALSTORAGE AND INCREMENT THE SURVEY INDEX
        surveyTestState.clearCompletedSurvey();
        surveyTestState.token = result.token;
        surveyTestState.surveyIndex++;
        requestRedirect();
    } catch (err) {
        if (err.message === "Offline") {
            // Offline error is handled by showing the DatabaseError component.
            // We just log it here and prevent the generic error state.
            _log("Offline error caught in onSurveyItemEnd.");
            return;
        }
        surveyTestState.testState = "error_saving";
        _log(err);
    }
};

/**
 * FETCH WITH RETRY
 * @param {string} url
 * @param {Object} options
 * @param {number} retries
 * @param {number} delay
 * @returns {Promise}
 */
const fetchWithRetry = async (
    url,
    options,
    retries = 2,
    delay = 1000,
    originalResponse = null
) => {
    let lastError;

    for (let i = 0; i < retries; i++) {
        try {
            const response = await fetch(url, options);
            if (response.status === 500 || response.status === 503)
                throw new Error("Server error");
            return response;
        } catch (err) {
            lastError = err;

            // Check for offline status on failure
            if (!navigator.onLine) {
                surveyTestState.isOffline = true;
                _log("Submission failed due to lost connection.");
                throw new Error("Offline"); // Propagate specific error to onSurveyItemEnd
            }

            if (i < retries - 1) {
                await new Promise((res) => setTimeout(res, delay));
            }
        }
    }
    showErrorModal(originalResponse, false);
    throw lastError;
};

/**
 * IF SURVEY INDEX REACHES THE END THEN REQUEST FOR REDIRECT LINK / URL
 * IF NOT, DO NOTHING
 */
export const requestRedirect = async () => {
    if (surveyTestState.surveyIndex >= surveyTestState.surveyCount) {
        // Survey is complete
        document.title = "Done!";
        surveyTestState.testState = isTesting()
            ? "survey_completed"
            : "redirecting";
        const params = new URLSearchParams();
        for (const key in surveyTestState.query) {
            if (Object.hasOwnProperty.call(surveyTestState.query, key)) {
                params.append(key, surveyTestState.query[key]);
            }
        }

        try {
            // SEND REQUEST FOR REDIRECT URL
            const response = await fetch(
                // "/api/v1/goWhere" + window.location.search,
                "/api/v1/goWhere?" + params.toString(),
                {
                    method: "GET",
                    headers: {
                        Authorization: `Bearer ${surveyTestState.token}`,
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                }
            );

            const data = await response.json();
            _log(data);

            if (data.external === true) {
                setTimeout(() => {
                    window.location.href = data.link;
                }, 2000);
            } else {
                if (window.location && window.location.href) {
                    window.location.href = data.link;
                }
            }
        } catch (err) {
            // IF ERROR ON REDIRECT, SHOW ERROR MODAL
            surveyTestState.testState = "error_redirecting";
            _log(err);
        }
    }
};

/**
 * SUBMIT SURVEY RESPONSES TO SERVER
 * This function should be called when the survey is complete
 */
export const submitSurveyResponses = async () => {
    const response = {
        detail: {
            survey_id: surveyTestState.surveyData.surveys[0].survey_id,
            responses: surveyTestState.responses,
            variables: [],
            url: window.location.href,
            started_at: new Date().toUTCString(),
            ended_at: new Date().toUTCString(),
        },
    };

    // Save completed survey data to localStorage before submitting
    surveyTestState.saveCompletedSurvey(response);

    await onSurveyItemEnd(response);
};

/**
 * INITIALIZE SURVEY SUBMISSION
 * This should be called when the survey is marked as complete
 */
export const initializeSurveySubmission = () => {
    surveyTestState.isSurveyComplete = true;
    submitSurveyResponses();
};

/**
 * RETRY FAILED SURVEY SUBMISSION
 * This function should be called when containers come back online
 */
export const retryFailedSubmission = async () => {
    const completedData = surveyTestState.loadCompletedSurvey();

    if (!completedData) {
        _log("No failed survey submission found");
        return false;
    }

    try {
        _log("Retrying failed survey submission...");

        // Restore the entire survey state from localStorage
        const restored = surveyTestState.restoreFromStorage();
        if (!restored) {
            _log("Failed to restore survey state from localStorage");
            return false;
        }

        // Submit the saved payload
        await onSurveyItemEnd(completedData.savePayload);

        _log("Failed survey submission retried successfully");
        return true;
    } catch (error) {
        _log("Failed to retry survey submission:", error);
        return false;
    }
};

/**
 * CHECK FOR FAILED SUBMISSIONS ON PAGE LOAD
 * This should be called when the page loads to check for any failed submissions
 */
export const checkForFailedSubmissions = () => {
    _log("Checking for failed submissions...");
    const hasCompletedSurvey = surveyTestState.hasCompletedSurvey();
    _log("Has completed survey in localStorage:", hasCompletedSurvey);

    if (hasCompletedSurvey) {
        _log("Found failed survey submission, ready to retry when online");
        return true;
    }
    return false;
};

/**
 * SHOW ERROR MODAL
 * @param {Object} response - Original response for retry
 * @param {boolean} isRedirectError - Whether this is a redirect error
 */
const showErrorModal = (response, isRedirectError = false) => {
    // Create a custom event to show error modal
    const event = new CustomEvent("survey.error", {
        detail: {
            headerText: "Saving failed",
            message: surveyTestState.texts.error_saving,
            buttons: [
                {
                    text: "RETRY",
                    onClick: () => {
                        if (isRedirectError) {
                            surveyTestState.testState = "redirecting";
                            requestRedirect();
                        } else {
                            surveyTestState.testState = "saving";
                            onSurveyItemEnd(response);
                        }
                    },
                },
            ],
        },
    });

    window.dispatchEvent(event);
};
