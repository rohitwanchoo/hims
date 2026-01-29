<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BedTransfer;
use App\Models\Bed;
use App\Models\IpdAdmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BedTransferController extends Controller
{
    /**
     * Display a listing of bed transfers
     */
    public function index(Request $request)
    {
        $hospitalId = $request->user()->hospital_id;

        $transfers = BedTransfer::where('hospital_id', $hospitalId)
            ->with([
                'ipdAdmission.patient',
                'fromBed',
                'toBed',
                'fromWard',
                'toWard',
                'transferredByUser',
                'swapIpdAdmission.patient'
            ])
            ->orderBy('transfer_datetime', 'desc')
            ->get()
            ->map(function ($transfer) {
                $displayType = $transfer->transfer_type;
                if ($transfer->move_completion_type) {
                    $displayType = $transfer->move_completion_type === 'back_to_origin'
                        ? 'back to origin'
                        : 'move to new bed';
                }

                return [
                    'transfer_id' => $transfer->transfer_id,
                    'transfer_type' => $transfer->transfer_type,
                    'display_type' => $displayType,
                    'is_move_completed' => $transfer->is_move_completed,
                    'move_completion_type' => $transfer->move_completion_type,
                    'ipd_number' => $transfer->ipdAdmission->ipd_number ?? '',
                    'patient_name' => $transfer->ipdAdmission->patient->full_name ?? '',
                    'from_ward_name' => $transfer->fromWard->ward_name ?? '',
                    'from_bed_number' => $transfer->fromBed->bed_number ?? '',
                    'to_ward_name' => $transfer->toWard->ward_name ?? '',
                    'to_bed_number' => $transfer->toBed->bed_number ?? '',
                    'reason' => $transfer->reason,
                    'transfer_datetime' => $transfer->transfer_datetime,
                    'transferred_by_name' => $transfer->transferredByUser->full_name ?? $transfer->transferredByUser->name ?? '',
                ];
            });

        return response()->json(['data' => $transfers]);
    }

    /**
     * Get active move transfers for a patient
     */
    public function getActiveMove(Request $request)
    {
        $ipdId = $request->ipd_id;
        $hospitalId = $request->user()->hospital_id;

        $activeMove = BedTransfer::where('hospital_id', $hospitalId)
            ->where('ipd_id', $ipdId)
            ->where('transfer_type', 'move')
            ->where('is_move_completed', false)
            ->with(['fromBed.ward', 'toBed.ward'])
            ->first();

        if ($activeMove) {
            return response()->json([
                'has_active_move' => true,
                'active_move' => [
                    'transfer_id' => $activeMove->transfer_id,
                    'from_bed_id' => $activeMove->from_bed_id,
                    'from_bed_number' => $activeMove->fromBed->bed_number ?? '',
                    'from_ward_name' => $activeMove->fromWard->ward_name ?? '',
                    'current_bed_id' => $activeMove->to_bed_id,
                    'current_bed_number' => $activeMove->toBed->bed_number ?? '',
                    'current_ward_name' => $activeMove->toWard->ward_name ?? '',
                    'transfer_datetime' => $activeMove->transfer_datetime,
                    'reason' => $activeMove->reason,
                ]
            ]);
        }

        return response()->json(['has_active_move' => false]);
    }

    /**
     * Store a new bed transfer
     */
    public function store(Request $request)
    {
        $request->validate([
            'transfer_type' => 'required|in:transfer,swap,move,back_to_origin,new_bed',
            'ipd_id' => 'required|exists:ipd_admissions,ipd_id',
            'swap_ipd_id' => 'required_if:transfer_type,swap|nullable|exists:ipd_admissions,ipd_id',
            'to_bed_id' => 'required_if:transfer_type,transfer,move,new_bed|nullable|exists:beds,bed_id',
            'parent_move_transfer_id' => 'required_if:transfer_type,back_to_origin,new_bed|nullable|exists:bed_transfers,transfer_id',
            'reason' => 'required|string',
            'transfer_date' => 'required|date',
            'transfer_time' => 'required|date_format:H:i',
        ]);

        $hospitalId = $request->user()->hospital_id;
        $userId = $request->user()->user_id;

        try {
            DB::beginTransaction();

            // Get the IPD admission
            $ipdAdmission = IpdAdmission::where('ipd_id', $request->ipd_id)
                ->where('hospital_id', $hospitalId)
                ->firstOrFail();

            $fromBedId = $ipdAdmission->bed_id;
            $fromBed = Bed::findOrFail($fromBedId);
            $fromWardId = $fromBed->ward_id;

            $transferDatetime = $request->transfer_date . ' ' . $request->transfer_time;

            if ($request->transfer_type === 'transfer') {
                // Transfer: Empty old bed, occupy new bed
                $toBed = Bed::findOrFail($request->to_bed_id);

                if ($toBed->status !== 'available') {
                    throw new \Exception('Target bed is not available');
                }

                // Update patient's bed
                $ipdAdmission->bed_id = $request->to_bed_id;
                $ipdAdmission->save();

                // Empty old bed
                $fromBed->status = 'available';
                $fromBed->save();

                // Occupy new bed
                $toBed->status = 'occupied';
                $toBed->save();

                // Create transfer record
                BedTransfer::create([
                    'hospital_id' => $hospitalId,
                    'ipd_id' => $request->ipd_id,
                    'transfer_type' => 'transfer',
                    'from_bed_id' => $fromBedId,
                    'to_bed_id' => $request->to_bed_id,
                    'from_ward_id' => $fromWardId,
                    'to_ward_id' => $toBed->ward_id,
                    'transfer_datetime' => $transferDatetime,
                    'reason' => $request->reason,
                    'transferred_by' => $userId,
                ]);

            } elseif ($request->transfer_type === 'swap') {
                // Swap: Exchange beds between 2 patients
                $swapIpdAdmission = IpdAdmission::where('ipd_id', $request->swap_ipd_id)
                    ->where('hospital_id', $hospitalId)
                    ->firstOrFail();

                $swapBedId = $swapIpdAdmission->bed_id;
                $swapBed = Bed::findOrFail($swapBedId);

                // Swap bed assignments
                $ipdAdmission->bed_id = $swapBedId;
                $ipdAdmission->save();

                $swapIpdAdmission->bed_id = $fromBedId;
                $swapIpdAdmission->save();

                // Create transfer records for both patients
                BedTransfer::create([
                    'hospital_id' => $hospitalId,
                    'ipd_id' => $request->ipd_id,
                    'transfer_type' => 'swap',
                    'from_bed_id' => $fromBedId,
                    'to_bed_id' => $swapBedId,
                    'from_ward_id' => $fromWardId,
                    'to_ward_id' => $swapBed->ward_id,
                    'swap_ipd_id' => $request->swap_ipd_id,
                    'transfer_datetime' => $transferDatetime,
                    'reason' => $request->reason,
                    'transferred_by' => $userId,
                ]);

                BedTransfer::create([
                    'hospital_id' => $hospitalId,
                    'ipd_id' => $request->swap_ipd_id,
                    'transfer_type' => 'swap',
                    'from_bed_id' => $swapBedId,
                    'to_bed_id' => $fromBedId,
                    'from_ward_id' => $swapBed->ward_id,
                    'to_ward_id' => $fromWardId,
                    'swap_ipd_id' => $request->ipd_id,
                    'transfer_datetime' => $transferDatetime,
                    'reason' => $request->reason,
                    'transferred_by' => $userId,
                ]);

            } elseif ($request->transfer_type === 'move') {
                // Move: Occupy new bed, keep old bed occupied
                $toBed = Bed::findOrFail($request->to_bed_id);

                if ($toBed->status !== 'available') {
                    throw new \Exception('Target bed is not available');
                }

                // Update patient's bed (now occupies both beds)
                $ipdAdmission->bed_id = $request->to_bed_id;
                $ipdAdmission->save();

                // Keep old bed occupied (don't change status)
                // Occupy new bed
                $toBed->status = 'occupied';
                $toBed->save();

                // Create transfer record
                BedTransfer::create([
                    'hospital_id' => $hospitalId,
                    'ipd_id' => $request->ipd_id,
                    'transfer_type' => 'move',
                    'from_bed_id' => $fromBedId,
                    'to_bed_id' => $request->to_bed_id,
                    'from_ward_id' => $fromWardId,
                    'to_ward_id' => $toBed->ward_id,
                    'transfer_datetime' => $transferDatetime,
                    'reason' => $request->reason,
                    'transferred_by' => $userId,
                ]);

            } elseif ($request->transfer_type === 'back_to_origin') {
                // Back to Origin: Return patient to original bed from move
                $parentMove = BedTransfer::findOrFail($request->parent_move_transfer_id);

                if ($parentMove->is_move_completed) {
                    throw new \Exception('This move has already been completed');
                }

                $originBedId = $parentMove->from_bed_id;
                $currentBedId = $ipdAdmission->bed_id;

                $originBed = Bed::findOrFail($originBedId);
                $currentBed = Bed::findOrFail($currentBedId);

                // Move patient back to original bed
                $ipdAdmission->bed_id = $originBedId;
                $ipdAdmission->save();

                // Free current bed
                $currentBed->status = 'available';
                $currentBed->save();

                // Origin bed remains occupied (it was already occupied)

                // Mark parent move as completed
                $parentMove->is_move_completed = true;
                $parentMove->save();

                // Create transfer record
                BedTransfer::create([
                    'hospital_id' => $hospitalId,
                    'ipd_id' => $request->ipd_id,
                    'transfer_type' => 'transfer',
                    'from_bed_id' => $currentBedId,
                    'to_bed_id' => $originBedId,
                    'from_ward_id' => $currentBed->ward_id,
                    'to_ward_id' => $originBed->ward_id,
                    'parent_move_transfer_id' => $request->parent_move_transfer_id,
                    'move_completion_type' => 'back_to_origin',
                    'transfer_datetime' => $transferDatetime,
                    'reason' => $request->reason,
                    'transferred_by' => $userId,
                ]);

            } elseif ($request->transfer_type === 'new_bed') {
                // New Bed: Move patient to completely new bed, free both previous beds
                $parentMove = BedTransfer::findOrFail($request->parent_move_transfer_id);

                if ($parentMove->is_move_completed) {
                    throw new \Exception('This move has already been completed');
                }

                $toBed = Bed::findOrFail($request->to_bed_id);

                if ($toBed->status !== 'available') {
                    throw new \Exception('Target bed is not available');
                }

                $originBedId = $parentMove->from_bed_id;
                $currentBedId = $ipdAdmission->bed_id;

                $originBed = Bed::findOrFail($originBedId);
                $currentBed = Bed::findOrFail($currentBedId);

                // Move patient to new bed
                $ipdAdmission->bed_id = $request->to_bed_id;
                $ipdAdmission->save();

                // Free both previous beds
                $originBed->status = 'available';
                $originBed->save();

                $currentBed->status = 'available';
                $currentBed->save();

                // Occupy new bed
                $toBed->status = 'occupied';
                $toBed->save();

                // Mark parent move as completed
                $parentMove->is_move_completed = true;
                $parentMove->save();

                // Create transfer record
                BedTransfer::create([
                    'hospital_id' => $hospitalId,
                    'ipd_id' => $request->ipd_id,
                    'transfer_type' => 'transfer',
                    'from_bed_id' => $currentBedId,
                    'to_bed_id' => $request->to_bed_id,
                    'from_ward_id' => $currentBed->ward_id,
                    'to_ward_id' => $toBed->ward_id,
                    'parent_move_transfer_id' => $request->parent_move_transfer_id,
                    'move_completion_type' => 'new_bed',
                    'transfer_datetime' => $transferDatetime,
                    'reason' => $request->reason,
                    'transferred_by' => $userId,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bed transfer completed successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
