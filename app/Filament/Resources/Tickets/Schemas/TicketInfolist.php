<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Models\Ticket;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('ticket_no')
                            ->label('Ticket no.'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state, Ticket $record): string => $record->statusLabel())
                            ->color(fn (Ticket $record): string => $record->statusColor()),
                        TextEntry::make('priority')
                            ->badge()
                            ->formatStateUsing(fn (string $state, Ticket $record): string => $record->priorityLabel())
                            ->color(fn (Ticket $record): string => $record->priorityColor()),
                        TextEntry::make('customer.name')
                            ->label('Customer'),
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('technician.name')
                            ->label('Technician')
                            ->placeholder('Unassigned'),
                        TextEntry::make('subject')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                    ]),
                Section::make('Timeline')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('reported_at')
                            ->dateTime(),
                        TextEntry::make('assigned_at')
                            ->dateTime()
                            ->placeholder('Not assigned'),
                        TextEntry::make('resolved_at')
                            ->label('Completed at')
                            ->dateTime()
                            ->placeholder('Not completed'),
                        TextEntry::make('closed_at')
                            ->dateTime()
                            ->placeholder('Not closed'),
                        TextEntry::make('creator.name')
                            ->label('Created by')
                            ->placeholder('System')
                            ->columnSpan(2),
                        TextEntry::make('resolution_note')
                            ->label('Completion note')
                            ->placeholder('No completion note')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
