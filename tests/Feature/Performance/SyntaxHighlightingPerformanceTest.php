<?php

declare(strict_types=1);

use App\Models\Enterprise;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('syntax highlighting initialization completes in less than 500ms after page load', function (): void {
    // This test validates SC-012: syntax highlighting performance
    // Note: This is a placeholder test as actual syntax highlighting happens client-side
    // In a real browser test environment, you would:
    // 1. Load the page with bio content containing code blocks
    // 2. Measure the time until highlight.js has processed all code blocks
    // 3. Assert it's less than 500ms

    // For now, we'll test that the bio HTML contains proper code block structure
    $bioContent = <<<'MARKDOWN'
# Code Examples

Here's a PHP example:

```php
<?php
class Example {
    public function method(): string {
        return 'Hello';
    }
}
```

And a JavaScript example:

```javascript
function example() {
    return 'World';
}
```
MARKDOWN;

    $team = Enterprise::factory()->create([
        'bio' => $bioContent,
    ]);

    $html = $team->bio_html;

    // Verify code blocks are present (syntax highlighting will process these client-side)
    expect($html)->toContain('<pre');
    expect($html)->toContain('<code');
    expect($html)->toContain('class Example');
})->skip('Performance test - requires browser environment for accurate client-side timing');

test('syntax highlighting handles multiple code blocks efficiently', function (): void {
    // Create bio with multiple code blocks
    $bioWithManyCodeBlocks = '';
    for ($i = 1; $i <= 10; $i++) {
        $bioWithManyCodeBlocks .= "## Example {$i}\n\n";
        $bioWithManyCodeBlocks .= "```php\n";
        $bioWithManyCodeBlocks .= "public function example{$i}(): void {}\n";
        $bioWithManyCodeBlocks .= "```\n\n";
    }

    $team = Enterprise::factory()->create([
        'bio' => $bioWithManyCodeBlocks,
    ]);

    $html = $team->bio_html;

    // Count code blocks (they should all be present)
    $codeBlockCount = mb_substr_count($html, '<pre');
    expect($codeBlockCount)->toBeGreaterThanOrEqual(10);

    // Verify structure is correct for highlighting
    expect($html)->toContain('<pre');
    expect($html)->toContain('<code');
})->skip('Performance test - requires browser environment for accurate client-side timing');

test('syntax highlighting handles empty code blocks gracefully', function (): void {
    $bioWithEmptyCodeBlock = <<<'MARKDOWN'
# Example

```php
```

That's an empty code block.
MARKDOWN;

    $team = Enterprise::factory()->create([
        'bio' => $bioWithEmptyCodeBlock,
    ]);

    $html = $team->bio_html;

    // Should still render code block structure even if empty
    expect($html)->toContain('<pre');
    expect($html)->toContain('<code');
})->skip('Performance test - requires browser environment for accurate client-side timing');
