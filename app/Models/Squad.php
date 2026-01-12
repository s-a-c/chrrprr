<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SchemaScopedModel;
use Parental\HasParent;

/**
 * Cross-functional team: Cross-functional Execution Team.
 * Floating - no hierarchical parent required.
 */
final class Squad extends Team implements SchemaScopedModel
{
    use HasParent;
}
