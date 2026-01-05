<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthPackage;
use App\Models\HealthPackageService;
use App\Models\Service;
use Illuminate\Http\Request;

class HealthPackageController extends Controller
{
    /**
     * Display a listing of health packages
     */
    public function index(Request $request)
    {
        $query = HealthPackage::with('services.service');

        if ($request->search) {
            $query->where('package_name', 'like', "%{$request->search}%");
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->gender) {
            $query->where(function ($q) use ($request) {
                $q->where('gender', $request->gender)
                  ->orWhere('gender', 'all');
            });
        }

        if ($request->min_age !== null || $request->max_age !== null) {
            $query->where(function ($q) use ($request) {
                if ($request->min_age !== null) {
                    $q->where(function ($q2) use ($request) {
                        $q2->whereNull('min_age')
                           ->orWhere('min_age', '<=', $request->min_age);
                    });
                }
                if ($request->max_age !== null) {
                    $q->where(function ($q2) use ($request) {
                        $q2->whereNull('max_age')
                           ->orWhere('max_age', '>=', $request->max_age);
                    });
                }
            });
        }

        $packages = $query->orderBy('package_name')->get();

        return response()->json($packages);
    }

    /**
     * Store a newly created health package
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_name' => 'required|string|max:150',
            'package_code' => 'nullable|string|max:20|unique:health_packages,package_code',
            'description' => 'nullable|string',
            'package_rate' => 'required|numeric|min:0',
            'gender' => 'nullable|in:male,female,all',
            'min_age' => 'nullable|integer|min:0',
            'max_age' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            // Services
            'services' => 'nullable|array',
            'services.*.service_id' => 'required|exists:services,service_id',
            'services.*.quantity' => 'nullable|integer|min:1',
            'services.*.is_mandatory' => 'boolean',
        ]);

        $package = HealthPackage::create([
            'package_name' => $validated['package_name'],
            'package_code' => $validated['package_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'package_rate' => $validated['package_rate'],
            'gender' => $validated['gender'] ?? 'all',
            'min_age' => $validated['min_age'] ?? null,
            'max_age' => $validated['max_age'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Add services if provided
        if (!empty($validated['services'])) {
            foreach ($validated['services'] as $serviceData) {
                HealthPackageService::create([
                    'package_id' => $package->package_id,
                    'service_id' => $serviceData['service_id'],
                    'quantity' => $serviceData['quantity'] ?? 1,
                    'is_mandatory' => $serviceData['is_mandatory'] ?? true,
                ]);
            }
        }

        return response()->json($package->load('services.service'), 201);
    }

    /**
     * Display the specified health package
     */
    public function show(string $id)
    {
        $package = HealthPackage::with('services.service')->findOrFail($id);

        // Calculate total individual cost vs package rate
        $individualCost = $package->services->sum(function ($ps) {
            return ($ps->service->rate ?? 0) * $ps->quantity;
        });

        return response()->json([
            'package' => $package,
            'individual_cost' => $individualCost,
            'savings' => $individualCost - $package->package_rate,
        ]);
    }

    /**
     * Update the specified health package
     */
    public function update(Request $request, string $id)
    {
        $package = HealthPackage::findOrFail($id);

        $validated = $request->validate([
            'package_name' => 'string|max:150',
            'package_code' => 'nullable|string|max:20|unique:health_packages,package_code,' . $id . ',package_id',
            'description' => 'nullable|string',
            'package_rate' => 'numeric|min:0',
            'gender' => 'nullable|in:male,female,all',
            'min_age' => 'nullable|integer|min:0',
            'max_age' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $package->update($validated);

        return response()->json($package->load('services.service'));
    }

    /**
     * Remove the specified health package
     */
    public function destroy(string $id)
    {
        $package = HealthPackage::findOrFail($id);

        // Delete associated services
        $package->services()->delete();
        $package->delete();

        return response()->json(['message' => 'Health package deleted successfully']);
    }

    /**
     * Add service to health package
     */
    public function addService(Request $request, string $id)
    {
        $package = HealthPackage::findOrFail($id);

        $validated = $request->validate([
            'service_id' => 'required|exists:services,service_id',
            'quantity' => 'nullable|integer|min:1',
            'is_mandatory' => 'boolean',
        ]);

        // Check if service already exists
        $existing = HealthPackageService::where('package_id', $package->package_id)
            ->where('service_id', $validated['service_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Service already exists in package',
            ], 422);
        }

        $packageService = HealthPackageService::create([
            'package_id' => $package->package_id,
            'service_id' => $validated['service_id'],
            'quantity' => $validated['quantity'] ?? 1,
            'is_mandatory' => $validated['is_mandatory'] ?? true,
        ]);

        return response()->json($packageService->load('service'), 201);
    }

    /**
     * Remove service from health package
     */
    public function removeService(string $packageId, string $serviceId)
    {
        $package = HealthPackage::findOrFail($packageId);

        $packageService = HealthPackageService::where('package_id', $package->package_id)
            ->where('service_id', $serviceId)
            ->firstOrFail();

        $packageService->delete();

        return response()->json(['message' => 'Service removed from package']);
    }
}
