<?php

declare(strict_types=1);

return [
    'hierarchy' => [
        'hard_depth_limit' => (int) env('TEAM_HIERARCHY_HARD_LIMIT', 10),
        'default_soft_depth_limit' => (int) env('TEAM_HIERARCHY_SOFT_LIMIT', 5),
    ],
];
