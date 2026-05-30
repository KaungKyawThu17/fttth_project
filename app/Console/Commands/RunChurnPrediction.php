<?php

namespace App\Console\Commands;

use App\Services\ChurnAnalysisService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('churn:predict {--limit=15 : Number of customers to send}')]
#[Description('Send customer churn data to the prediction API and save the results')]
class RunChurnPrediction extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ChurnAnalysisService $churnAnalysis): int
    {
        $predictions = $churnAnalysis->predictAndSave((int) $this->option('limit'));

        $this->info("Saved {$predictions->count()} churn predictions.");

        return self::SUCCESS;
    }
}
