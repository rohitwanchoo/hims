<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferenceDoctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferenceDoctorController extends Controller
{
    public function index(Request $request)
    {
        $query = ReferenceDoctor::with(['gender', 'bloodGroup', 'qualificationMaster', 'department'])
            ->withCount('patients as usage_count');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('clinic_name', 'like', "%{$search}%")
                  ->orWhere('specialization', 'like', "%{$search}%")
                  ->orWhere('registration_no', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('first_name')->get();
    }

    public function active()
    {
        return ReferenceDoctor::where('is_active', true)
            ->orderBy('first_name')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prefix_id' => 'nullable|integer|exists:prefixes,prefix_id',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'gender_id' => 'nullable|integer|exists:genders,gender_id',
            'blood_group_id' => 'nullable|integer|exists:blood_groups,blood_group_id',
            'qualification_id' => 'nullable|integer|exists:qualifications,qualification_id',
            'department_id' => 'nullable|integer|exists:departments,department_id',
            'qualification' => 'nullable|string|max:100',
            'skill_set' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:100',
            'registration_no' => 'nullable|string|max:50',
            'practice_no' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'hospital_name' => 'nullable|string|max:150',
            'group_name' => 'nullable|string|max:100',
            'clinic_name' => 'nullable|string|max:150',
            'mobile' => 'nullable|string|max:15',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            // Residence Address
            'res_address_line1' => 'nullable|string|max:200',
            'res_address_line2' => 'nullable|string|max:200',
            'res_country_id' => 'nullable|integer|exists:countries,country_id',
            'res_state_id' => 'nullable|integer|exists:states,state_id',
            'res_district_id' => 'nullable|integer|exists:districts,district_id',
            'res_city_id' => 'nullable|integer|exists:cities,city_id',
            'res_pincode' => 'nullable|string|max:10',
            'res_fax' => 'nullable|string|max:20',
            'res_tel1' => 'nullable|string|max:20',
            'res_tel2' => 'nullable|string|max:20',
            'res_mobile' => 'nullable|string|max:15',
            'res_email' => 'nullable|email|max:100',
            'res_website' => 'nullable|string|max:200',
            // Clinic Address
            'clinic_address_line1' => 'nullable|string|max:200',
            'clinic_address_line2' => 'nullable|string|max:200',
            'clinic_country_id' => 'nullable|integer|exists:countries,country_id',
            'clinic_state_id' => 'nullable|integer|exists:states,state_id',
            'clinic_district_id' => 'nullable|integer|exists:districts,district_id',
            'clinic_city_id' => 'nullable|integer|exists:cities,city_id',
            'clinic_pincode' => 'nullable|string|max:10',
            'clinic_fax' => 'nullable|string|max:20',
            'clinic_tel1' => 'nullable|string|max:20',
            'clinic_tel2' => 'nullable|string|max:20',
            'clinic_mobile' => 'nullable|string|max:15',
            'clinic_email' => 'nullable|email|max:100',
            'clinic_website' => 'nullable|string|max:200',
            'is_active' => 'boolean'
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        // Build full_name from parts
        $nameParts = array_filter([
            $validated['first_name'] ?? '',
            $validated['middle_name'] ?? '',
            $validated['last_name'] ?? ''
        ]);
        $validated['full_name'] = implode(' ', $nameParts);

        $item = ReferenceDoctor::create($validated);

        return response()->json($item->load(['gender', 'bloodGroup', 'qualificationMaster', 'department']), 201);
    }

    public function show(ReferenceDoctor $referenceDoctor)
    {
        return $referenceDoctor->load([
            'gender', 'bloodGroup', 'qualificationMaster', 'department',
            'resCountry', 'resState', 'resDistrict', 'resCity',
            'clinicCountry', 'clinicState', 'clinicDistrict', 'clinicCity'
        ]);
    }

    public function update(Request $request, ReferenceDoctor $referenceDoctor)
    {
        $validated = $request->validate([
            'prefix_id' => 'nullable|integer|exists:prefixes,prefix_id',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'gender_id' => 'nullable|integer|exists:genders,gender_id',
            'blood_group_id' => 'nullable|integer|exists:blood_groups,blood_group_id',
            'qualification_id' => 'nullable|integer|exists:qualifications,qualification_id',
            'department_id' => 'nullable|integer|exists:departments,department_id',
            'qualification' => 'nullable|string|max:100',
            'skill_set' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:100',
            'registration_no' => 'nullable|string|max:50',
            'practice_no' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'hospital_name' => 'nullable|string|max:150',
            'group_name' => 'nullable|string|max:100',
            'clinic_name' => 'nullable|string|max:150',
            'mobile' => 'nullable|string|max:15',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            // Residence Address
            'res_address_line1' => 'nullable|string|max:200',
            'res_address_line2' => 'nullable|string|max:200',
            'res_country_id' => 'nullable|integer|exists:countries,country_id',
            'res_state_id' => 'nullable|integer|exists:states,state_id',
            'res_district_id' => 'nullable|integer|exists:districts,district_id',
            'res_city_id' => 'nullable|integer|exists:cities,city_id',
            'res_pincode' => 'nullable|string|max:10',
            'res_fax' => 'nullable|string|max:20',
            'res_tel1' => 'nullable|string|max:20',
            'res_tel2' => 'nullable|string|max:20',
            'res_mobile' => 'nullable|string|max:15',
            'res_email' => 'nullable|email|max:100',
            'res_website' => 'nullable|string|max:200',
            // Clinic Address
            'clinic_address_line1' => 'nullable|string|max:200',
            'clinic_address_line2' => 'nullable|string|max:200',
            'clinic_country_id' => 'nullable|integer|exists:countries,country_id',
            'clinic_state_id' => 'nullable|integer|exists:states,state_id',
            'clinic_district_id' => 'nullable|integer|exists:districts,district_id',
            'clinic_city_id' => 'nullable|integer|exists:cities,city_id',
            'clinic_pincode' => 'nullable|string|max:10',
            'clinic_fax' => 'nullable|string|max:20',
            'clinic_tel1' => 'nullable|string|max:20',
            'clinic_tel2' => 'nullable|string|max:20',
            'clinic_mobile' => 'nullable|string|max:15',
            'clinic_email' => 'nullable|email|max:100',
            'clinic_website' => 'nullable|string|max:200',
            'is_active' => 'boolean'
        ]);

        // Build full_name from parts
        $nameParts = array_filter([
            $validated['first_name'] ?? '',
            $validated['middle_name'] ?? '',
            $validated['last_name'] ?? ''
        ]);
        $validated['full_name'] = implode(' ', $nameParts);

        $referenceDoctor->update($validated);

        return response()->json($referenceDoctor->load(['gender', 'bloodGroup', 'qualificationMaster', 'department']));
    }

    public function destroy(ReferenceDoctor $referenceDoctor)
    {
        $referenceDoctor->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
