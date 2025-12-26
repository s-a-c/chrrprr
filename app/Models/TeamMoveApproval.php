<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property int|null $rejected_by_id
 * @property string|null $rejection_reason
 * @property Team $team
 * @property Team|null $fromParent
 * @property Team|null $toParent
 * @property User $requestedBy
 * @property User|null $rejectedBy
 */
class TeamMoveApproval extends Model
{
    use HasFactory;

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

    protected $casts = [
        'required_approvers' => 'array',
        'approvals' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function fromParent(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'from_parent_id');
    }

    public function toParent(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'to_parent_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
