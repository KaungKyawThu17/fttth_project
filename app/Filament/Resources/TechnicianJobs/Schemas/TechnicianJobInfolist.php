<?php

namespace App\Filament\Resources\TechnicianJobs\Schemas;

use App\Models\TechnicianJob;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TechnicianJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Job details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('job_no')
                            ->label('Job no.'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state, TechnicianJob $record): string => $record->statusLabel())
                            ->color(fn (TechnicianJob $record): string => $record->statusColor()),
                        TextEntry::make('job_type')
                            ->label('Job type')
                            ->formatStateUsing(fn (string $state, TechnicianJob $record): string => $record->jobTypeLabel()),
                        TextEntry::make('ticket.ticket_no')
                            ->label('Ticket no.'),
                        TextEntry::make('customer.name')
                            ->label('Customer'),
                        TextEntry::make('technician.name')
                            ->label('Technician'),
                        TextEntry::make('ticket.subject')
                            ->label('Ticket subject')
                            ->columnSpanFull(),
                    ]),
                Section::make('Timeline')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('scheduled_date')
                            ->date()
                            ->placeholder('Not scheduled'),
                        TextEntry::make('started_at')
                            ->dateTime()
                            ->placeholder('Not started'),
                        TextEntry::make('completed_at')
                            ->dateTime()
                            ->placeholder('Not completed'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
