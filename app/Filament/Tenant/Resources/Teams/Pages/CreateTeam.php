<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Teams\Pages;

use App\Actions\Teams\CreateTeam as CreateTeamAction;
use App\Filament\Tenant\Resources\Teams\TeamResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Inject and use the Action
        return app(CreateTeamAction::class)->handle($data);
    }
}
