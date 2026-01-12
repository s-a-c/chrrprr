<?php

declare(strict_types=1);

use App\Support\Html\TeamBioRenderer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\LaravelMarkdown\MarkdownRenderer;

test('returns null for empty bio', function (): void {
    $renderer = new TeamBioRenderer(
        resolve(MarkdownRenderer::class)
    );

    expect($renderer->render(null))->toBeNull();
    expect($renderer->render(''))->toBeNull();
    expect($renderer->render('   '))->not->toBeNull(); // Whitespace is not empty
});

test('renders markdown to html', function (): void {
    $renderer = new TeamBioRenderer(
        resolve(MarkdownRenderer::class)
    );

    $html = $renderer->render('# Heading');

    expect($html)->toContain('<h1>Heading</h1>');
});

test('sanitizes html content', function (): void {
    $renderer = new TeamBioRenderer(
        resolve(MarkdownRenderer::class)
    );

    $html = $renderer->render('Hello <script>alert("XSS")</script>');

    expect($html)->not->toContain('<script>');
    expect($html)->toContain('Hello');
});

test('handles binding resolution exception gracefully', function (): void {
    $mockRenderer = Mockery::mock(MarkdownRenderer::class);
    $mockRenderer->shouldReceive('toHtml')
        ->andThrow(new BindingResolutionException('Target class [config] does not exist'));

    $renderer = new TeamBioRenderer($mockRenderer);

    // Should return null gracefully for config errors (mutation testing scenario)
    expect($renderer->render('test'))->toBeNull();
});

test('rethrows non-config binding resolution exceptions', function (): void {
    $mockRenderer = Mockery::mock(MarkdownRenderer::class);
    $mockRenderer->shouldReceive('toHtml')
        ->andThrow(new BindingResolutionException('Some other error'));

    $renderer = new TeamBioRenderer($mockRenderer);

    expect(fn (): ?string => $renderer->render('test'))->toThrow(BindingResolutionException::class);
});
