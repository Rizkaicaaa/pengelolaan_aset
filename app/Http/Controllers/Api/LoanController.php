<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\AssetItem;
use App\Http\Helpers\ApiResponse;
use App\Http\Resources\LoanResource;
use App\Http\Resources\LoanCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    /**
     * 1. MENAMBAH PEMINJAMAN
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_item_id' => 'required|exists:asset_items,id',
            'loan_purpose' => 'required|string|max:500',
            'loan_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after:loan_date'
        ], [
            'asset_item_id.required' => 'Item aset harus dipilih',
            'asset_item_id.exists' => 'Item aset tidak ditemukan',
            'loan_purpose.required' => 'Keperluan peminjaman harus diisi',
            'loan_date.required' => 'Tanggal pinjam harus diisi',
            'loan_date.after_or_equal' => 'Tanggal pinjam minimal hari ini',
            'return_date.required' => 'Tanggal kembali harus diisi',
            'return_date.after' => 'Tanggal kembali harus setelah tanggal pinjam'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal', 422, $validator->errors());
        }

        $assetItem = AssetItem::with('asset')->find($request->asset_item_id);

        if ($assetItem->status !== 'available') {
            return ApiResponse::error(
                'Item tidak tersedia untuk dipinjam. Status saat ini: ' . $assetItem->status,
                400
            );
        }

        if ($assetItem->condition === 'damaged') {
            return ApiResponse::error('Item dalam kondisi rusak, tidak dapat dipinjam', 400);
        }

        // Cek bentrok tanggal
        $conflictingLoan = Loan::where('asset_item_id', $request->asset_item_id)
            ->whereIn('loan_status', ['pending', 'approved'])
            ->where(function($query) use ($request) {
                $query->whereBetween('loan_date', [$request->loan_date, $request->return_date])
                      ->orWhereBetween('return_date', [$request->loan_date, $request->return_date])
                      ->orWhere(function($q) use ($request) {
                          $q->where('loan_date', '<=', $request->loan_date)
                            ->where('return_date', '>=', $request->return_date);
                      });
            })
            ->exists();

        if ($conflictingLoan) {
            return ApiResponse::error('Item sudah dipinjam pada rentang tanggal tersebut', 400);
        }

        $loan = Loan::create([
            'user_id' => auth()->id(),
            'asset_item_id' => $request->asset_item_id,
            'loan_purpose' => $request->loan_purpose,
            'loan_date' => $request->loan_date,
            'return_date' => $request->return_date,
            'loan_status' => 'pending'
        ]);

        $assetItem->update(['status' => 'borrowed']);

        $loan->load(['user', 'assetItem.asset']);

        return ApiResponse::success(
            new LoanResource($loan),
            'Peminjaman berhasil diajukan',
            201
        );
    }

    /**
     * 2. MELIHAT RIWAYAT PEMINJAMAN SENDIRI
     */
    public function myLoans(Request $request)
    {
        $query = Loan::with(['assetItem.asset'])
            ->where('user_id', auth()->id());

        if ($request->has('status')) {
            $query->where('loan_status', $request->status);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSortFields = ['loan_date', 'return_date', 'created_at', 'loan_status'];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 10);
        $loans = $query->paginate($perPage);

        return ApiResponse::success(
            new LoanCollection($loans),
            'Riwayat peminjaman berhasil diambil'
        );
    }

    /**
     * 3. MENGEDIT PEMINJAMAN
     */
    public function update(Request $request, $id)
    {
        $loan = Loan::find($id);
        $user = auth()->user();

        if (!$loan) {
            return ApiResponse::error('Peminjaman tidak ditemukan', 404);
        }

        if ($user->role === 'admin_jurusan') {
            // Admin Jurusan update status
            $validator = Validator::make($request->all(), [
                'loan_status' => 'required|in:approved,rejected,returned',
                'rejection_reason' => 'required_if:loan_status,rejected|string|nullable',
            ], [
                'loan_status.required' => 'Status peminjaman harus diisi',
                'loan_status.in' => 'Status harus approved, rejected, atau returned',
                'rejection_reason.required_if' => 'Alasan penolakan harus diisi jika status rejected',
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validasi gagal', 422, $validator->errors());
            }

            $validated = $validator->validated();

            // Validasi transisi status
            if ($validated['loan_status'] === 'approved' && $loan->loan_status !== 'pending') {
                return ApiResponse::error('Hanya peminjaman pending yang bisa diapprove', 400);
            }
            if ($validated['loan_status'] === 'rejected' && $loan->loan_status !== 'pending') {
                return ApiResponse::error('Hanya peminjaman pending yang bisa ditolak', 400);
            }
            if ($validated['loan_status'] === 'returned' && $loan->loan_status !== 'approved') {
                return ApiResponse::error('Hanya peminjaman approved yang bisa dikembalikan', 400);
            }

            $loan->loan_status = $validated['loan_status'];
            $loan->rejection_reason = $validated['rejection_reason'] ?? null;
            $loan->save();

            // Update status item
            if (in_array($validated['loan_status'], ['rejected', 'returned'])) {
                $loan->assetItem->update(['status' => 'available']);
            }
            if ($validated['loan_status'] === 'approved') {
                $loan->assetItem->update(['status' => 'borrowed']);
            }

        } else {
            // Dosen/Admin Lab edit peminjaman sendiri
            if ($loan->user_id !== $user->id) {
                return ApiResponse::error('Anda tidak berhak mengedit peminjaman ini', 403);
            }
            if ($loan->loan_status !== 'pending') {
                return ApiResponse::error('Hanya peminjaman pending yang bisa diedit', 400);
            }

            $validator = Validator::make($request->all(), [
                'loan_purpose' => 'sometimes|required|string|max:500',
                'loan_date' => 'sometimes|required|date|after_or_equal:today',
                'return_date' => 'sometimes|required|date|after:' . ($request->loan_date ?? $loan->loan_date)
            ], [
                'loan_purpose.required' => 'Keperluan peminjaman harus diisi',
                'loan_date.after_or_equal' => 'Tanggal pinjam minimal hari ini',
                'return_date.after' => 'Tanggal kembali harus setelah tanggal pinjam',
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validasi gagal', 422, $validator->errors());
            }

            $loan->update($validator->validated());
        }

        $loan->load(['user', 'assetItem.asset']);

        return ApiResponse::success(
            new LoanResource($loan),
            'Peminjaman berhasil diperbarui'
        );
    }

    /**
     * 4. MENGHAPUS PEMINJAMAN
     */
    public function destroy($id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return ApiResponse::error('Peminjaman tidak ditemukan', 404);
        }

        if ($loan->user_id !== auth()->id()) {
            return ApiResponse::error('Anda tidak berhak menghapus peminjaman ini', 403);
        }

        if ($loan->loan_status !== 'pending') {
            return ApiResponse::error(
                'Hanya peminjaman dengan status pending yang dapat dihapus',
                400
            );
        }

        $loan->assetItem->update(['status' => 'available']);
        $loan->delete();

        return ApiResponse::success(null, 'Peminjaman berhasil dihapus');
    }

    /**
     * 5. MELIHAT DAFTAR SEMUA PEMINJAMAN (Admin)
     */
    public function index(Request $request)
    {
        $query = Loan::with(['user', 'assetItem.asset']);

        if ($request->has('status')) {
            $query->where('loan_status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('role')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('role', $request->role);
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('assetItem.asset', function($assetQuery) use ($search) {
                    $assetQuery->where('name', 'LIKE', "%{$search}%");
                })
                ->orWhere('loan_purpose', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('loan_date', [$request->start_date, $request->end_date]);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSortFields = ['loan_date', 'return_date', 'created_at', 'loan_status'];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 10);
        $loans = $query->paginate($perPage);

        return ApiResponse::success(
            new LoanCollection($loans),
            'Daftar peminjaman berhasil diambil'
        );
    }

    /**
     * MELIHAT DETAIL PEMINJAMAN
     */
    public function show($id)
    {
        $loan = Loan::with(['user', 'assetItem.asset'])->find($id);

        if (!$loan) {
            return ApiResponse::error('Peminjaman tidak ditemukan', 404);
        }

        if (auth()->user()->role !== 'admin_jurusan' && $loan->user_id !== auth()->id()) {
            return ApiResponse::error('Anda tidak berhak melihat detail peminjaman ini', 403);
        }

        return ApiResponse::success(
            new LoanResource($loan),
            'Detail peminjaman berhasil diambil'
        );
    }
}

    /**
 * 6. APPROVE / REJECT PEMINJAMAN (Admin Jurusan)
 * Method: PUT
 * URL: /api/loans/{id}/approve-reject
 * Body: loan_status (approved/rejected), rejection_reason (optional jika rejected)
 * Role: admin_jurusan
 */
// public function approveReject(Request $request, $id)
// {
//     $loan = Loan::find($id);

//     if (!$loan) {
//         return ApiResponse::error('Peminjaman tidak ditemukan', 404);
//     }

//     $validated = $request->validate([
//         'loan_status' => 'required|in:approved,rejected',
//         'rejection_reason' => 'nullable|string|required_if:loan_status,rejected',
//     ]);

//     $loan->update([
//         'loan_status' => $validated['loan_status'],
//         'rejection_reason' => $validated['rejection_reason'] ?? null,
//     ]);

//     // Jika ditolak, kembalikan status item menjadi available
//     if ($validated['loan_status'] === 'rejected') {
//         $loan->assetItem->update(['status' => 'available']);
//     }

//     $loan->load([
//         'user:id,name,email,role',
//         'assetItem.asset:id,name,category'
//     ]);

//     return ApiResponse::success(
//         $loan,
//         'Status peminjaman berhasil diperbarui'
//     );
// }

/**
 * 7. PENGEMBALIAN PEMINJAMAN (Admin Jurusan)
 * Method: PUT
 * URL: /api/loans/{id}/return
 * Role: admin_jurusan
 */
// public function markAsReturned($id)
// {
//     $loan = Loan::find($id);

//     if (!$loan) {
//         return ApiResponse::error('Peminjaman tidak ditemukan', 404);
//     }

//     // Hanya bisa mengubah status jika sebelumnya approved atau borrowed
//     if (!in_array($loan->loan_status, ['approved', 'borrowed'])) {
//         return ApiResponse::error(
//             'Peminjaman hanya bisa dikembalikan jika statusnya approved atau borrowed. Status saat ini: ' . $loan->loan_status,
//             400
//         );
//     }

//     $loan->update(['loan_status' => 'returned']);

//     // Kembalikan status item menjadi tersedia
//     $loan->assetItem->update(['status' => 'available']);

//     $loan->load([
//         'user:id,name,email,role',
//         'assetItem.asset:id,name,category'
//     ]);

//     return ApiResponse::success(
//         $loan,
//         'Peminjaman telah dikembalikan'
//     );
// }

