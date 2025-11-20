<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'totalQuantity' => $this->total_quantity,
            'createdAt' => optional($this->created_at)->toISOString(),
            'updatedAt' => optional($this->updated_at)->toISOString(),

            // nested items
            'items' => $this->whenLoaded('items', function() {
                return $this->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'assetCode' => $item->asset_code,
                        'condition' => $item->condition,
                        'status' => $item->status,
                        'procurementDate' => optional($item->procurement_date)->toISOString(),
                        'description' => $item->description,
                    ];
                });
            }),
        ];
    }
}

