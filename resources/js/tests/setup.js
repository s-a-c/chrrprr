import { GlobalRegistrator } from '@happy-dom/global-registrator';
import '@testing-library/jest-dom';
import { mock } from 'bun:test';

GlobalRegistrator.register();

mock.module('pusher-js', () => ({
    default: class {
        subscribe() {
            return { bind: () => {}, unbind: () => {} };
        }
        unsubscribe() {}
        disconnect() {}
    },
}));

mock.module('laravel-echo', () => ({
    default: class {
        channel() {
            return { listen: () => {}, subscribed: () => {} };
        }
        private() {
            return this.channel();
        }
        join() {
            return this.channel();
        }
        leave() {
            return this;
        }
        disconnect() {}
    },
}));

mock.module('highlight.js', () => ({
    default: { highlightElement: mock(() => {}) },
}));

if (typeof process !== 'undefined') {
    const fix = (s) => {
        if (s && !Object.getOwnPropertyDescriptor(s, 'isTTY')) {
            Object.defineProperty(s, 'isTTY', { get: () => false, configurable: true });
        }
    };
    fix(process.stdout);
    fix(process.stderr);
}

export class FallbackResizeObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
}

global.ResizeObserver = global.ResizeObserver || FallbackResizeObserver;
