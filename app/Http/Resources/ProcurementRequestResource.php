<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProcurementRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'assetName' => $this->asset_name,
            'quantity' => $this->quantity,
            'category' => $this->category,
            'reason' => $this->reason,
            'requestStatus' => $this->request_status,
            'rejectionReason' => $this->rejection_reason,
            'createdAt' => optional($this->created_at)->toISOString(),
            'updatedAt' => optional($this->updated_at)->toISOString(),

            // nested user 
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}