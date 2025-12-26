<?php

declare(strict_types=1);

namespace App\States\Team;

use Override;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class TeamState extends State
{
    #[Override]
    final public static function config(): StateConfig
    {
        return parent::config()
            ->default(Active::class)
            ->allowTransition(Active::class, Inactive::class)
            ->allowTransition(Inactive::class, Active::class)
            ->allowTransition(Active::class, Archived::class)
            ->allowTransition(Inactive::class, Archived::class);

        // Archived is a terminal state (allows no outward transitions in this config)
    }
}
