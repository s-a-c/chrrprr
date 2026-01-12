<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Teams\Pages;

use App\Filament\Tenant\Resources\Teams\TeamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    /**
     * @return CreateAction[]
     *
     * @psalm-return list{CreateAction}
     */
    #[Override]
    /**
     * @return CreateAction[]
     *
     * @psalm-return list{CreateAction}
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
