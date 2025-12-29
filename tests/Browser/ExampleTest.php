<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can visit the homepage', function (): void {
    $page = visit('/');

    $page->assertSee('Laravel');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});
