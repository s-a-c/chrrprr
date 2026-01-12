<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('ulid')->searchable(),
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('email')->searchable()->sortable(),
            TextColumn::make('state')->badge()->searchable(),
            TextColumn::make('status')->badge()->searchable(),
            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])->recordActions([
            ViewAction::make()
                ->url(static fn (User $record): string => route('users.show', ['user' => $record->getRouteKey()])),
            EditAction::make(),
        ])->toolbarActions([
            // Add create action if needed
        ]);
    }
}
