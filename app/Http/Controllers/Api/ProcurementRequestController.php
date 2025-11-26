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
        ]);

        $requestData = [
            'user_id'    => $request->user()->id,
            'asset_name' => $validated['assetName'],
            'quantity'   => $validated['quantity'],
            'category'   => $validated['category'],
            'reason'     => $validated['reason'],
        ];

        $procurement = ProcurementRequest::create($requestData);

        return ApiResponse::success(
            new ProcurementRequestResource($procurement),
            'Procurement request created',
            201
        );
    }


    // Admin jurusan melihat semua request, Dosen + Admin Lab melihat request miliknya
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ProcurementRequest::with('user');

        if ($user->role !== 'admin_jurusan') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('search')) {
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

        return ApiResponse::success(
            ProcurementRequestResource::collection($data)
        );
    }

    //Update data pengajuan pengadaan

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $procurement = ProcurementRequest::find($id);

        if (!$procurement) {
            return ApiResponse::error('Pengajuan tidak ditemukan', 404);
        }

        // Jika Admin Jurusan → handle logic khusus admin
        if ($user->role === 'admin_jurusan') {
            return $this->updateStatus($request, $procurement);
        }

        // Jika Dosen/Admin Lab → handle logic khusus pemilik
        return $this->updateRequest($request, $procurement, $user);
    }
    
    // Admin Jurusan mengedit status (approve/reject)
 // Admin Jurusan mengedit status (approve/reject)
    private function updateStatus(Request $request, $procurement)
    {
        // ❗ Tambahan: Hanya bisa update jika status PENDING
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
            'request_status'   => $validated['requestStatus'],
            'rejection_reason' => $validated['rejectionReason'] ?? null,
        ]);

        return ApiResponse::success(
            new ProcurementRequestResource($procurement),
            'Status pengajuan berhasil diperbarui oleh Admin Jurusan'
        );
    }


    // Dosen/Admin Lab bisa edit pengajuan miliknya jika masih pending
    private function updateRequest(Request $request, $procurement, $user)
    {
        // Pastikan miliknya sendiri
        if ($procurement->user_id !== $user->id) {
            return ApiResponse::error('Anda tidak berhak mengedit pengajuan ini', 403);
        }

        // Hanya boleh mengedit jika status pending
        if ($procurement->request_status !== 'pending') {
            return ApiResponse::error('Data pengajuan hanya bisa diedit jika status masih pending', 400);
        }

        // Validasi field yang boleh diedit
        $validated = $request->validate([
            'assetName' => 'sometimes|required|string',
            'quantity'  => 'sometimes|required|integer|min:1',
            'category'  => 'sometimes|required|in:electronics,furniture,stationary',
            'reason'    => 'sometimes|required|string',
        ]);

        $procurement->update([
            'asset_name' => $validated['assetName'] ?? $procurement->asset_name,
            'quantity'   => $validated['quantity'] ?? $procurement->quantity,
            'category'   => $validated['category'] ?? $procurement->category,
            'reason'     => $validated['reason'] ?? $procurement->reason,
        ]);

        return ApiResponse::success(
            new ProcurementRequestResource($procurement),
            'Pengajuan berhasil diperbarui'
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
            'Detail procurement request'
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

        return ApiResponse::success([], 'Pengajuan berhasil dihapus');
    }
}