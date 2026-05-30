<?php

namespace App\Filament\Resources\Technicians\Schemas;

use App\Models\Technician;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TechnicianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Technician details')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Login user')
                            ->options(fn (?Technician $record): array => User::query()
                                ->where('role', User::ROLE_TECHNICIAN)
                                ->where(function ($query) use ($record): void {
                                    $query
                                        ->whereDoesntHave('technician')
                                        ->when($record?->user_id, fn ($query, int $userId) => $query->orWhere('id', $userId));
                                })
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->helperText('Create a technician user first if this list is empty.'),
                        TextInput::make('name')
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->options([
                                Technician::STATUS_ACTIVE => 'Active',
                                Technician::STATUS_INACTIVE => 'Inactive',
                            ])
                            ->default(Technician::STATUS_ACTIVE)
                            ->required()
                            ->native(false),
                        Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
