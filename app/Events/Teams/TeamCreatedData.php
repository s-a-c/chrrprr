<?php

declare(strict_types=1);

namespace App\Events\Teams;

/**
 * Data object for TeamCreated event optional parameters.
 *
 * Groups optional parameters to reduce constructor parameter count.
 */
final readonly class TeamCreatedData
{
    public function __construct(
        public ?int $parent_id = null,
        public ?int $tenant_id = null,
        public ?string $state = null,
        public ?string $status = null,
    ) {}
}
