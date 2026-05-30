<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
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
            'onu_serial_number' => $this->onu_serial_number,
            'onu_model' => $this->onu_model,
            'mac_address' => $this->mac_address,
            'router_model' => $this->router_model,
            'installation_date' => $this->installation_date?->toDateString(),
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
        ];
    }
}
