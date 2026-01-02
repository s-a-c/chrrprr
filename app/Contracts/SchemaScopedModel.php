<?php

declare(strict_types=1);

namespace App\Contracts;

interface SchemaScopedModel
{
    /**
     * Get the table associated with the model, scoped to the app schema.
     */
    public function getTable();
}
