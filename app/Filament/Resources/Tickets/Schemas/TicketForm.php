<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Models\Customer;
use App\Models\Technician;
use App\Models\Ticket;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ticket_no')
                            ->label('Ticket no.')
                            ->disabled()
                            ->saved(false)
                            ->visibleOn('edit'),
                        DateTimePicker::make('reported_at')
                            ->label('Reported at')
                            ->default(now())
                            ->seconds(false)
                            ->required(),
                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship(
                                name: 'customer',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('status', Customer::STATUS_ACTIVE)
                                    ->orderBy('name'),
                            )
                            ->searchable(['name', 'customer_code', 'phone'])
                            ->preload()
                            ->required(),
                        Select::make('ticket_category_id')
                            ->label('Category')
                            ->relationship(
                                name: 'category',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('is_active', true)
                                    ->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('subject')
                            ->maxLength(150)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->rows(5)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Workflow')
                    ->columns(2)
                    ->schema([
                        Select::make('priority')
                            ->options(Ticket::priorityOptions())
                            ->default(Ticket::PRIORITY_MEDIUM)
                            ->required()
                            ->native(false),
                        Select::make('technician_id')
                            ->label('Technician')
                            ->relationship(
                                name: 'technician',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('status', Technician::STATUS_ACTIVE)
                                    ->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit')
                            ->saved(fn (string $operation): bool => $operation === 'create'),
                        Select::make('status')
                            ->options(Ticket::statusOptions())
                            ->default(Ticket::STATUS_OPEN)
                            ->disabled()
                            ->saved(false)
                            ->visibleOn('edit')
                            ->native(false),
                        Textarea::make('resolution_note')
                            ->label('Completion note')
                            ->rows(4)
                            ->maxLength(2000)
                            ->disabledOn('edit')
                            ->saved(false)
                            ->visibleOn('edit')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
