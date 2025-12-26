<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Users\Schemas;

use App\Enums\UserState;
use App\Enums\UserStatus;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('state')
                    ->label('State')
                    ->options(UserState::class)
                    ->default(UserState::ACTIVE)
                    ->native(false),
                Select::make('status')
                    ->label('Status')
                    ->options(UserStatus::class)
                    ->default(UserStatus::OFFLINE)
                    ->native(false),
                MarkdownEditor::make('bio')
                    ->label('Biography')
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'link',
                        'bulletList',
                        'orderedList',
                        'codeBlock',
                        'undo',
                        'redo',
                    ])
                    ->maxLength(50000) // Hard limit: 50,000 characters
                    ->helperText('Maximum 50,000 characters. Recommended: 10,000 characters. Markdown supported.'),
            ]);
    }
}
