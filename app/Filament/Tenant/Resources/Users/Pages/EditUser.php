<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Users\Pages;

use App\Actions\Users\UpdateUserProfile;
use App\Filament\Tenant\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model|User $record, array $data): Model|User
    {
        // Use the UpdateUserProfile action instead of default model update
        return app(UpdateUserProfile::class)->handle($record, $data);
    }
}
