import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                neutral: {
                    950: "#121212", // Darkest background for sidebar
                    900: "#1A1A1A", // Dark background for header/main container
                    800: "#212121", // Lighter background for main content card
                    700: "#282828", // Even lighter for input fields, etc.
                    400: "#A0A0A0", // Text on dark background
                },
                accent: {
                    500: "#1ED760", // Bright green for active states/buttons
                },
                orange: {
                    // For the logout text
                    500: "#F59E0B",
                },
                yellow: {
                    // For the notification bell
                    500: "#FDE047",
                },
            },
        },
    },

    plugins: [forms],
};
