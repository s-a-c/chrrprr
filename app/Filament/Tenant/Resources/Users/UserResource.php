<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Users;

use App\Filament\Tenant\Resources\Users\Pages\EditUser;
use App\Filament\Tenant\Resources\Users\Pages\ListUsers;
use App\Filament\Tenant\Resources\Users\Schemas\UserForm;
use App\Filament\Tenant\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /**
     * @psalm-return array<never, never>
     */
    #[Override]
    /**
     * @psalm-return array<never, never>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return PageRegistration[]
     *
     * @psalm-return array{index: PageRegistration, edit: PageRegistration}
     */
    #[Override]
    /**
     * @return PageRegistration[]
     *
     * @psalm-return array{index: PageRegistration, edit: PageRegistration}
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
