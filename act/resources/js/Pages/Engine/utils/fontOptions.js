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
