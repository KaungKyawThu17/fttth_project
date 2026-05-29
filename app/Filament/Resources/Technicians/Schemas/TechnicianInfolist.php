<?php

namespace App\Filament\Resources\Technicians\Schemas;

use App\Models\Technician;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TechnicianInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Technician::STATUS_ACTIVE => 'Active',
                        Technician::STATUS_INACTIVE => 'Inactive',
                        default => str($state)->headline()->toString(),
                    })
                    ->color(fn (string $state): string => $state === Technician::STATUS_ACTIVE ? 'success' : 'gray'),
                TextEntry::make('tickets_count')
                    ->label('Tickets')
                    ->numeric(),
                TextEntry::make('technician_jobs_count')
                    ->label('Jobs')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
