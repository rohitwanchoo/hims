<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RadiologyReport;
use App\Models\RadiologyOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RadiologyReportController extends Controller
{
    public function index(Request $request)
    {
        $query = RadiologyReport::with([
            'order.patient',
            'reportingRadiologist',
        ]);

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->report_status) {
            $query->where('report_status', $request->report_status);
        }

        if ($request->has('critical_finding')) {
            $query->where('critical_finding', $request->boolean('critical_finding'));
        }

        if ($request->radiologist_id) {
            $query->where('reporting_radiologist_id', $request->radiologist_id);
        }

        $reports = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($reports);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'radiology_order_id' => 'required|exists:radiology_orders,radiology_order_id',
            'reporting_radiologist_id' => 'required|exists:doctors,doctor_id',
            'technique' => 'nullable|string',
            'findings' => 'required|string',
            'impression' => 'required|string',
            'recommendation' => 'nullable|string',
            'critical_finding' => 'nullable|boolean',
            'report_status' => 'nullable|in:draft,preliminary,final',
        ]);

        $order = RadiologyOrder::findOrFail($validated['radiology_order_id']);

        $report = RadiologyReport::create([
            ...$validated,
            'report_status' => $validated['report_status'] ?? 'draft',
        ]);

        if ($validated['report_status'] === 'final') {
            $order->update(['status' => 'completed']);
        }

        return response()->json([
            'message' => 'Report created successfully',
            'report' => $report,
        ], 201);
    }

    public function show(RadiologyReport $report)
    {
        return response()->json([
            'report' => $report->load([
                'order.patient',
                'order.orderDetails.test',
                'reportingRadiologist',
                'verifiedBy',
                'images',
            ]),
        ]);
    }

    public function update(Request $request, RadiologyReport $report)
    {
        if ($report->report_status === 'final' && !$request->boolean('force_update')) {
            return response()->json([
                'message' => 'Cannot update finalized report',
            ], 422);
        }

        $validated = $request->validate([
            'technique' => 'nullable|string',
            'findings' => 'sometimes|string',
            'impression' => 'sometimes|string',
            'recommendation' => 'nullable|string',
            'critical_finding' => 'nullable|boolean',
            'report_status' => 'nullable|in:draft,preliminary,final',
        ]);

        $report->update($validated);

        if ($validated['report_status'] === 'final') {
            $report->order->update(['status' => 'completed']);
        }

        return response()->json([
            'message' => 'Report updated successfully',
            'report' => $report,
        ]);
    }

    public function verify(Request $request, RadiologyReport $report)
    {
        $validated = $request->validate([
            'verification_notes' => 'nullable|string',
        ]);

        $report->update([
            'report_status' => 'final',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $report->order->update(['status' => 'completed']);

        return response()->json([
            'message' => 'Report verified successfully',
            'report' => $report,
        ]);
    }

    public function uploadImage(Request $request, RadiologyReport $report)
    {
        $validated = $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,dcm|max:10240',
            'image_type' => 'nullable|in:xray,ct,mri,ultrasound,other',
            'description' => 'nullable|string',
        ]);

        $path = $request->file('image')->store('radiology/images', 'public');

        $image = $report->images()->create([
            'image_type' => $validated['image_type'] ?? 'other',
            'file_path' => $path,
            'original_filename' => $request->file('image')->getClientOriginalName(),
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'image' => $image,
        ], 201);
    }

    public function generatePdf(RadiologyReport $report)
    {
        // PDF generation logic would go here
        // For now, return the report data
        return response()->json([
            'report' => $report->load([
                'order.patient',
                'order.orderDetails.test',
                'reportingRadiologist',
            ]),
        ]);
    }

    public function pendingReports(Request $request)
    {
        $query = RadiologyOrder::with(['patient', 'orderDetails.test'])
            ->whereIn('status', ['in_progress', 'completed'])
            ->whereDoesntHave('reports', function ($q) {
                $q->where('report_status', 'final');
            });

        $orders = $query->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->get();

        return response()->json(['pending_orders' => $orders]);
    }
}
