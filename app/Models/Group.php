<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SchemaScopedModel;
use Parental\HasParent;

/**
 * Cross-functional team: Feature/Topic Cluster.
 * Floating - no hierarchical parent required.
 */
final class Group extends Team implements SchemaScopedModel
{
    use HasParent;
}
