<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            'ticket_no' => $this->ticket_no,
            'subject' => $this->subject,
            'description' => $this->description,
            'priority' => $this->priority,
            'priority_label' => $this->priorityLabel(),
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'reported_at' => $this->reported_at?->toIso8601String(),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'completed_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'completion_note' => $this->resolution_note,
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'technician' => $this->whenLoaded('technician', fn (): ?array => $this->technician ? [
                'id' => $this->technician->id,
                'name' => $this->technician->name,
                'phone' => $this->technician->phone,
            ] : null),
            'active_job' => new TechnicianJobResource($this->whenLoaded('activeTechnicianJob')),
        ];
    }
}
