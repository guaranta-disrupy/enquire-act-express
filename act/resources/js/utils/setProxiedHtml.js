// Rewrite cross-origin <img src> to same-origin proxy for caching in editor
export function setProxiedHtml(html) {
    try {
        if (!html || typeof html !== "string") return html;
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const imgs = doc.querySelectorAll("img[src]");
        imgs.forEach((img) => {
            try {
                const u = new URL(
                    img.getAttribute("src"),
                    window.location.origin
                );
                if (u.origin !== window.location.origin) {
                    img.setAttribute(
                        "src",
                        `/gallery-proxy?u=${encodeURIComponent(u.href)}`
                    );
                }
            } catch (_) {}
        });
        return doc.body.innerHTML;
    } catch (_) {
        return html;
    }
}
