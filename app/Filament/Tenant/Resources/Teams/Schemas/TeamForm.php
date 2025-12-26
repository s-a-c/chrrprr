<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Teams\Schemas;

use App\Enums\TeamState;
use App\Enums\TeamStatus;
use App\Enums\TeamType;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Team Name')
                    ->required()
                    ->maxLength(255),
                // Slug is auto-generated from name (translatable)
                Select::make('type')
                    ->label('Team Type')
                    ->options(TeamType::class)
                    ->required()
                    ->native(false),
                Select::make('parent_id')
                    ->label('Parent Team')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('state')
                    ->label('State')
                    ->options(TeamState::class)
                    ->default(TeamState::ACTIVE)
                    ->native(false),
                Select::make('status')
                    ->label('Status')
                    ->options(TeamStatus::class)
                    ->default(TeamStatus::OFFLINE)
                    ->native(false),
                MarkdownEditor::make('bio')
                    ->label('Team Biography')
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
                    ->helperText('Maximum 50,000 characters. Markdown supported.'),
            ]);
    }
}
