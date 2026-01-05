<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RateChangeRequest;
use App\Models\OpdConfiguration;
use Illuminate\Http\Request;

class RateChangeRequestController extends Controller
{
    /**
     * List all rate change requests
     */
    public function index(Request $request)
    {
        $query = RateChangeRequest::with(['opdVisit.patient', 'service', 'requestedBy', 'approvedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('opd_id')) {
            $query->where('opd_id', $request->opd_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        return response()->json($requests);
    }

    /**
     * Create a new rate change request
     */
    public function store(Request $request)
    {
        // Check if doctor approval is required for rate changes
        $config = OpdConfiguration::where('is_active', true)->first();

        if (!$config || !$config->require_doctor_approval_for_rate_change) {
            return response()->json([
                'message' => 'Rate change requests are not enabled. Rates can be changed directly.',
            ], 400);
        }

        $validated = $request->validate([
            'opd_id' => 'nullable|exists:opd_visits,opd_id',
            'service_id' => 'nullable|exists:services,service_id',
            'original_rate' => 'required|numeric|min:0',
            'requested_rate' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ]);

        $validated['hospital_id'] = auth()->user()->hospital_id;
        $validated['requested_by'] = auth()->user()->user_id;
        $validated['status'] = 'pending';

        $rateChangeRequest = RateChangeRequest::create($validated);

        return response()->json([
            'message' => 'Rate change request submitted successfully',
            'request' => $rateChangeRequest->load(['opdVisit', 'service', 'requestedBy']),
        ], 201);
    }

    /**
     * Show a specific rate change request
     */
    public function show(string $id)
    {
        $request = RateChangeRequest::with(['opdVisit.patient', 'service', 'requestedBy', 'approvedBy'])
            ->findOrFail($id);

        return response()->json($request);
    }

    /**
     * Approve a rate change request
     */
    public function approve(Request $request, string $id)
    {
        $rateChangeRequest = RateChangeRequest::findOrFail($id);

        if ($rateChangeRequest->status !== 'pending') {
            return response()->json([
                'message' => 'This request has already been ' . $rateChangeRequest->status,
            ], 400);
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        $rateChangeRequest->approve(auth()->user()->user_id, $validated['remarks'] ?? null);

        return response()->json([
            'message' => 'Rate change request approved successfully',
            'request' => $rateChangeRequest->fresh(['opdVisit', 'service', 'requestedBy', 'approvedBy']),
        ]);
    }

    /**
     * Reject a rate change request
     */
    public function reject(Request $request, string $id)
    {
        $rateChangeRequest = RateChangeRequest::findOrFail($id);

        if ($rateChangeRequest->status !== 'pending') {
            return response()->json([
                'message' => 'This request has already been ' . $rateChangeRequest->status,
            ], 400);
        }

        $validated = $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        $rateChangeRequest->reject(auth()->user()->user_id, $validated['remarks']);

        return response()->json([
            'message' => 'Rate change request rejected',
            'request' => $rateChangeRequest->fresh(['opdVisit', 'service', 'requestedBy', 'approvedBy']),
        ]);
    }

    /**
     * Get pending requests count (for dashboard/notifications)
     */
    public function pendingCount()
    {
        $count = RateChangeRequest::where('status', 'pending')->count();

        return response()->json([
            'pending_count' => $count,
        ]);
    }

    /**
     * Bulk approve requests
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'request_ids' => 'required|array',
            'request_ids.*' => 'exists:rate_change_requests,request_id',
            'remarks' => 'nullable|string|max:500',
        ]);

        $userId = auth()->user()->user_id;
        $approved = 0;
        $errors = [];

        foreach ($validated['request_ids'] as $requestId) {
            $rateChangeRequest = RateChangeRequest::find($requestId);

            if ($rateChangeRequest && $rateChangeRequest->status === 'pending') {
                $rateChangeRequest->approve($userId, $validated['remarks'] ?? null);
                $approved++;
            } else {
                $errors[] = "Request #{$requestId} could not be approved";
            }
        }

        return response()->json([
            'message' => "{$approved} requests approved successfully",
            'errors' => $errors,
        ]);
    }

    /**
     * Delete a pending request (only by requester)
     */
    public function destroy(string $id)
    {
        $rateChangeRequest = RateChangeRequest::findOrFail($id);

        if ($rateChangeRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending requests can be deleted',
            ], 400);
        }

        if ($rateChangeRequest->requested_by !== auth()->user()->user_id) {
            return response()->json([
                'message' => 'You can only delete your own requests',
            ], 403);
        }

        $rateChangeRequest->delete();

        return response()->json([
            'message' => 'Rate change request deleted successfully',
        ]);
    }
}
