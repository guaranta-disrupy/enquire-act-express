import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import typography from "@tailwindcss/typography";

// Escala verde NextSensus no lugar do laranja da marca antiga (ação = 600).
const nsGreen = {
    50: "#eef6f2",
    100: "#d6eadf",
    200: "#aed5c0",
    300: "#7fbb9c",
    400: "#4f9c78",
    500: "#2f8562",
    600: "#257556",
    700: "#1d5e45",
    800: "#174a37",
    900: "#123a2c",
    950: "#0a2219",
};

module.exports = {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Inter", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                orange: nsGreen,
            },
        },
    },

    plugins: [forms, typography, require("daisyui")],
};
