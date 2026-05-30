<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCustomerTicketRequest;
use App\Http\Resources\Api\DeviceResource;
use App\Http\Resources\Api\TicketCategoryResource;
use App\Http\Resources\Api\TicketResource;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerTicketController extends Controller
{
    public function categories(): AnonymousResourceCollection
    {
        return TicketCategoryResource::collection(
            TicketCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
        );
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $customer = $this->customerFromRequest($request);

        return TicketResource::collection(
            $customer->tickets()
                ->with(['category', 'technician', 'activeTechnicianJob.photos', 'device'])
                ->orderByDesc('reported_at')
                ->orderByDesc('id')
                ->paginate($this->perPage($request))
        );
    }

    public function store(StoreCustomerTicketRequest $request): JsonResponse
    {
        $customer = $this->customerFromRequest($request);
        $data = $request->validated();

        $ticket = Ticket::query()->create([
            'customer_id' => $customer->getKey(),
            'ticket_category_id' => $data['ticket_category_id'],
            'created_by' => $request->user()->getKey(),
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? Ticket::PRIORITY_MEDIUM,
            'status' => Ticket::STATUS_OPEN,
            'reported_at' => now(),
            'device_id' => $data['device_id'] ?? null,
        ]);

        return (new TicketResource($ticket->load(['category', 'technician', 'activeTechnicianJob.photos', 'device'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Ticket $ticket): TicketResource
    {
        $customer = $this->customerFromRequest($request);

        abort_unless((int) $ticket->customer_id === (int) $customer->getKey(), 403);

        return new TicketResource($ticket->load(['category', 'technician', 'activeTechnicianJob.photos', 'device']));
    }

    public function devices(Request $request): AnonymousResourceCollection
    {
        $customer = $this->customerFromRequest($request);

        return DeviceResource::collection(
            $customer->devices()->orderBy('onu_serial_number')->get()
        );
    }

    protected function customerFromRequest(Request $request): Customer
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->isCustomer(), 403);

        $customer = $user->customer;

        abort_unless($customer instanceof Customer, 403, 'Customer profile not found.');
        abort_unless($customer->status === Customer::STATUS_ACTIVE, 403, 'Customer account is not active.');

        return $customer;
    }

    protected function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 50);
    }
}
