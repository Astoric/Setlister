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
        container: {
            center: true,
            padding: "2rem",
            screens: {
                "2xl": "1400px",
            },
        },
        extend: {
            colors: {
                // Core Backgrounds / Text Colors (from JSX and Figma analysis)
                background: "hsl(var(--background))", // Will map to a custom property in app.css
                foreground: "hsl(var(--foreground))", // Will map to a custom property in app.css

                // UI Elements derived from Figma/JSX
                card: "hsl(var(--card))",
                "card-foreground": "hsl(var(--card-foreground))",
                popover: "hsl(var(--popover))",
                "popover-foreground": "hsl(var(--popover-foreground))",
                border: "hsl(var(--border))",
                input: "hsl(var(--input))",

                // Primary / Accent / Destructive (from JSX explicit hex/color names)
                primary: "hsl(var(--primary))",
                "primary-foreground": "hsl(var(--primary-foreground))",
                secondary: "hsl(var(--secondary))",
                "secondary-foreground": "hsl(var(--secondary-foreground))",
                accent: "hsl(var(--accent))",
                "accent-foreground": "hsl(var(--accent-foreground))",
                destructive: "hsl(var(--destructive))",
                "destructive-foreground": "hsl(var(--destructive-foreground))",

                // Standard Tailwind gray palette (for consistency with shades)
                // Mapping typical gray-300, gray-400, gray-500 from JSX to custom properties
                "gray-300": "hsl(var(--gray-300))", // From JSX
                "gray-400": "hsl(var(--gray-400))", // From JSX
                "gray-500": "hsl(var(--gray-500))", // From JSX
                "gray-600": "hsl(var(--gray-600))", // From JSX
                "gray-700": "hsl(var(--gray-700))", // From JSX
                "gray-800": "hsl(var(--gray-800))", // From JSX

                // Specific colors from your previous palette / matching direct hex from JSX
                "neutral-950": "#121212", // Sidebar bg
                "neutral-900": "#1A1A1A", // Card bg
                "neutral-800": "#212121", // Main content area bg
                "neutral-700": "#282828", // Input bg (from previous app)
                "neutral-600": "#333333", // For general darker neutral elements (e.g. hover)

                "emerald-500": "#1ED760", // Primary accent green from your image / JSX
                "emerald-600": "#1DB954", // Slightly darker green (derived or from JSX)
                "teal-600": "#008060", // Used in your JSX gradient for buttons
                "red-500": "#EF4444", // For errors/destructive (standard Tailwind)
                "red-600": "#DC2626", // Slightly darker red

                "yellow-400": "#FACC15", // For stars (standard Tailwind)
            },
            borderRadius: {
                lg: "var(--radius)", // default: 0.5rem (8px), now customizable via CSS variable
                md: "calc(var(--radius) - 2px)",
                sm: "calc(var(--radius) - 4px)",
                xl: "12px", // Approx 0.75rem for `rounded-xl`
                "2xl": "16px", // Approx 1rem for `rounded-2xl`
            },
            keyframes: {
                "accordion-down": {
                    from: { height: "0" },
                    to: { height: "var(--radix-accordion-content-height)" },
                },
                "accordion-up": {
                    from: { height: "var(--radix-accordion-content-height)" },
                    to: { height: "0" },
                },
            },
            animation: {
                "accordion-down": "accordion-down 0.2s ease-out",
                "accordion-up": "accordion-up 0.2s ease-out",
            },
            fontFamily: {
                sans: ["Inter", ...defaultTheme.fontFamily.sans], // Assuming 'Inter' for modern UI
            },
        },
    },
    plugins: [
        forms,
        require("tailwindcss-animate"), // For animations from Radix/Shadcn-like components
        require("@tailwindcss/typography"), // For typography plugin if needed
        require("@tailwindcss/container-queries"), // For container queries if @container/card-header is used
    ],
};
