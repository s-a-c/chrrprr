<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Users\Pages;

use App\Filament\Tenant\Resources\Users\UserResource;
use App\Handlers\Commands\Users\UpdateUserProfileCommand;
use App\Handlers\Commands\Users\UpdateUserProfileHandler;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Override;
use RuntimeException;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @return DeleteAction[]
     *
     * @psalm-return list{DeleteAction}
     */
    #[Override]
    /**
     * @return DeleteAction[]
     *
     * @psalm-return list{DeleteAction}
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @return User
     */
    #[Override]
    /**
     * @return User
     */
    protected function handleRecordUpdate(Model|User $record, array $data): Model|User
    {
        assert($record instanceof User, 'Record must be a User instance');
        $handler = resolve(UpdateUserProfileHandler::class);
        $command = new UpdateUserProfileCommand($record, $data);

        $result = $handler->handle($command)->logInternal();

        /** @var User */
        return $result->match(
            onSuccess: static function (mixed $user, array $logs): User {
                assert($user instanceof User, 'Result must contain a User instance');
                Notification::make()
                    ->title('Profile updated successfully')
                    ->success()
                    ->send();

                return $user;
            },
            onFailure: static function (string $error, array $logs): never {
                Notification::make()
                    ->title('Profile update failed')
                    ->body($error)
                    ->danger()
                    ->send();

                throw new RuntimeException($error);
            }
        );
    }
}
