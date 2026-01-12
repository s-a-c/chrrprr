import './bootstrap';
import hljs from 'highlight.js';

/**
 * Initialize syntax highlighting for code blocks.
 * Finds all <pre><code> elements and highlights them using highlight.js.
 * Skips elements that have already been highlighted (marked with data-highlighted="yes").
 *
 * @param {typeof hljs} highlightLib - The highlight.js library instance (defaults to hljs)
 */
export const highlightCodeBlocks = (highlightLib = hljs) => {
    document.querySelectorAll('pre code').forEach((el) => {
        if (!el.dataset.highlighted) {
            highlightLib.highlightElement(el);
            el.dataset.highlighted = 'yes';
        }
    });
};

/**
 * Initialize syntax highlighting on page load and Livewire navigation.
 */
const initSyntaxHighlighting = () => {
    // Initial load
    document.addEventListener('DOMContentLoaded', highlightCodeBlocks);

    // Livewire 4 SPA navigation
    document.addEventListener('livewire:navigated', highlightCodeBlocks);
};

// Auto-initialize when module is loaded
initSyntaxHighlighting();
