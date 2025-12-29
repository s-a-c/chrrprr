<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HasUlidTestModel extends Model
{
    use HasFactory;
    use HasUlid;

    public $timestamps = false;

    protected $table = 'has_ulid_test_models';

    protected $guarded = [];
}
