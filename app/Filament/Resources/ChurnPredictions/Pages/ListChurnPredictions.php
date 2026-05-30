<?php

namespace App\Filament\Resources\ChurnPredictions\Pages;

use App\Filament\Resources\ChurnPredictions\ChurnPredictionResource;
use App\Services\ChurnAnalysisService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListChurnPredictions extends ListRecords
{
    protected static string $resource = ChurnPredictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runBatchAnalysis')
                ->label('Run batch analysis')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->modalHeading('Run churn batch analysis')
                ->modalDescription('This will send at least 15 customers to the churn API and save the returned predictions.')
                ->successNotificationTitle('Churn predictions saved')
                ->action(function (ChurnAnalysisService $churnAnalysis): void {
                    $churnAnalysis->predictAndSave();
                }),
        ];
    }
}
