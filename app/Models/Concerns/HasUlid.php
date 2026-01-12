<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\Ulid;

trait HasUlid
{
    public static function bootHasUlid(): void
    {
        static::creating(static function (Model $model): void {
            if (($model->ulid ?? null) === null || $model->ulid === '') {
                $model->ulid = Ulid::generate();
            }
        });
    }

    /**
     * Get the route key for the model.
     *
     * @psalm-return 'ulid'
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Retrieve the model for a bound value.
     * Supports both ULID and integer ID for backward compatibility.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field ??= $this->getRouteKeyName();

        // If field is 'ulid', try ULID first, then fall back to integer ID for backward compatibility
        if ($field === 'ulid') {
            // Try ULID first
            $model = static::where('ulid', $value)->first();
            if ($model) {
                return $model;
            }

            // Fall back to integer ID if value is numeric (backward compatibility)
            if (is_numeric($value)) {
                return static::where('id', (int) $value)->first();
            }
        }

        return static::where($field, $value)->first();
    }

    /**
     * Scope a query to find by ULID.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function scopeByUlid(Builder $query, string $ulid): Builder
    {
        return $query->where('ulid', $ulid);
    }
}
