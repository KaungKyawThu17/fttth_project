<?php

namespace App\Filament\Resources\Technicians\Tables;

use App\Models\Technician;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TechniciansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Login user')
                    ->placeholder('None')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Technician::STATUS_ACTIVE => 'Active',
                        Technician::STATUS_INACTIVE => 'Inactive',
                        default => str($state)->headline()->toString(),
                    })
                    ->color(fn (string $state): string => $state === Technician::STATUS_ACTIVE ? 'success' : 'gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tickets_count')
                    ->label('Tickets')
                    ->sortable(),
                TextColumn::make('technician_jobs_count')
                    ->label('Jobs')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Technician::STATUS_ACTIVE => 'Active',
                        Technician::STATUS_INACTIVE => 'Inactive',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
