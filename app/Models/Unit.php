<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SchemaScopedModel;
use Parental\HasParent;

final class Unit extends Team implements SchemaScopedModel
{
    use HasParent;
}
