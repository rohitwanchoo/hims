<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EntryCard;
use App\Models\OpdConfiguration;
use App\Models\Patient;
use Illuminate\Http\Request;

class EntryCardController extends Controller
{
    /**
     * Display entry cards
     */
    public function index(Request $request)
    {
        $query = EntryCard::with(['patient', 'issuedBy']);

        if ($request->patient_id) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('issue_date', [$request->from_date, $request->to_date]);
        }

        // Filter expired cards
        if ($request->expired === 'true') {
            $query->where('valid_to', '<', now()->toDateString());
        } elseif ($request->expired === 'false') {
            $query->where(function ($q) {
                $q->whereNull('valid_to')
                  ->orWhere('valid_to', '>=', now()->toDateString());
            });
        }

        $cards = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        return response()->json($cards);
    }

    /**
     * Issue new entry card
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'amount' => 'nullable|numeric|min:0',
            'validity_type' => 'nullable|in:one_time,daily,monthly,half_yearly,yearly,lifetime',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ]);

        // Check if patient already has valid card
        $existingCard = EntryCard::getValidCardForPatient($validated['patient_id']);
        if ($existingCard) {
            return response()->json([
                'message' => 'Patient already has a valid entry card',
                'existing_card' => $existingCard,
            ], 422);
        }

        // Get configuration for defaults
        $config = OpdConfiguration::where('is_active', true)->first();

        $validityType = $validated['validity_type'] ?? $config->entry_card_validity_type ?? 'yearly';
        $amount = $validated['amount'] ?? $config->entry_card_amount ?? 0;

        // Calculate validity dates
        $validFrom = $validated['valid_from'] ?? now()->toDateString();
        $validTo = $validated['valid_to'] ?? $this->calculateValidTo($validFrom, $validityType);

        $card = EntryCard::create([
            'card_number' => EntryCard::generateCardNumber(request()->user()->hospital_id ?? 1),
            'patient_id' => $validated['patient_id'],
            'amount' => $amount,
            'issue_date' => now()->toDateString(),
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'validity_type' => $validityType,
            'status' => 'active',
            'issued_by' => $request->user()->id ?? null,
        ]);

        return response()->json($card->load(['patient', 'issuedBy']), 201);
    }

    /**
     * Display specific entry card
     */
    public function show(string $id)
    {
        $card = EntryCard::with(['patient', 'issuedBy', 'bill'])->findOrFail($id);

        return response()->json([
            'card' => $card,
            'is_valid' => $card->isValid(),
        ]);
    }

    /**
     * Cancel entry card
     */
    public function cancel(string $id)
    {
        $card = EntryCard::findOrFail($id);

        if ($card->status !== 'active') {
            return response()->json(['message' => 'Card is not active'], 422);
        }

        $card->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Entry card cancelled']);
    }

    /**
     * Renew entry card
     */
    public function renew(Request $request, string $id)
    {
        $card = EntryCard::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'validity_type' => 'nullable|in:one_time,daily,monthly,half_yearly,yearly,lifetime',
        ]);

        // Create new card
        $validityType = $validated['validity_type'] ?? $card->validity_type;
        $validFrom = now()->toDateString();
        $validTo = $this->calculateValidTo($validFrom, $validityType);

        // Get configuration for default amount
        $config = OpdConfiguration::where('is_active', true)->first();
        $amount = $validated['amount'] ?? $config->entry_card_amount ?? $card->amount;

        // Mark old card as expired
        $card->update(['status' => 'expired']);

        // Create new card
        $newCard = EntryCard::create([
            'card_number' => EntryCard::generateCardNumber(request()->user()->hospital_id ?? 1),
            'patient_id' => $card->patient_id,
            'amount' => $amount,
            'issue_date' => now()->toDateString(),
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'validity_type' => $validityType,
            'status' => 'active',
            'issued_by' => $request->user()->id ?? null,
        ]);

        return response()->json([
            'message' => 'Entry card renewed',
            'new_card' => $newCard->load('patient'),
            'old_card' => $card,
        ]);
    }

    /**
     * Check if patient has valid entry card
     */
    public function checkValidity(string $patientId)
    {
        $card = EntryCard::getValidCardForPatient($patientId);

        return response()->json([
            'patient_id' => $patientId,
            'has_valid_card' => $card !== null,
            'card' => $card,
        ]);
    }

    /**
     * Calculate valid_to date based on validity type
     */
    private function calculateValidTo($validFrom, $validityType)
    {
        $from = \Carbon\Carbon::parse($validFrom);

        return match ($validityType) {
            'one_time' => $from->toDateString(),
            'daily' => $from->toDateString(),
            'monthly' => $from->addMonth()->subDay()->toDateString(),
            'half_yearly' => $from->addMonths(6)->subDay()->toDateString(),
            'yearly' => $from->addYear()->subDay()->toDateString(),
            'lifetime' => null,
            default => $from->addYear()->subDay()->toDateString(),
        };
    }
}
