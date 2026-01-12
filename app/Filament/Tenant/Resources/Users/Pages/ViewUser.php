<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Users\Pages;

use App\Filament\Tenant\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Override;

final class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @return EditAction[]
     *
     * @psalm-return list{EditAction}
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
