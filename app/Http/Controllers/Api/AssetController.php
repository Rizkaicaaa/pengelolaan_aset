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
     * 1. Semua user (Lab & Dosen) melihat daftar aset
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
     * 2. Admin Jurusan menambah aset baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Validasi Parent
            'name' => 'required|string|max:20',
            'category' => 'required|string|max:20',
            
            // Validasi Array Items (Minimal 1 item)
            'items' => 'required|array|min:1',
            
            // Validasi Detail Tiap Item di dalam Array
            'items.*.asset_code' => 'required|string|unique:asset_items,asset_code|distinct', 
            'items.*.condition' => 'required|in:good,damaged',
            'items.*.status' => 'required|in:available,borrowed,unavailable',
            'items.*.description' => 'required|string',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $quantity = count($validated['items']);
                $asset = Asset::create([
                    'name' => $validated['name'],
                    'category' => $validated['category'],
                    'total_quantity' => $quantity,
                ]);
                $itemsData = [];
                foreach ($validated['items'] as $item) {
                    $itemsData[] = [
                        'asset_code' => $item['asset_code'],
                        'condition' => $item['condition'],
                        'status' => $item['status'],
                        'description' => $item['description'],
                        'procurement_date' => now(), // Tanggal otomatis
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $asset->items()->createMany($itemsData);
                $asset->load('items');

                return ApiResponse::success(
                    new AssetResource($asset),
                    'Aset berhasil ditambahkan',
                    201
                );
            });
        } catch (\Exception $e) {
            return ApiResponse::error('Gagal menyimpan aset: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 3. Semua user melihat detail aset
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
     * 4. Admin Jurusan update aset (name, category, condition/status semua item)
     */
    public function update(Request $request, $id)
    {
        $asset = Asset::find($id);
        if (!$asset) {
            return ApiResponse::error('Aset tidak ditemukan', 404);
        }
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'condition' => 'sometimes|in:good,damaged',
            'status' => 'sometimes|in:available,borrowed,unavailable',
        ]);

        try {
            DB::transaction(function () use ($asset, $validated) {
                $asset->update([
                    'name' => $validated['name'] ?? $asset->name,
                    'category' => $validated['category'] ?? $asset->category,
                ]);
                if (isset($validated['condition']) || isset($validated['status'])) {
                    
                    $updateData = [];
                    if (isset($validated['condition'])) {
                        $updateData['condition'] = $validated['condition'];
                    }
                    if (isset($validated['status'])) {
                        $updateData['status'] = $validated['status'];
                    }
                    if ($asset->items()->exists()) {
                        $asset->items()->update($updateData);
                    }
                }
            });

            $asset->load('items');
            return ApiResponse::success(
                new AssetResource($asset),
                'Aset berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Gagal memperbarui aset: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 5. PUT: UPDATE SATU ITEM SPESIFIK 
     * URL: /api/asset-items/{id}  <-- ID milik Item
     */
    public function updateItem(Request $request, $itemId)
    {
        $item = AssetItem::find($itemId);
        if (!$item) return ApiResponse::error('Item aset tidak ditemukan', 404);
        $validated = $request->validate([
            'asset_code' => 'sometimes|string|unique:asset_items,asset_code,' . $itemId,
            'condition' => 'sometimes|in:good,damaged',
            'status' => 'sometimes|in:available,borrowed,unavailable',
            'description' => 'sometimes|string',
        ]);
        $item->update([
            'asset_code' => $validated['asset_code'] ?? $item->asset_code,
            'condition' => $validated['condition'] ?? $item->condition,
            'status' => $validated['status'] ?? $item->status,
            'description' => $validated['description'] ?? $item->description,
        ]);

        return ApiResponse::success($item, 'Item berhasil diperbarui');
    }
    
    /**
     * 6. Admin Jurusan hapus aset + semua AssetItem terkait
     */
    public function destroy($id)
    {
        $asset = Asset::find($id);
        if (!$asset) {
            return ApiResponse::error('Aset tidak ditemukan', 404);
        }
        try {
            DB::transaction(function () use ($asset) {
                $asset->items()->delete();
                $asset->delete();
            });
            return ApiResponse::success(null, 'Aset dan seluruh itemnya berhasil dihapus');
        } catch (\Exception $e) {
            return ApiResponse::error('Gagal menghapus aset: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 7. DELETE SPESIFIK ITEM (SATU PER SATU)
     */
    public function destroyItem($itemId)
    {
        $item = AssetItem::find($itemId);
        if (!$item) {
            return ApiResponse::error('Item aset tidak ditemukan', 404);
        }
        $parentAsset = $item->asset;
        try {
            DB::transaction(function () use ($item, $parentAsset) {
                $item->delete();
                if ($parentAsset && $parentAsset->total_quantity > 0) {
                    $parentAsset->decrement('total_quantity');
                }
            });
            return ApiResponse::success(null, 'Satu item berhasil dihapus dan stok dikurangi');
        } catch (\Exception $e) {
            return ApiResponse::error('Gagal menghapus item: ' . $e->getMessage(), 500);
        }
    }
}
