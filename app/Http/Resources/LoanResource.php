<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
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
            'userId' => $this->user_id,
            'assetItemId' => $this->asset_item_id,
            'loanPurpose' => $this->loan_purpose,
            'loanDate' => $this->loan_date?->format('Y-m-d'),
            'returnDate' => $this->return_date?->format('Y-m-d'),
            'loanStatus' => $this->loan_status,
            'rejectionReason' => $this->rejection_reason,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),

            // Relasi
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'role' => $this->user->role,
                    'phoneNumber' => $this->user->phone_number,
                ];
            }),

            'assetItem' => $this->whenLoaded('assetItem', function() {
                return [
                    'id' => $this->assetItem->id,
                    'assetId' => $this->assetItem->asset_id,
                    'assetCode' => $this->assetItem->asset_code,
                    'condition' => $this->assetItem->condition,
                    'status' => $this->assetItem->status,
                    'procurementDate' => $this->assetItem->procurement_date?->format('Y-m-d'),
                    'description' => $this->assetItem->description,
                    'createdAt' => $this->assetItem->created_at?->toISOString(),
                    'updatedAt' => $this->assetItem->updated_at?->toISOString(),

                    'asset' => $this->whenLoaded('assetItem.asset', function() {
                        return [
                            'id' => $this->assetItem->asset->id,
                            'name' => $this->assetItem->asset->name,
                            'category' => $this->assetItem->asset->category,
                            'description' => $this->assetItem->asset->description ?? null,
                        ];
                    }),
                ];
            }),
        ];
    }
}
