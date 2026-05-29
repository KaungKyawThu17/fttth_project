<?php

namespace App\Filament\Resources\TechnicianJobs\Pages;

use App\Filament\Resources\TechnicianJobs\TechnicianJobResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTechnicianJob extends EditRecord
{
    protected static string $resource = TechnicianJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ...TechnicianJobResource::workflowActions(),
        ];
    }
}
