export const CUSTOMER_SUPPORT_STATE_KEY = 'act-chatbot-customer-support-visible';
export const TERMS_MODAL_STATE_KEY = 'act-chatbot-terms-acknowledged';

export function persistCustomerSupportState(visible) {
    try {
        if (visible) {
            localStorage.setItem(CUSTOMER_SUPPORT_STATE_KEY, JSON.stringify(true));
        } else {
            localStorage.removeItem(CUSTOMER_SUPPORT_STATE_KEY);
        }
    } catch (e) {
        console.warn('Failed to persist customer support state', e);
    }
}

export function restoreTermsModalState() {
    try {
        const saved = localStorage.getItem(TERMS_MODAL_STATE_KEY);
        if (saved !== null) {
            return JSON.parse(saved);
        }
    } catch (e) {
        console.warn('Failed to restore terms modal state', e);
    }

    return false;
}

export function persistTermsModalState(acknowledged) {
    try {
        localStorage.setItem(TERMS_MODAL_STATE_KEY, JSON.stringify(acknowledged));
    } catch (e) {
        console.warn('Failed to persist terms modal state', e);
    }
}
