<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SchemaScopedModel;
use App\Enums\TeamType;
use App\Support\Result;
use Override;
use Parental\HasParent;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\HasInternalKeys;
use Stancl\Tenancy\Database\TenantCollection;

final class Enterprise extends Team implements SchemaScopedModel, TenantContract
{
    use HasDomains;
    use HasInternalKeys;
    use HasParent;

    public function canHaveParent(): bool
    {
        return false;
    }

    /**
     * Get the tenant key name (ULID field).
     */
    public function getTenantKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Get the tenant key value (ULID).
     */
    public function getTenantKey(): string
    {
        return $this->ulid;
    }

    /**
     * Create a new tenant collection instance.
     *
     * @param  array<static>  $models
     * @return TenantCollection<static>
     */
    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }

    /**
     * Get the integer key (ID) for the tenant.
     */
    public function getIntKey(): int
    {
        return $this->id;
    }

    /**
     * Run a callback in the context of this tenant.
     *
     * Initializes tenancy for this Enterprise, executes the callback,
     * and restores the previous tenant context or ends tenancy.
     *
     * Returns a Result monad that captures success/failure and provides
     * an audit trail of the tenancy operation.
     *
     * @param  callable(Enterprise): mixed  $callback
     * @return Result<mixed>
     */
    #[Override]
    public function run(callable $callback): Result
    {
        $originalTenant = tenant();

        $ulid = $this->ulid;
        /** @var TenantContract|null $originalTenant */
        $originalTenantTyped = $originalTenant;

        $originalTenantUlid = 'none';
        if ($originalTenantTyped instanceof self) {
            $originalTenantUlid = $originalTenantTyped->ulid;
        }

        return Result::try(
            function () use ($callback, $originalTenantTyped): mixed {
                tenancy()->initialize($this);

                try {
                    return $callback($this);
                } finally {
                    if ($originalTenantTyped !== null) {
                        tenancy()->initialize($originalTenantTyped);
                    } else {
                        tenancy()->end();
                    }
                }
            },
            [
                "Initialized tenancy for Enterprise: {$ulid}",
                "Original tenant: {$originalTenantUlid}",
            ]
        );
    }

    #[Override]
    protected static function booted(): void
    {
        parent::booted();

        self::creating(static function (Enterprise $enterprise): void {
            $enterprise->type = TeamType::ENTERPRISE;
            $enterprise->parent_id = null;
        });
    }

    /**
     * Get the subdomain attribute (first domain's domain value).
     */
    protected function getSubdomainAttribute(): ?string
    {
        return $this->domains()->first()?->domain;
    }
}
