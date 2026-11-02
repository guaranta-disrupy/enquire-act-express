// Central font configuration for the Engine.
// These families must match the names used in `resources/css/fonts.css`.

export const ENGINE_FONT_FAMILIES = [
    {
        label: "Nunito (Default)",
        value: "Nunito",
    },
    {
        label: "Roboto",
        value: "Roboto",
    },
    {
        label: "Inter",
        value: "Inter",
    },
    {
        label: "Open Sans",
        value: "Open Sans",
    },
    {
        label: "Montserrat",
        value: "Montserrat",
    },
    {
        label: "Noto Sans",
        value: "Noto Sans",
    },
    {
        label: "Mulish",
        value: "Mulish",
    },
    {
        label: "Merriweather",
        value: "Merriweather",
    },
    {
        label: "Source Serif 4",
        value: "Source Serif 4",
    },
    {
        label: "Cormorant Garamond",
        value: "Cormorant Garamond",
    },
    {
        label: "Lato",
        value: '"Lato", sans-serif',
    },
    {
        label: "Poppins",
        value: '"Poppins", sans-serif',
    },
    {
        label: "Nunito Sans",
        value: '"Nunito Sans Variable", sans-serif',
    },
    {
        label: "DM Sans",
        value: '"DM Sans Variable", sans-serif',
    },
    {
        label: "Raleway",
        value: '"Raleway Variable", sans-serif',
    },
    {
        label: "Rubik",
        value: '"Rubik Variable", sans-serif',
    },
    {
        label: "Work Sans",
        value: '"Work Sans Variable", sans-serif',
    },
    {
        label: "Outfit",
        value: '"Outfit Variable", sans-serif',
    },
    {
        label: "Plus Jakarta Sans",
        value: '"Plus Jakarta Sans Variable", sans-serif',
    },
    {
        label: "Oswald",
        value: '"Oswald Variable", sans-serif',
    },
    {
        label: "Source Sans 3",
        value: '"Source Sans 3 Variable", sans-serif',
    },
    {
        label: "Playfair Display",
        value: '"Playfair Display Variable", serif',
    },
    {
        label: "Libre Baskerville",
        value: '"Libre Baskerville", serif',
    },
    // Fontes do sistema: não são carregadas, dependem do aparelho (caem na
    // alternativa da lista onde não existem, ex.: Android).
    {
        label: "Arial Black (system)",
        value: '"Arial Black", Arial, sans-serif',
    },
    {
        label: "Tahoma (system)",
        value: 'Tahoma, Verdana, sans-serif',
    },
    {
        label: "Segoe UI (system)",
        value: '"Segoe UI", system-ui, sans-serif',
    },
    {
        label: "Century Gothic (system)",
        value: '"Century Gothic", "Avenir Next", sans-serif',
    },
    {
        label: "Franklin Gothic Medium (system)",
        value: '"Franklin Gothic Medium", "Arial Narrow", sans-serif',
    },
    {
        label: "Candara (system)",
        value: 'Candara, Calibri, sans-serif',
    },
    {
        label: "Optima (system)",
        value: 'Optima, Candara, sans-serif',
    },
    {
        label: "Lucida Console (system)",
        value: '"Lucida Console", Monaco, monospace',
    },
];

// Discrete weights we expose in the UI. All variable fonts we load support
// this range, so it is safe across families.
export const ENGINE_FONT_WEIGHTS = [
    { label: "300 (Light)", value: "300" },
    { label: "400 (Normal)", value: "400" },
    { label: "500 (Medium)", value: "500" },
    { label: "700 (Bold)", value: "700" },
    { label: "900 (Black)", value: "900" },
];
