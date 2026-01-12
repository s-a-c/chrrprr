<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Users\Pages;

use App\Filament\Tenant\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /**
     * @psalm-return array<never, never>
     */
    #[Override]
    /**
     * @psalm-return array<never, never>
     */
    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(), // Uncomment if user creation is needed
        ];
    }
}
