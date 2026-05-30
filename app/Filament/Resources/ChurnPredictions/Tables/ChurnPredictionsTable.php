<?php

namespace App\Filament\Resources\ChurnPredictions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChurnPredictionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('predicted_at', 'desc')
            ->columns([
                TextColumn::make('customer_code')
                    ->label('Customer ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('churn_prediction')
                    ->label('Prediction')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Churn' : 'Keep')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('churn_probability')
                    ->label('Probability')
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state * 100, 1).'%')
                    ->sortable(),
                TextColumn::make('complaints')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('downtime_hours')
                    ->label('Downtime hours')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('resolution_time')
                    ->label('Resolution hours')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('duration_time')
                    ->label('Device months')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sentiment_label')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString())
                    ->color(fn (string $state): string => match ($state) {
                        'negative' => 'danger',
                        'positive' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('sentiment_score')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                TextColumn::make('predicted_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('churn_prediction')
                    ->label('Prediction')
                    ->options([
                        1 => 'Churn',
                        0 => 'Keep',
                    ])
                    ->native(false),
                SelectFilter::make('sentiment_label')
                    ->options([
                        'positive' => 'Positive',
                        'neutral' => 'Neutral',
                        'negative' => 'Negative',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
