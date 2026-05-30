<?php

namespace App\Filament\Resources\ChurnPredictions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ChurnPredictionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer_code')
                    ->label('Customer ID'),
                TextEntry::make('customer.name')
                    ->label('Customer'),
                TextEntry::make('churn_prediction')
                    ->label('Prediction')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Churn' : 'Keep')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success'),
                TextEntry::make('churn_probability')
                    ->label('Probability')
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state * 100, 1).'%'),
                TextEntry::make('complaints')
                    ->numeric(),
                TextEntry::make('downtime_hours')
                    ->label('Downtime hours')
                    ->numeric(decimalPlaces: 2),
                TextEntry::make('resolution_time')
                    ->label('Resolution hours')
                    ->numeric(decimalPlaces: 2),
                TextEntry::make('duration_time')
                    ->label('Device months')
                    ->numeric(),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('sentiment_label')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString())
                    ->color(fn (string $state): string => match ($state) {
                        'negative' => 'danger',
                        'positive' => 'success',
                        default => 'gray',
                    }),
                TextEntry::make('sentiment_score')
                    ->numeric(decimalPlaces: 3),
                TextEntry::make('predicted_at')
                    ->dateTime(),
            ]);
    }
}
