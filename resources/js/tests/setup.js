import { GlobalRegistrator } from '@happy-dom/global-registrator';
import '@testing-library/jest-dom';
import { mock } from 'bun:test';

// Register Happy DOM globals (window, document, etc.)
GlobalRegistrator.register();

// Mock pusher-js before any imports - using mock.module() for setup files
mock.module('pusher-js', () => ({
    default: class MockPusher {
        constructor() {}
        subscribe() {
            return {
                bind: () => {},
                unbind: () => {},
            };
        }
        unsubscribe() {}
        disconnect() {}
    },
}));

// Mock laravel-echo before any imports - using mock.module() for setup files
mock.module('laravel-echo', () => ({
    default: class MockEcho {
        constructor() {}
        channel() {
            return {
                listen: () => {},
                subscribed: () => {},
            };
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

// Mock ResizeObserver if not available
if (!global.ResizeObserver) {
    global.ResizeObserver = class ResizeObserver {
        observe() {}
        unobserve() {}
        disconnect() {}
    };
}
