<?php

namespace App\Http\Controllers\Api;

use App\Models\ProcurementRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProcurementRequestResource;
use App\Http\Helpers\ApiResponse;
use Illuminate\Http\Request;

class ProcurementRequestController extends Controller
{
    // Dosen + Admin Lab membuat request
    public function store(Request $request)
    {
        $validated = $request->validate([
            'assetName' => 'required|string',
            'quantity'  => 'required|integer|min:1',
            'category'  => 'required|in:electronics,furniture,stationary',
            'reason'    => 'required|string',
            'image_reference' => 'nullable|string',
        ]);

        $requestData = [
            'user_id'    => $request->user()->id,
            'asset_name' => $validated['assetName'],
            'quantity'   => $validated['quantity'],
            'category'   => $validated['category'],
            'reason'     => $validated['reason'],
            'image_reference' => $validated['image_reference'] ?? null,
        ];

        $procurement = ProcurementRequest::create($requestData);

        return ApiResponse::success(
            new ProcurementRequestResource($procurement),
            'Pengajuan pengadaan berhasil dibuat',
            code: 201
        );
    }

    // Admin jurusan melihat semua request, Dosen + Admin Lab melihat request miliknya
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ProcurementRequest::with('user');

        // Role: non admin_jurusan hanya melihat data miliknya
        if ($user->role !== 'admin_jurusan') {
            $query->where('user_id', $user->id);
        }

        // Fitur pencarian
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('asset_name', 'LIKE', "%{$search}%")
                    ->orWhere('category', 'LIKE', "%{$search}%")
                    ->orWhere('reason', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $data = $query->latest()->get();

        // Jika data kosong
        if ($data->isEmpty()) {
            return ApiResponse::error('Data pengajuan tidak ditemukan', 404, []);
        }

        return ApiResponse::success(
            ProcurementRequestResource::collection($data),
            'Data pengajuan berhasil ditampilkan'
        );
    }

    // Melihat detail request
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $procurement = ProcurementRequest::with('user')->find($id);

        if (!$procurement) {
            return ApiResponse::error('Pengajuan tidak ditemukan', 404);
        }

        if ($user->role !== 'admin_jurusan' && $procurement->user_id !== $user->id) {
            return ApiResponse::error('Anda tidak berhak melihat pengajuan ini', 403);
        }

        return ApiResponse::success(
            new ProcurementRequestResource($procurement),
            'Detail pengajuan berhasil ditampilkan'
        );
    }

    //Update data pengajuan pengadaan
    public function updateRequest(Request $request, $id)
    {
        $user = $request->user();
        $procurement = ProcurementRequest::find($id);

        if (!$procurement) {
            return ApiResponse::error('Pengajuan tidak ditemukan', 404);
        }

        if ($procurement->user_id !== $user->id) {
            return ApiResponse::error('Anda tidak berhak mengedit pengajuan ini', 403);
        }

        if ($procurement->request_status !== 'pending') {
            return ApiResponse::error('Pengajuan hanya bisa diubah jika status pending', 400);
        }

        $validated = $request->validate([
            'assetName' => 'sometimes|required|string',
            'quantity'  => 'sometimes|required|integer|min:1',
            'category'  => 'sometimes|required|in:electronics,furniture,stationary',
            'reason'    => 'sometimes|required|string',
            'image_reference' => 'nullable|string',
        ]);

        $procurement->update([
            'asset_name' => $validated['assetName'] ?? $procurement->asset_name,
            'quantity'   => $validated['quantity'] ?? $procurement->quantity,
            'category'   => $validated['category'] ?? $procurement->category,
            'reason'     => $validated['reason'] ?? $procurement->reason,
            'image_reference' => $validated['image_reference'] ?? $procurement->image_reference,
        ]);

        return ApiResponse::success(new ProcurementRequestResource($procurement->fresh()),
            'Pengajuan berhasil diperbarui');
    }

    // Update data status pengajuan pengadaan
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'admin_jurusan') {
            return ApiResponse::error('Akses ditolak, hanya Admin Jurusan yang berhak mengubah status', 403);
        }

        $procurement = ProcurementRequest::find($id);

        if (!$procurement) {
            return ApiResponse::error('Pengajuan tidak ditemukan', 404);
        }

        if ($procurement->request_status !== 'pending') {
            return ApiResponse::error(
                'Status hanya dapat diubah jika pengajuan masih pending',
                400
            );
        }

        $validated = $request->validate([
            'requestStatus'   => 'required|in:approved,rejected',
            'rejectionReason' => 'nullable|string|required_if:requestStatus,rejected',
        ]);

        $procurement->update([
            'request_status' => $validated['requestStatus'],
            'rejection_reason' => $validated['rejectionReason'] ?? null,
        ]);

        return ApiResponse::success(
            new ProcurementRequestResource($procurement->fresh()),
            'Status pengajuan berhasil diperbarui'
        );
    }

    // Dosen & Admin Lab boleh menghapus pengajuan jika pending
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $procurement = ProcurementRequest::find($id);

        if (!$procurement) {
            return ApiResponse::error('Pengajuan tidak ditemukan', 404);
        }

        if ($user->role === 'admin_jurusan') {
            return ApiResponse::error('Admin jurusan tidak boleh menghapus pengajuan', 403);
        }

        if ($procurement->user_id !== $user->id) {
            return ApiResponse::error('Anda tidak berhak menghapus pengajuan ini', 403);
        }

        if ($procurement->request_status !== 'pending') {
            return ApiResponse::error('Pengajuan hanya dapat dihapus jika status masih pending', 400);
        }
        $procurement->delete();
        return response()->noContent();
    }
}