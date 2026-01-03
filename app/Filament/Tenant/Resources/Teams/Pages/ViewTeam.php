<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Teams\Pages;

use App\Filament\Tenant\Resources\Teams\TeamResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Override;

final class ViewTeam extends ViewRecord
{
    protected static string $resource = TeamResource::class;

    /**
     * @return (DeleteAction|EditAction|ForceDeleteAction|RestoreAction)[]
     *
     * @psalm-return list{EditAction, DeleteAction, ForceDeleteAction, RestoreAction}
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
