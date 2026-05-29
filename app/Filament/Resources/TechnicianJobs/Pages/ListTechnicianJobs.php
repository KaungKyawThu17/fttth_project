<?php

namespace App\Filament\Resources\TechnicianJobs\Pages;

use App\Filament\Resources\TechnicianJobs\TechnicianJobResource;
use Filament\Resources\Pages\ListRecords;

class ListTechnicianJobs extends ListRecords
{
    protected static string $resource = TechnicianJobResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
