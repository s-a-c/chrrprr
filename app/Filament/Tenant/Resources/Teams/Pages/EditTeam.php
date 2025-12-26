<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Teams\Pages;

use App\Actions\Teams\UpdateTeam;
use App\Filament\Tenant\Resources\Teams\TeamResource;
use App\Models\Team;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure lock_version is included
        if (! isset($data['lock_version'])) {
            $data['lock_version'] = $this->record->lock_version;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model|Team $record, array $data): Model|Team
    {
        // Use the UpdateTeam action instead of default model update
        return app(UpdateTeam::class)->handle($record, $data);
    }
}
