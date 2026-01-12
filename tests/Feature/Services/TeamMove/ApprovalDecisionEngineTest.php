<?php

declare(strict_types=1);

namespace Tests\Feature\Services\TeamMove;

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Services\TeamMove\ApprovalDecisionEngine;
use App\Services\TeamMove\ApprovalRuleInterface;
use Override;
use RuntimeException;

it('returns true when any rule requires approval', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $rule = new class implements ApprovalRuleInterface
    {
        /**
         * @return true
         */
        #[Override]
        /**
         * @return true
         */
        public function requiresApproval($team, $newParent, $enterprise): bool
        {
            return true;
        }
    };

    $engine = new ApprovalDecisionEngine([$rule]);

    expect($engine->requiresApproval($org, null, $enterprise))->toBeTrue();
});

it('returns false when no rules require approval', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $rule = new class implements ApprovalRuleInterface
    {
        /**
         * @return false
         */
        #[Override]
        /**
         * @return false
         */
        public function requiresApproval($team, $newParent, $enterprise): bool
        {
            return false;
        }
    };

    $engine = new ApprovalDecisionEngine([$rule]);

    expect($engine->requiresApproval($org, null, $enterprise))->toBeFalse();
});

it('stops at first rule that requires approval', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $rule1 = new class implements ApprovalRuleInterface
    {
        /**
         * @return true
         */
        #[Override]
        /**
         * @return true
         */
        public function requiresApproval($team, $newParent, $enterprise): bool
        {
            return true;
        }
    };

    $rule2 = new class implements ApprovalRuleInterface
    {
        /**
         * @return never
         */
        #[Override]
        /**
         * @return never
         */
        public function requiresApproval($team, $newParent, $enterprise): bool
        {
            // This should never be called if contains() short-circuits
            throw new RuntimeException('Should not be called');
        }
    };

    $engine = new ApprovalDecisionEngine([$rule1, $rule2]);

    expect($engine->requiresApproval($org, null, $enterprise))->toBeTrue();
});

it('returns false when enterprise is not provided', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $rule = new class implements ApprovalRuleInterface
    {
        /**
         * @return true
         */
        #[Override]
        /**
         * @return true
         */
        public function requiresApproval($team, $newParent, $enterprise): bool
        {
            return true;
        }
    };

    $engine = new ApprovalDecisionEngine([$rule]);

    expect($engine->requiresApproval($org, null, null))->toBeFalse();
});
