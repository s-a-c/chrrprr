<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Engine for determining if team moves require approval.
 *
 * Uses collection pipeline to evaluate rules.
 */
final readonly class ApprovalDecisionEngine
{
    /**
     * @param  array<ApprovalRuleInterface>  $rules
     */
    public function __construct(
        private array $rules,
    ) {}

    /**
     * Determine if approval is required using collection contains.
     *
     * Replaces foreach loop with collection pipeline.
     */
    public function requiresApproval(Team $team, ?Team $newParent, ?Team $enterprise): bool
    {
        if (! $enterprise instanceof Team) {
            return false;
        }

        // Use 'contains' to stop at first rule that returns true
        return collect($this->rules)
            ->contains(fn (ApprovalRuleInterface $rule) => $rule->requiresApproval(
                $team,
                $newParent,
                $enterprise
            ));
    }
}
