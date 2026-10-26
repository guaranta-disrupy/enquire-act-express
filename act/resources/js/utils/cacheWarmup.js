import { BASE_PATH } from "./basePath";
// Cache warm-up utilities
// - Extract image URLs from HTML
// - Build manifest from tests and survey
// - Warm cache with concurrency and session dedupe

export function extractImageUrlsFromHtml(htmlString) {
    const urls = [];
    if (!htmlString || typeof htmlString !== "string") return urls;
    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlString, "text/html");
        const imgs = doc.querySelectorAll("img[src]");
        imgs.forEach((img) => {
            try {
                const u = new URL(
                    img.getAttribute("src"),
                    window.location.origin
                );
                if (u.protocol === "http:" || u.protocol === "https:")
                    urls.push(u.href);
            } catch (_) {}
        });
    } catch (_) {}
    return urls;
}

export function buildImageManifestFromTests(testsProp, surveyProp) {
    const out = new Set();
    const arr = Array.isArray(testsProp)
        ? testsProp
        : testsProp
        ? [testsProp]
        : [];
    arr.forEach((test) => {
        // Descriptive title images
        extractImageUrlsFromHtml(test?.config?.title).forEach((u) =>
            out.add(u)
        );
        // Primes
        (test?.primes || []).forEach((p) => {
            extractImageUrlsFromHtml(p?.prime).forEach((u) => out.add(u));
        });
        // Targets
        (test?.targets || []).forEach((t) => {
            extractImageUrlsFromHtml(t?.target).forEach((u) => out.add(u));
        });
    });
    // TODO: include survey-level assets (background/logo/theme) when available
    console.log("out", out);
    // console.log("out", Array.from(out).map(toProxyIfCrossOrigin));
    return Array.from(out).map(toProxyIfCrossOrigin);
}

async function warmCache(urls, concurrency = 8) {
    if (!Array.isArray(urls) || urls.length === 0) return;
    const seen = new Set();
    const queue = urls.filter((u) => {
        if (!u || seen.has(u)) return false;
        seen.add(u);
        return true;
    });
    let index = 0;
    async function worker() {
        while (index < queue.length) {
            const current = queue[index++];
            try {
                const isSameOrigin = (() => {
                    try {
                        const u = new URL(current, window.location.origin);
                        return u.origin === window.location.origin;
                    } catch (_) {
                        return false;
                    }
                })();
                const opts = isSameOrigin
                    ? {
                          credentials: "same-origin",
                          cache: "force-cache",
                          headers: { Accept: "image/*,*/*" },
                      }
                    : {
                          // For cross-origin assets, use no-cors to avoid CORS errors
                          mode: "no-cors",
                          cache: "force-cache",
                          // no credentials for third-party
                      };
                await fetch(current, opts);
            } catch (_) {}
        }
    }
    const workers = Array.from(
        { length: Math.min(concurrency, queue.length) },
        worker
    );
    await Promise.all(workers);
}

// Session-level warmed set to avoid rewarming during navigation
const warmed = new Set();
export async function warmCacheSession(urls) {
    const missing = (urls || []).filter((u) => !!u && !warmed.has(u));
    if (missing.length === 0) return;
    await warmCache(missing);
    missing.forEach((u) => warmed.add(u));
}

// Background decode to eliminate layout jank on first paint after load
const decoded = new Set();

function createImageDecode(url) {
    return new Promise((resolve) => {
        try {
            const img = new Image();
            // Resolve on load/error to avoid hanging; prefer decode() when available
            img.onload = () => resolve(true);
            img.onerror = () => resolve(false);
            img.src = url;
            if (typeof img.decode === "function") {
                img.decode()
                    .then(() => resolve(true))
                    .catch(() => resolve(false));
            }
            // Safety timeout in case decode never settles (older browsers)
            setTimeout(() => resolve(false), 4000);
        } catch (_) {
            resolve(false);
        }
    });
}

export async function decodeImagesInBackground(urls, concurrency = 4) {
    if (!Array.isArray(urls) || urls.length === 0) return;
    const queue = urls.filter((u) => !!u && !decoded.has(u));
    if (queue.length === 0) return;

    let index = 0;
    async function worker() {
        while (index < queue.length) {
            const current = queue[index++];
            try {
                await createImageDecode(current);
                decoded.add(current);
            } catch (_) {}
        }
    }

    // If requestIdleCallback exists, schedule workers during idle periods
    const run = () =>
        Promise.all(
            Array.from({ length: Math.min(concurrency, queue.length) }, worker)
        ).then(() => void 0);

    if (typeof window !== "undefined" && "requestIdleCallback" in window) {
        window.requestIdleCallback(
            () => {
                run();
            },
            { timeout: 3000 }
        );
    } else {
        // Fallback: microtask soon
        setTimeout(() => {
            run();
        }, 0);
    }
}

// Convenience: warm first, then decode (fire-and-forget decoding)
export async function warmAndDecodeSession(urls) {
    await warmCacheSession(urls);
    decodeImagesInBackground(urls);
}

function toProxyIfCrossOrigin(url) {
    try {
        const u = new URL(url, window.location.origin);
        if (u.origin !== window.location.origin) {
            return `${BASE_PATH}/gallery-proxy?u=${encodeURIComponent(u.href)}`;
        }
        return u.href;
    } catch (_) {
        return url;
    }
}
