import { beforeEach, describe, expect, test as it, vi } from 'bun:test';

// Mock highlight.js - must use factory function that returns the mock
vi.mock('highlight.js', () => {
    const mockHighlightElement = vi.fn();
    return {
        default: {
            highlightElement: mockHighlightElement,
        },
    };
});

import hljs from 'highlight.js';
// Import after mocking
import { highlightCodeBlocks } from '../app.js';

describe('App Module', () => {
    beforeEach(() => {
        // Reset DOM and mocks before each test
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    describe('DOM environment', () => {
        it('should have DOM available', () => {
            expect(document).toBeDefined();
            expect(document.body).toBeDefined();
        });

        it('should handle DOM manipulation', () => {
            const div = document.createElement('div');
            div.textContent = 'Test';
            document.body.appendChild(div);

            expect(document.body.querySelector('div')).toBeTruthy();
            expect(document.body.querySelector('div')?.textContent).toBe('Test');
        });
    });

    describe('highlightCodeBlocks function', () => {
        it('should highlight code blocks when present', () => {
            // Create a code block element
            const code = document.createElement('code');
            code.textContent = 'const x = 1;';
            const pre = document.createElement('pre');
            pre.appendChild(code);
            document.body.appendChild(pre);

            const mockHljs = { highlightElement: hljs.highlightElement };
            highlightCodeBlocks(mockHljs);

            expect(hljs.highlightElement).toHaveBeenCalledTimes(1);
            expect(hljs.highlightElement).toHaveBeenCalledWith(code);
            expect(code.dataset.highlighted).toBe('yes');
        });

        it('should highlight multiple code blocks', () => {
            // Create multiple code blocks
            const code1 = document.createElement('code');
            code1.textContent = 'const x = 1;';
            const pre1 = document.createElement('pre');
            pre1.appendChild(code1);

            const code2 = document.createElement('code');
            code2.textContent = 'const y = 2;';
            const pre2 = document.createElement('pre');
            pre2.appendChild(code2);

            document.body.appendChild(pre1);
            document.body.appendChild(pre2);

            const mockHljs = { highlightElement: hljs.highlightElement };
            highlightCodeBlocks(mockHljs);

            expect(hljs.highlightElement).toHaveBeenCalledTimes(2);
            expect(hljs.highlightElement).toHaveBeenCalledWith(code1);
            expect(hljs.highlightElement).toHaveBeenCalledWith(code2);
            expect(code1.dataset.highlighted).toBe('yes');
            expect(code2.dataset.highlighted).toBe('yes');
        });

        it('should not re-highlight already highlighted elements', () => {
            // Create a code block that's already highlighted
            const code = document.createElement('code');
            code.textContent = 'const x = 1;';
            code.dataset.highlighted = 'yes';
            const pre = document.createElement('pre');
            pre.appendChild(code);
            document.body.appendChild(pre);

            const mockHljs = { highlightElement: hljs.highlightElement };
            highlightCodeBlocks(mockHljs);

            expect(hljs.highlightElement).not.toHaveBeenCalled();
            expect(code.dataset.highlighted).toBe('yes');
        });

        it('should handle pages with no code blocks', () => {
            // Empty page should not cause errors
            document.body.innerHTML = '<div>No code blocks here</div>';

            const mockHljs = { highlightElement: hljs.highlightElement };
            highlightCodeBlocks(mockHljs);

            expect(hljs.highlightElement).not.toHaveBeenCalled();
        });

        it('should only highlight code blocks within pre elements', () => {
            // Create a code element not inside a pre
            const code = document.createElement('code');
            code.textContent = 'const x = 1;';
            document.body.appendChild(code);

            // Create a code element inside a pre
            const codeInPre = document.createElement('code');
            codeInPre.textContent = 'const y = 2;';
            const pre = document.createElement('pre');
            pre.appendChild(codeInPre);
            document.body.appendChild(pre);

            const mockHljs = { highlightElement: hljs.highlightElement };
            highlightCodeBlocks(mockHljs);

            // Only the code inside pre should be highlighted
            expect(hljs.highlightElement).toHaveBeenCalledTimes(1);
            expect(hljs.highlightElement).toHaveBeenCalledWith(codeInPre);
            expect(codeInPre.dataset.highlighted).toBe('yes');
            expect(code.dataset.highlighted).toBeUndefined();
        });

        it('should handle mixed highlighted and unhighlighted code blocks', () => {
            // Create one already highlighted and one not highlighted
            const code1 = document.createElement('code');
            code1.textContent = 'const x = 1;';
            code1.dataset.highlighted = 'yes';
            const pre1 = document.createElement('pre');
            pre1.appendChild(code1);

            const code2 = document.createElement('code');
            code2.textContent = 'const y = 2;';
            const pre2 = document.createElement('pre');
            pre2.appendChild(code2);

            document.body.appendChild(pre1);
            document.body.appendChild(pre2);

            const mockHljs = { highlightElement: hljs.highlightElement };
            highlightCodeBlocks(mockHljs);

            // Only the unhighlighted one should be processed
            expect(hljs.highlightElement).toHaveBeenCalledTimes(1);
            expect(hljs.highlightElement).toHaveBeenCalledWith(code2);
            expect(code1.dataset.highlighted).toBe('yes');
            expect(code2.dataset.highlighted).toBe('yes');
        });
    });

    describe('event listeners', () => {
        it('should export highlightCodeBlocks function', () => {
            // The function is exported for testability
            // Event listener setup is tested via integration tests (Playwright)
            // For unit testing, we verify the exported function works correctly
            expect(typeof highlightCodeBlocks).toBe('function');
        });
    });
});
