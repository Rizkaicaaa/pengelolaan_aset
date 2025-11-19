<?php

namespace App\Http\Controllers\Api;

use App\Models\ProcurementRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProcurementRequestResource;
use App\Http\Helpers\ApiResponse;
use Illuminate\Http\Request;

class ProcurementRequestController extends Controller
{
    // 1. Dosen + Admin Lab submit request
    public function store(Request $request)
    {
        $validated = $request->validate([
            'assetName' => 'required|string',
            'quantity'  => 'required|integer|min:1',
            'category'  => 'required|in:electronics,furniture,stationary',
            'reason'    => 'required|string',
        ]);

        $requestData = [
            'user_id'   => $request->user()->id,
            'asset_name'=> $validated['assetName'],
            'quantity'  => $validated['quantity'],
            'category'  => $validated['category'],
            'reason'    => $validated['reason'],
        ];

        $procurement = ProcurementRequest::create($requestData);

        return ApiResponse::success(
            new ProcurementRequestResource($procurement),
            'Procurement request created',
            201
        );
    }

    // 2. Dosen + Admin Lab melihat request miliknya
    public function myRequests(Request $request)
    {
        $requests = ProcurementRequest::with('user')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return ApiResponse::success(
            ProcurementRequestResource::collection($requests)
        );
    }

    // 3. Admin jurusan melihat semua request
    public function index()
    {
        $requests = ProcurementRequest::with('user')
            ->latest()
            ->get();

        return ApiResponse::success(
            ProcurementRequestResource::collection($requests)
        );
    }

    // 4. Admin jurusan approve/reject
    public function update(Request $request, ProcurementRequest $procurement)
    {
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
            'Procurement request updated'
        );
    }
}