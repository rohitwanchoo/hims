<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperationTheater;
use Illuminate\Http\Request;

class OperationTheaterController extends Controller
{
    public function index(Request $request)
    {
        $query = OperationTheater::query();

        if ($request->ot_type) {
            $query->where('ot_type', $request->ot_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $theaters = $query->orderBy('ot_name')->get();

        return response()->json(['theaters' => $theaters]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ot_code' => 'required|string|max:20',
            'ot_name' => 'required|string|max:100',
            'ot_type' => 'required|in:major,minor,daycare,emergency',
            'floor' => 'nullable|string|max:20',
            'building' => 'nullable|string|max:50',
            'has_laminar_flow' => 'nullable|boolean',
            'has_c_arm' => 'nullable|boolean',
            'has_video_scope' => 'nullable|boolean',
            'charges_per_hour' => 'nullable|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $theater = OperationTheater::create($validated);

        return response()->json([
            'message' => 'Operation theater created successfully',
            'theater' => $theater,
        ], 201);
    }

    public function show(OperationTheater $theater)
    {
        return response()->json(['theater' => $theater]);
    }

    public function update(Request $request, OperationTheater $theater)
    {
        $validated = $request->validate([
            'ot_name' => 'sometimes|string|max:100',
            'ot_type' => 'sometimes|in:major,minor,daycare,emergency',
            'floor' => 'nullable|string|max:20',
            'building' => 'nullable|string|max:50',
            'has_laminar_flow' => 'nullable|boolean',
            'has_c_arm' => 'nullable|boolean',
            'has_video_scope' => 'nullable|boolean',
            'charges_per_hour' => 'nullable|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $theater->update($validated);

        return response()->json([
            'message' => 'Operation theater updated successfully',
            'theater' => $theater,
        ]);
    }

    public function destroy(OperationTheater $theater)
    {
        if ($theater->schedules()->exists()) {
            return response()->json([
                'message' => 'Cannot delete theater with existing schedules',
            ], 422);
        }

        $theater->delete();

        return response()->json([
            'message' => 'Operation theater deleted successfully',
        ]);
    }

    public function availability(Request $request)
    {
        $date = $request->date ?? today()->toDateString();

        $theaters = OperationTheater::where('is_active', true)
            ->with(['schedules' => function ($q) use ($date) {
                $q->whereDate('scheduled_date', $date)
                    ->whereNotIn('status', ['cancelled', 'completed']);
            }])
            ->get()
            ->map(function ($theater) use ($date) {
                $scheduledHours = $theater->schedules->sum(function ($schedule) {
                    return $schedule->estimated_duration_mins / 60;
                });

                return [
                    'theater' => $theater,
                    'scheduled_hours' => round($scheduledHours, 1),
                    'available_hours' => 12 - $scheduledHours, // Assuming 12-hour day
                    'schedules' => $theater->schedules,
                ];
            });

        return response()->json(['availability' => $theaters]);
    }
}
