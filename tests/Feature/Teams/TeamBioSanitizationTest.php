<?php

declare(strict_types=1);

use App\Models\Enterprise;

test('sanitizes script tags from bio content', function (): void {
    $team = Enterprise::factory()->create([
        'bio' => 'Hello <script>alert("XSS")</script> World',
    ]);

    expect($team->bio_html)->not->toContain('<script>');
    expect($team->bio_html)->not->toContain('alert');
    expect($team->bio_html)->toContain('Hello');
    expect($team->bio_html)->toContain('World');
});

test('sanitizes onclick attributes from bio content', function (): void {
    $team = Enterprise::factory()->create([
        'bio' => 'Click <a onclick="alert(\'XSS\')" href="#">here</a>',
    ]);

    expect($team->bio_html)->not->toContain('onclick');
    expect($team->bio_html)->toContain('Click');
});

test('preserves valid markdown and HTML elements', function (): void {
    $team = Enterprise::factory()->create([
        'bio' => '# Heading'."\n\n".'**Bold** and *italic* text with [link](https://example.com)',
    ]);

    $html = $team->bio_html;

    // Check that markdown is parsed and sanitized, preserving valid HTML elements
    expect($html)->toContain('<h1>Heading</h1>');
    expect($html)->toContain('<strong>Bold</strong>');
    expect($html)->toContain('<em>italic</em>');
    expect($html)->toContain('<a');
    expect($html)->toContain('href="https://example.com"');
});

test('sanitizes javascript protocol in links', function (): void {
    $team = Enterprise::factory()->create([
        'bio' => '[Click me](javascript:alert("XSS"))',
    ]);

    $html = $team->bio_html;

    expect($html)->not->toContain('javascript:');
});

test('sanitizes iframe tags from bio content', function (): void {
    $team = Enterprise::factory()->create([
        'bio' => 'Content <iframe src="evil.com"></iframe>',
    ]);

    expect($team->bio_html)->not->toContain('<iframe');
});
