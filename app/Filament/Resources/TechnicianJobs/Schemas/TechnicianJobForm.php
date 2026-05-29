<?php

namespace App\Filament\Resources\TechnicianJobs\Schemas;

use App\Models\TechnicianJob;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TechnicianJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Job details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('job_no')
                            ->label('Job no.')
                            ->disabled()
                            ->saved(false),
                        Select::make('status')
                            ->options(TechnicianJob::statusOptions())
                            ->disabled()
                            ->saved(false)
                            ->native(false),
                        Select::make('ticket_id')
                            ->label('Ticket')
                            ->relationship('ticket', 'ticket_no')
                            ->searchable(['ticket_no', 'subject'])
                            ->preload()
                            ->disabled()
                            ->saved(false),
                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable(['name', 'customer_code', 'phone'])
                            ->preload()
                            ->disabled()
                            ->saved(false),
                        Select::make('technician_id')
                            ->label('Technician')
                            ->relationship('technician', 'name')
                            ->searchable(['name', 'phone', 'email'])
                            ->preload()
                            ->disabled()
                            ->saved(false),
                        Select::make('job_type')
                            ->label('Job type')
                            ->options(TechnicianJob::jobTypeOptions())
                            ->required()
                            ->native(false),
                        DatePicker::make('scheduled_date')
                            ->label('Scheduled date')
                            ->native(false),
                    ]),
                Section::make('Timeline')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('started_at')
                            ->label('Started at')
                            ->seconds(false)
                            ->disabled()
                            ->saved(false),
                        DateTimePicker::make('completed_at')
                            ->label('Completed at')
                            ->seconds(false)
                            ->disabled()
                            ->saved(false),
                    ]),
            ]);
    }
}
