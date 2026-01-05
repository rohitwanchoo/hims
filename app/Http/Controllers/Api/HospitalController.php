<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class HospitalController extends Controller
{
    public function index(Request $request)
    {
        $query = Hospital::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $query->orderBy('name')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:hospitals,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:general,clinic,opd_center,ipd_center,diagnostic_center',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
            'license_number' => 'nullable|string',
            'license_expiry' => 'nullable|date',
            'tax_id' => 'nullable|string',
            // Global Settings
            'currency' => 'nullable|string|max:5',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|max:5',
            // Subscription
            'subscription_plan' => 'nullable|in:basic,standard,premium',
            'subscription_start' => 'nullable|date',
            'subscription_end' => 'nullable|date',
            'is_active' => 'boolean',
            // Admin user for the hospital
            'admin_username' => 'required|string|unique:users,username',
            'admin_password' => 'required|string|min:6',
            'admin_email' => 'required|email',
            'admin_full_name' => 'required|string',
        ]);

        // Build settings array
        $settings = [
            'currency' => $validated['currency'] ?? 'INR',
            'date_format' => $validated['date_format'] ?? 'DD/MM/YYYY',
            'time_format' => $validated['time_format'] ?? '12h',
        ];

        // Create hospital
        $hospital = Hospital::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? 'India',
            'pincode' => $validated['pincode'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'license_number' => $validated['license_number'] ?? null,
            'license_expiry' => $validated['license_expiry'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'settings' => $settings,
            'subscription_plan' => $validated['subscription_plan'] ?? 'basic',
            'subscription_start' => $validated['subscription_start'] ?? now(),
            'subscription_end' => $validated['subscription_end'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Create admin user for the hospital
        $admin = User::withoutGlobalScopes()->create([
            'hospital_id' => $hospital->hospital_id,
            'username' => $validated['admin_username'],
            'password' => Hash::make($validated['admin_password']),
            'full_name' => $validated['admin_full_name'],
            'email' => $validated['admin_email'],
            'role' => 'admin',
            'is_active' => true,
            'is_super_admin' => false,
        ]);

        return response()->json([
            'message' => 'Hospital created successfully',
            'hospital' => $hospital,
            'admin' => [
                'user_id' => $admin->user_id,
                'username' => $admin->username,
                'full_name' => $admin->full_name,
            ],
        ], 201);
    }

    public function show(Hospital $hospital)
    {
        $hospital->load(['users' => function ($query) {
            $query->where('role', 'admin')->select('user_id', 'hospital_id', 'username', 'full_name', 'email');
        }]);

        // Add statistics
        $hospital->stats = [
            'total_users' => $hospital->users()->count(),
            'total_patients' => $hospital->patients()->count(),
            'total_doctors' => $hospital->doctors()->count(),
            'total_departments' => $hospital->departments()->count(),
        ];

        return response()->json($hospital);
    }

    public function update(Request $request, Hospital $hospital)
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:20|unique:hospitals,code,' . $hospital->hospital_id . ',hospital_id',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:general,clinic,opd_center,ipd_center,diagnostic_center',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
            'license_number' => 'nullable|string',
            'license_expiry' => 'nullable|date',
            'tax_id' => 'nullable|string',
            // Global Settings
            'currency' => 'nullable|string|max:5',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|max:5',
            // Subscription
            'subscription_plan' => 'nullable|in:basic,standard,premium',
            'subscription_start' => 'nullable|date',
            'subscription_end' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        // Update settings if provided
        if ($request->has('currency') || $request->has('date_format') || $request->has('time_format')) {
            $currentSettings = $hospital->settings ?? [];
            $settings = array_merge($currentSettings, [
                'currency' => $validated['currency'] ?? $currentSettings['currency'] ?? 'INR',
                'date_format' => $validated['date_format'] ?? $currentSettings['date_format'] ?? 'DD/MM/YYYY',
                'time_format' => $validated['time_format'] ?? $currentSettings['time_format'] ?? '12h',
            ]);
            $validated['settings'] = $settings;
        }

        // Remove settings fields from validated array (they're now in settings)
        unset($validated['currency'], $validated['date_format'], $validated['time_format']);

        $hospital->update($validated);

        return response()->json([
            'message' => 'Hospital updated successfully',
            'hospital' => $hospital,
        ]);
    }

    public function destroy(Hospital $hospital)
    {
        // Soft delete or deactivate - don't actually delete
        $hospital->update(['is_active' => false]);

        return response()->json([
            'message' => 'Hospital deactivated successfully',
        ]);
    }

    public function stats()
    {
        return response()->json([
            'total_hospitals' => Hospital::count(),
            'active_hospitals' => Hospital::where('is_active', true)->count(),
            'by_type' => Hospital::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->get(),
            'by_plan' => Hospital::selectRaw('subscription_plan, count(*) as count')
                ->groupBy('subscription_plan')
                ->get(),
            'expiring_soon' => Hospital::where('subscription_end', '<=', now()->addDays(30))
                ->where('subscription_end', '>', now())
                ->count(),
        ]);
    }
}
