<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\AssetItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    /**
     * 1. GET: Melihat semua daftar aset
     */
    public function index()
    {
        $assets = Asset::with('items')->latest()->get();

        return ApiResponse::success(
            AssetResource::collection($assets),
            'Daftar aset berhasil diambil'
        );
    }

    /**
     * 2. POST: Menambah aset baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:20',
            'category' => 'required|string|max:20',
            'items'    => 'required|array|min:1',
            'items.*.asset_code' => 'required|string|unique:asset_items,asset_code|distinct',
            'items.*.condition'  => 'required|in:good,damaged',
            'items.*.status'     => 'required|in:available,borrowed,unavailable',
            'items.*.description'=> 'required|string',
        ]);

        $asset = DB::transaction(function () use ($validated) {
            $asset = Asset::create([
                'name'           => $validated['name'],
                'category'       => $validated['category'],
                'total_quantity' => count($validated['items']),
            ]);

            $asset->items()->createMany(
                collect($validated['items'])->map(fn ($item) => [
                    'asset_code'       => $item['asset_code'],
                    'condition'        => $item['condition'],
                    'status'           => $item['status'],
                    'description'      => $item['description'],
                    'procurement_date' => now(),
                ])->toArray()
            );

            return $asset->load('items');
        });

        return ApiResponse::success(
            new AssetResource($asset),
            'Aset berhasil ditambahkan',
            201
        );
    }

    /**
     * 3. GET: Detail aset
     */
    public function show($id)
    {
        $asset = Asset::with('items')->find($id);

        if (!$asset) {
            return ApiResponse::error('Aset tidak ditemukan', 404);
        }

        return ApiResponse::success(
            new AssetResource($asset),
            'Detail aset berhasil diambil'
        );
    }

    /**
     * 4. PUT: Update aset 
     */
    public function update(Request $request, $id)
    {
        $asset = Asset::with('items')->find($id);

        if (!$asset) {
            return ApiResponse::error('Aset tidak ditemukan', 404);
        }

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'condition'=> 'sometimes|in:good,damaged',
            'status'   => 'sometimes|in:available,borrowed,unavailable',
        ]);

        DB::transaction(function () use ($asset, $validated) {
            $asset->update([
                'name'     => $validated['name'] ?? $asset->name,
                'category' => $validated['category'] ?? $asset->category,
            ]);

            if (isset($validated['condition']) || isset($validated['status'])) {
                $asset->items()->update(array_filter([
                    'condition' => $validated['condition'] ?? null,
                    'status'    => $validated['status'] ?? null,
                ]));
            }
        });

        return ApiResponse::success(
            new AssetResource($asset->fresh('items')),
            'Aset berhasil diperbarui'
        );
    }

    /**
     * 5. PUT: Update satu item aset 
     */
    public function updateItem(Request $request, $itemId)
    {
        $item = AssetItem::find($itemId);

        if (!$item) {
            return ApiResponse::error('Item aset tidak ditemukan', 404);
        }

        $validated = $request->validate([
            'asset_code' => 'sometimes|string|unique:asset_items,asset_code,' . $itemId,
            'condition'  => 'sometimes|in:good,damaged',
            'status'     => 'sometimes|in:available,borrowed,unavailable',
            'description'=> 'sometimes|string',
        ]);

        $item->update($validated);

        return ApiResponse::success(
            $item->fresh(),
            'Item aset berhasil diperbarui'
        );
    }

    /**
     * 6. DELETE: Hapus aset + item 
     */
    public function destroy($id)
    {
        $asset = Asset::find($id);

        if (!$asset) {
            return ApiResponse::error('Aset tidak ditemukan', 404);
        }

        DB::transaction(function () use ($asset) {
            $asset->items()->delete();
            $asset->delete();
        });

        return response()->noContent(); 
    }

    /**
     * 7. DELETE: Hapus satu item aset
     */
    public function destroyItem($itemId)
    {
        $item = AssetItem::find($itemId);

        if (!$item) {
            return ApiResponse::error('Item aset tidak ditemukan', 404);
        }

        DB::transaction(function () use ($item) {
            $asset = $item->asset;
            $item->delete();

            if ($asset && $asset->total_quantity > 0) {
                $asset->decrement('total_quantity');
            }
        });

        return response()->noContent(); 
    }
}
