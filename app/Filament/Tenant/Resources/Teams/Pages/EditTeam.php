<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Teams\Pages;

use App\Filament\Tenant\Resources\Teams\TeamResource;
use App\Handlers\Commands\Teams\UpdateTeamCommand;
use App\Handlers\Commands\Teams\UpdateTeamHandler;
use App\Models\Team;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Override;
use RuntimeException;

final class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    /**
     * @return (DeleteAction|ForceDeleteAction|RestoreAction)[]
     *
     * @psalm-return list{DeleteAction, ForceDeleteAction, RestoreAction}
     */
    #[Override]
    /**
     * @return (DeleteAction|ForceDeleteAction|RestoreAction)[]
     *
     * @psalm-return list{DeleteAction, ForceDeleteAction, RestoreAction}
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @return (mixed|null)[]
     *
     * @psalm-return array{lock_version: mixed|null,...}
     */
    #[Override]
    /**
     * @return (mixed|null)[]
     *
     * @psalm-return array{lock_version: mixed|null,...}
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure lock_version is included
        if (! isset($data['lock_version'])) {
            $data['lock_version'] = $this->record?->lock_version;
        }

        return $data;
    }

    /**
     * @return Team
     */
    #[Override]
    /**
     * @return Team
     */
    protected function handleRecordUpdate(Model|Team $record, array $data): Model|Team
    {
        assert($record instanceof Team, 'Record must be a Team instance');
        $handler = resolve(UpdateTeamHandler::class);
        $command = new UpdateTeamCommand($record, $data);

        $result = $handler->handle($command)->logInternal();

        /** @var Team */
        return $result->match(
            onSuccess: static function (mixed $team, array $logs): Team {
                assert($team instanceof Team, 'Result must contain a Team instance');
                Notification::make()
                    ->title('Team updated successfully')
                    ->success()
                    ->send();

                return $team;
            },
            onFailure: static function (string $error, array $logs): never {
                Notification::make()
                    ->title('Team update failed')
                    ->body($error)
                    ->danger()
                    ->send();

                throw new RuntimeException($error);
            }
        );
    }
}
