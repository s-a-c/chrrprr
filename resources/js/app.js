/** @format */

import './bootstrap';
import hljs from 'highlight.js';

// Initialize syntax highlighting for code blocks
const highlightCodeBlocks = (): void => {
    document.querySelectorAll('pre code').forEach((el) => {
        if (!el.dataset.highlighted) {
            hljs.highlightElement(el as HTMLElement);
            el.dataset.highlighted = 'yes';
        }
    });
};

// Initial load
document.addEventListener('DOMContentLoaded', highlightCodeBlocks);

// Livewire 4 SPA navigation
document.addEventListener('livewire:navigated', highlightCodeBlocks);
