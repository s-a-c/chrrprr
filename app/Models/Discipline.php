<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SchemaScopedModel;
use Parental\HasParent;

/**
 * Cross-functional team: Area of Competency.
 * Floating - no hierarchical parent required.
 */
final class Discipline extends Team implements SchemaScopedModel
{
    use HasParent;
}
