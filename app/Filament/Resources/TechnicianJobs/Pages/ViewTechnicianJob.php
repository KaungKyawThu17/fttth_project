<?php

namespace App\Filament\Resources\TechnicianJobs\Pages;

use App\Filament\Resources\TechnicianJobs\TechnicianJobResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTechnicianJob extends ViewRecord
{
    protected static string $resource = TechnicianJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => TechnicianJobResource::canEdit($this->getRecord())),
            ...TechnicianJobResource::workflowActions(),
        ];
    }
}
