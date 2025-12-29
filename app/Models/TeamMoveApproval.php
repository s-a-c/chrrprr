<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $from_parent_id
 * @property int|null $to_parent_id
 * @property int $requested_by_id
 * @property string $status
 * @property string|null $reason
 * @property array|null $required_approvers
 * @property array|null $approvals
 * @property Carbon|null $approved_at
 * @property Carbon|null $rejected_at
 * @property int|null $rejected_by_id
 * @property string|null $rejection_reason
 * @property Team $team
 * @property Team|null $fromParent
 * @property Team|null $toParent
 * @property User $requestedBy
 * @property User|null $rejectedBy
 *
 * @use HasFactory<Factory>
 */
class TeamMoveApproval extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'from_parent_id',
        'to_parent_id',
        'requested_by_id',
        'status',
        'reason',
        'required_approvers',
        'approvals',
        'approved_at',
        'rejected_at',
        'rejected_by_id',
        'rejection_reason',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * @return array<string, string>
     *
     * @psalm-return array{required_approvers: 'array', approvals: 'array', approved_at: 'datetime', rejected_at: 'datetime'}
     */
    protected function casts(): array
    {
        return [
            'required_approvers' => 'array',
            'approvals' => 'array',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
