export const PAGE_MAP = {
    Questionnaire: 'questionnaire',
    'Question Templates': 'question templates',
    Themes: 'themes',
    Gallery: 'gallery',
    Settings: 'act settings',
    Advanced: 'advanced',
    'Full Preview': 'preview',
};

export function detectWebsitePositionFromSidebar() {
    const active =
        document.querySelector('[data-nav-section][aria-current="page"]') ||
        document.querySelector('[data-nav-section].active') ||
        document.querySelector('.sidebar .active') ||
        document.querySelector('[aria-current="page"]');

    if (active) {
        const label = active.getAttribute('data-nav-section') || active.textContent?.trim();
        if (label) {
            const normalized = (label || '').replace(/\s+/g, ' ').trim();
            return PAGE_MAP[label] || PAGE_MAP[normalized] || null;
        }
    }

    return null;
}

export function detectWebsitePositionFromPath(pathname = window.location.pathname) {
    const pathSegments = pathname.split('/').filter(Boolean);
    let firstSegment = pathSegments[0] || '';
    const secondSegment = pathSegments[1] || '';
    const thirdSegment = pathSegments[2] || '';

    if (firstSegment === 'survey' && secondSegment && thirdSegment) {
        const pageMapping = {
            questions: 'questionnaire',
            templates: 'question templates',
            themes: 'themes',
            gallery: 'gallery',
            settings: 'act settings',
            advanced: 'advanced',
            preview: 'preview',
        };

        return pageMapping[thirdSegment] || `${firstSegment} ${thirdSegment}`;
    }

    if (firstSegment === 'folders' && pathSegments.length === 1) {
        firstSegment = 'project';
    } else if (firstSegment === 'folders' || firstSegment === 'surveys') {
        firstSegment = firstSegment.replace(/s$/, '');
    } else if (firstSegment === 'projects') {
        firstSegment = 'dashboard';
    } else if (firstSegment === 'teams') {
        firstSegment = 'project settings';
    }

    if (firstSegment === 'survey' && secondSegment && !thirdSegment) {
        return 'survey';
    }

    return firstSegment || 'ACT';
}

export function formatTimestamp(timestamp, now = new Date()) {
    if (!timestamp) return '';

    try {
        const date = new Date(timestamp);
        if (Number.isNaN(date.getTime())) return '';

        const diffMs = now - date;
        const diffSeconds = Math.floor(diffMs / 1000);

        if (diffSeconds < 10) return 'Just now';

        const isToday = date.toDateString() === now.toDateString();
        const isThisYear = date.getFullYear() === now.getFullYear();

        if (isToday) {
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
            });
        }

        if (isThisYear) {
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
            });
        }

        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
    } catch (e) {
        console.warn('Failed to format timestamp:', e);
        return '';
    }
}

export function truncateText(text, maxLength) {
    if (!text || text.length <= maxLength) return text;
    return `${text.substring(0, maxLength)}...`;
}

export function stripHtml(html) {
    if (!html) return '';
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

export function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${Math.round((bytes / Math.pow(k, i)) * 100) / 100} ${sizes[i]}`;
}

export const clamp = (n, min, max) => Math.min(Math.max(n, min), max);
