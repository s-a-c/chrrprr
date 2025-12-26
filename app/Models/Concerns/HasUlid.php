<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\Ulid;

trait HasUlid
{
    public static function bootHasUlid(): void
    {
        static::creating(static function (Model $model): void {
            if (empty($model->ulid)) {
                $model->ulid = Ulid::generate();
            }
        });
    }
}
