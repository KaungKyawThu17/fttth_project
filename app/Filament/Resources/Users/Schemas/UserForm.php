<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->required(),
                        Select::make('role')
                            ->options(User::roleOptions())
                            ->default(User::ROLE_SUPPORT)
                            ->required()
                            ->native(false),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email verified at')
                            ->seconds(false),
                    ]),
                Section::make('Password')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->afterStateHydrated(fn (TextInput $component): TextInput => $component->state(null))
                            ->rule(Password::min(8))
                            ->nullable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->saved(fn (?string $state): bool => filled($state))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
