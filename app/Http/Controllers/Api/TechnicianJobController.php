<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CompleteTechnicianJobRequest;
use App\Http\Requests\Api\StartTechnicianJobRequest;
use App\Http\Resources\Api\TechnicianJobResource;
use App\Models\Technician;
use App\Models\TechnicianJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TechnicianJobController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $technician = $this->technicianFromRequest($request);

        return TechnicianJobResource::collection(
            TechnicianJob::query()
                ->forTechnician((int) $technician->getKey())
                ->with(['ticket.category', 'customer', 'photos'])
                ->orderByDesc('scheduled_date')
                ->orderByDesc('id')
                ->paginate($this->perPage($request))
        );
    }

    public function show(TechnicianJob $technicianJob): TechnicianJobResource
    {
        Gate::authorize('view', $technicianJob);

        return new TechnicianJobResource($technicianJob->load(['ticket.category', 'customer', 'photos']));
    }

    public function start(StartTechnicianJobRequest $request, TechnicianJob $technicianJob): TechnicianJobResource
    {
        $data = $request->validated();

        $job = $technicianJob->startJobWithEstimatedArrival($data['estimated_arrival_at']);

        return new TechnicianJobResource($job->load(['ticket.category', 'customer', 'photos']));
    }

    public function complete(CompleteTechnicianJobRequest $request, TechnicianJob $technicianJob): JsonResponse
    {
        $data = $request->validated();

        $job = $technicianJob->completeJob($data['comment'] ?? null);

        return (new TechnicianJobResource($job->load(['ticket.category', 'customer', 'photos'])))
            ->response();
    }

    protected function technicianFromRequest(Request $request): Technician
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->isTechnician(), 403);

        $technician = $user->technician;

        abort_unless($technician instanceof Technician, 403, 'Technician profile not found.');
        abort_unless($technician->status === Technician::STATUS_ACTIVE, 403, 'Technician account is not active.');

        return $technician;
    }

    protected function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 50);
    }
}
