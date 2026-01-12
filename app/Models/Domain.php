<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

/**
 * @use HasFactory<Factory>
 */
final class Domain extends BaseDomain
{
    use HasFactory;

    /** @var array<string> */
    protected $guarded = [];
}
