<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StandardRx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StandardRxController extends Controller
{
    /**
     * Display a listing of standard rx.
     */
    public function index()
    {
        $hospitalId = Auth::user()->hospital_id;

        $standardRxList = StandardRx::where('hospital_id', $hospitalId)
            ->where('is_active', true)
            ->orderBy('disease_name', 'asc')
            ->get(['standard_rx_id as id', 'disease_name']);

        return response()->json($standardRxList);
    }

    /**
     * Display the specified standard rx with drugs.
     */
    public function show(StandardRx $standardRx)
    {
        if ($standardRx->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $standardRx->load('drugs');

        return response()->json([
            'drugs' => $standardRx->drugs->map(function ($drug) {
                return [
                    'drug_master_id' => $drug->drug_master_id,
                    'drug_name' => $drug->drug_name,
                    'drug_type' => $drug->drug_type,
                    'language' => $drug->language,
                    'dose_advice' => $drug->dose_advice,
                    'days' => $drug->days,
                    'qty' => $drug->qty,
                ];
            }),
            'advice' => $standardRx->advice,
        ]);
    }
}
