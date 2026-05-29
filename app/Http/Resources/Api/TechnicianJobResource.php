<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_no' => $this->job_no,
            'job_type' => $this->job_type,
            'job_type_label' => $this->jobTypeLabel(),
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'estimated_arrival_at' => $this->estimated_arrival_at?->toDateString(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'ticket' => new TicketResource($this->whenLoaded('ticket')),
            'customer' => $this->whenLoaded('customer', fn (): array => [
                'id' => $this->customer->id,
                'customer_code' => $this->customer->customer_code,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'address' => $this->customer->address,
            ]),
            'photos' => JobPhotoResource::collection($this->whenLoaded('photos')),
        ];
    }
}
