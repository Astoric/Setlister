import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

/**
 * Utility function to conditionally join Tailwind CSS classes and merge conflicts.
 * Mimics the 'cn' utility often found in Shadcn/UI or similar component libraries.
 * @param {...(string | string[] | Record<string, boolean> | null | undefined)} inputs
 * @returns {string} The merged CSS class string.
 */
export function cn(...inputs) {
    return twMerge(clsx(inputs));
}
