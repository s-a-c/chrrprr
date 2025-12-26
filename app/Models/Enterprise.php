<?php

declare(strict_types=1);

namespace App\Models;

use Parental\HasParent;

final class Enterprise extends Team
{
    use HasParent;
}
