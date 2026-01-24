<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Bed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms with their beds.
     */
    public function index(Request $request)
    {
        try {
            $query = Room::with(['ward', 'beds' => function($query) {
                $query->orderByRaw('CAST(bed_number AS UNSIGNED)');
            }]);

            // Filter by ward if provided
            if ($request->filled('ward_id')) {
                $query->where('ward_id', $request->ward_id);
            }

            $rooms = $query->orderBy('room_name')->get();

            return response()->json([
                'success' => true,
                'data' => $rooms
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch rooms',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created room with auto-generated beds.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ward_id' => 'required|exists:wards,ward_id',
            'room_name' => 'required|string|max:50',
            'bed_capacity' => 'required|integer|min:1|max:100',
            'room_type' => 'nullable|in:general,private,semi_private,icu,isolation',
            'floor_number' => 'nullable|integer',
            'room_description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create the room
            $room = Room::create([
                'ward_id' => $request->ward_id,
                'room_name' => $request->room_name,
                'bed_capacity' => $request->bed_capacity,
                'room_type' => $request->room_type ?? 'general',
                'floor_number' => $request->floor_number,
                'room_description' => $request->room_description,
            ]);

            // Get current hospital_id
            $hospitalId = app()->bound('current_hospital_id') ? app('current_hospital_id') : null;

            // Auto-create beds based on bed_capacity
            $beds = [];
            for ($i = 1; $i <= $request->bed_capacity; $i++) {
                $beds[] = [
                    'hospital_id' => $hospitalId,
                    'ward_id' => $request->ward_id,
                    'room_id' => $room->room_id,
                    'bed_number' => $i,
                    'status' => 'available',
                    'is_available' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Bed::insert($beds);

            DB::commit();

            // Reload the room with its beds and ward
            $room = Room::with(['beds' => function($query) {
                $query->orderByRaw('CAST(bed_number AS UNSIGNED)');
            }, 'ward'])->find($room->room_id);

            return response()->json([
                'success' => true,
                'message' => "Room created successfully with {$request->bed_capacity} beds",
                'data' => $room
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create room',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified room.
     */
    public function show($id)
    {
        try {
            $room = Room::with(['ward', 'beds' => function($query) {
                $query->orderByRaw('CAST(bed_number AS UNSIGNED)');
            }])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $room
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified room.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'room_name' => 'required|string|max:50',
            'room_type' => 'nullable|in:general,private,semi_private,icu,isolation',
            'floor_number' => 'nullable|integer',
            'room_description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $room = Room::findOrFail($id);

            $room->update([
                'room_name' => $request->room_name,
                'room_type' => $request->room_type ?? $room->room_type,
                'floor_number' => $request->floor_number,
                'room_description' => $request->room_description,
            ]);

            $room->load('beds', 'ward');

            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully',
                'data' => $room
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update room',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified room and its beds.
     */
    public function destroy($id)
    {
        try {
            $room = Room::findOrFail($id);

            // Check if any bed in the room is occupied
            $occupiedBeds = Bed::where('room_id', $id)
                ->where('status', 'occupied')
                ->count();

            if ($occupiedBeds > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete room. {$occupiedBeds} bed(s) are currently occupied."
                ], 422);
            }

            DB::beginTransaction();

            // Delete all beds in the room
            Bed::where('room_id', $id)->delete();

            // Delete the room
            $room->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Room and all its beds deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete room',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
