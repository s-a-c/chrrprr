<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Teams\Pages;

use App\Filament\Tenant\Resources\Teams\TeamResource;
use App\Handlers\Commands\Teams\CreateTeamCommand;
use App\Handlers\Commands\Teams\CreateTeamHandler;
use App\Models\Team;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Override;
use RuntimeException;

final class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    /**
     * @return Team
     */
    #[Override]
    /**
     * @return Team
     */
    protected function handleRecordCreation(array $data): Model
    {
        $handler = resolve(CreateTeamHandler::class);
        $command = new CreateTeamCommand($data);

        $result = $handler->handle($command)->logInternal();

        /** @var Team */
        return $result->match(
            onSuccess: static function (Team $team, array $logs): Team {
                Notification::make()
                    ->title('Team created successfully')
                    ->success()
                    ->send();

                return $team;
            },
            onFailure: static function (string $error, array $logs): never {
                Notification::make()
                    ->title('Team creation failed')
                    ->body($error)
                    ->danger()
                    ->send();

                throw new RuntimeException($error);
            }
        );
    }
}
