<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InsuranceCompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = InsuranceCompany::withCount('patients as usage_count');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('company_code', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('company_name')->get();
    }

    public function active()
    {
        $companies = InsuranceCompany::where('is_active', true)
            ->orderBy('company_name')
            ->get(['insurance_id', 'company_name', 'company_code']);

        return response()->json($companies);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:150',
            'company_code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:100',
            'mobile' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:200',
            'is_active' => 'boolean'
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $item = InsuranceCompany::create($validated);

        return response()->json($item, 201);
    }

    public function show(InsuranceCompany $insuranceCompany)
    {
        return $insuranceCompany;
    }

    public function update(Request $request, InsuranceCompany $insuranceCompany)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:150',
            'company_code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:100',
            'mobile' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:200',
            'is_active' => 'boolean'
        ]);

        $insuranceCompany->update($validated);

        return response()->json($insuranceCompany);
    }

    public function destroy(InsuranceCompany $insuranceCompany)
    {
        $insuranceCompany->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
