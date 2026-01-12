import { describe, expect, test as it } from 'bun:test';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { FallbackResizeObserver } from './setup.js';

describe('Infrastructure Mocks', () => {
    describe('Pusher Mock', () => {
        it('should exercise subscribe methods', () => {
            const pusher = new Pusher('key');
            const channel = pusher.subscribe('test');
            expect(channel).toBeDefined();
            expect(typeof channel.bind).toBe('function');
            expect(typeof channel.unbind).toBe('function');

            // Exercise the functions
            channel.bind();
            channel.unbind();
            pusher.unsubscribe();
            pusher.disconnect();
        });
    });

    describe('Echo Mock', () => {
        it('should exercise channel methods', () => {
            const echo = new Echo({});
            const channel = echo.channel('test');
            expect(channel).toBeDefined();
            expect(typeof channel.listen).toBe('function');
            expect(typeof channel.subscribed).toBe('function');

            channel.listen();
            channel.subscribed();

            expect(echo.private('test')).toBeDefined();
            expect(echo.join('test')).toBeDefined();
            expect(echo.leave('test')).toBe(echo);
            echo.disconnect();
        });
    });

    describe('TTY Mocks', () => {
        it('should exercise isTTY getters', () => {
            // These should be false as defined in setup.js
            expect(process.stdout.isTTY).toBe(false);
            expect(process.stderr.isTTY).toBe(false);
        });
    });

    describe('ResizeObserver Mock', () => {
        it('should exercise ResizeObserver methods', () => {
            const observer = new ResizeObserver(() => {});
            expect(observer).toBeDefined();
            observer.observe(document.createElement('div'));
            observer.unobserve(document.createElement('div'));
            observer.disconnect();
        });

        it('should exercise FallbackResizeObserver methods for coverage', () => {
            const observer = new FallbackResizeObserver();
            expect(observer).toBeDefined();
            observer.observe();
            observer.unobserve();
            observer.disconnect();
        });
    });
});
