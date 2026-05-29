// Extract all template variable names inside {{ }} from an array of tests
// tests: Array of test objects as passed via props.tests in Surveys2/Index.vue
export function getVariablesFromTests(tests) {
    if (!Array.isArray(tests) || tests.length === 0) {
        return [];
    }

    const resultSet = new Set();
    const mustacheRegex = /{{\s*([^}]+?)\s*}}/g;

    const extractFromString = (str) => {
        if (!str || typeof str !== "string") {
            return;
        }
        let match;
        while ((match = mustacheRegex.exec(str)) !== null) {
            const inner = match[1].trim();
            if (!inner) continue;

            // Split on common separators in case there are multiple vars:
            // e.g. "{{ brand }}, {{ model }}" or "{{brand|fallback}}"
            inner.split(/[,\|]/).forEach((token) => {
                const name = token.trim();
                if (name) {
                    resultSet.add(name);
                }
            });
        }
    };

    tests.forEach((test) => {
        if (!test || typeof test !== "object") return;

        // Primes: prime.prime
        if (Array.isArray(test.primes)) {
            test.primes.forEach((prime) => {
                if (prime && typeof prime === "object") {
                    extractFromString(prime.prime);
                }
            });
        }

        // Targets: target.target
        if (Array.isArray(test.targets)) {
            test.targets.forEach((target) => {
                if (target && typeof target === "object") {
                    extractFromString(target.target);
                }
            });
        }

        const config = test.config || {};

        // Config title and bottom_text
        extractFromString(config.title);
        extractFromString(config.bottom_text);
    });

    return Array.from(resultSet);
}
