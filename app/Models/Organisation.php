<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SchemaScopedModel;
use Parental\HasParent;

final class Organisation extends Team implements SchemaScopedModel
{
    use HasParent;
}
