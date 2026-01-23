<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Countries
        $indiaId = DB::table('countries')->insertGetId([
            'country_name' => 'India',
            'country_code' => 'IN',
            'phone_code' => '+91',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed States for India
        $states = [
            ['name' => 'Maharashtra', 'code' => 'MH'],
            ['name' => 'Gujarat', 'code' => 'GJ'],
            ['name' => 'Karnataka', 'code' => 'KA'],
            ['name' => 'Tamil Nadu', 'code' => 'TN'],
            ['name' => 'Delhi', 'code' => 'DL'],
            ['name' => 'Uttar Pradesh', 'code' => 'UP'],
            ['name' => 'Rajasthan', 'code' => 'RJ'],
            ['name' => 'West Bengal', 'code' => 'WB'],
            ['name' => 'Madhya Pradesh', 'code' => 'MP'],
            ['name' => 'Kerala', 'code' => 'KL'],
        ];

        $stateIds = [];
        foreach ($states as $state) {
            $stateIds[$state['code']] = DB::table('states')->insertGetId([
                'country_id' => $indiaId,
                'state_name' => $state['name'],
                'state_code' => $state['code'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Districts for Maharashtra
        $maharashtraDistricts = [
            ['name' => 'Mumbai', 'code' => 'MUM'],
            ['name' => 'Pune', 'code' => 'PUN'],
            ['name' => 'Nagpur', 'code' => 'NAG'],
            ['name' => 'Thane', 'code' => 'THA'],
            ['name' => 'Nashik', 'code' => 'NAS'],
        ];

        $districtIds = [];
        foreach ($maharashtraDistricts as $district) {
            $districtIds[$district['code']] = DB::table('districts')->insertGetId([
                'state_id' => $stateIds['MH'],
                'district_name' => $district['name'],
                'district_code' => $district['code'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Districts for Gujarat
        $gujaratDistricts = [
            ['name' => 'Ahmedabad', 'code' => 'AHM'],
            ['name' => 'Surat', 'code' => 'SUR'],
            ['name' => 'Vadodara', 'code' => 'VAD'],
            ['name' => 'Rajkot', 'code' => 'RAJ'],
        ];

        foreach ($gujaratDistricts as $district) {
            $districtIds[$district['code']] = DB::table('districts')->insertGetId([
                'state_id' => $stateIds['GJ'],
                'district_name' => $district['name'],
                'district_code' => $district['code'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Cities for Mumbai District
        $mumbaiCities = [
            ['name' => 'Mumbai City', 'code' => 'MBC'],
            ['name' => 'Mumbai Suburban', 'code' => 'MBS'],
        ];

        $cityIds = [];
        foreach ($mumbaiCities as $city) {
            $cityIds[$city['code']] = DB::table('cities')->insertGetId([
                'district_id' => $districtIds['MUM'],
                'city_name' => $city['name'],
                'city_code' => $city['code'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Cities for Pune District
        $puneCities = [
            ['name' => 'Pune City', 'code' => 'PNC'],
            ['name' => 'Pimpri-Chinchwad', 'code' => 'PPC'],
            ['name' => 'Haveli', 'code' => 'HAV'],
        ];

        foreach ($puneCities as $city) {
            $cityIds[$city['code']] = DB::table('cities')->insertGetId([
                'district_id' => $districtIds['PUN'],
                'city_name' => $city['name'],
                'city_code' => $city['code'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Cities for Ahmedabad District
        $ahmedabadCities = [
            ['name' => 'Ahmedabad City', 'code' => 'AHC'],
            ['name' => 'Daskroi', 'code' => 'DAS'],
        ];

        foreach ($ahmedabadCities as $city) {
            $cityIds[$city['code']] = DB::table('cities')->insertGetId([
                'district_id' => $districtIds['AHM'],
                'city_name' => $city['name'],
                'city_code' => $city['code'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Areas for Mumbai City
        $mumbaiAreas = [
            ['name' => 'Colaba', 'pincode' => '400005'],
            ['name' => 'Fort', 'pincode' => '400001'],
            ['name' => 'Marine Lines', 'pincode' => '400002'],
            ['name' => 'Churchgate', 'pincode' => '400020'],
        ];

        foreach ($mumbaiAreas as $area) {
            DB::table('areas')->insert([
                'city_id' => $cityIds['MBC'],
                'area_name' => $area['name'],
                'pincode' => $area['pincode'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Areas for Mumbai Suburban
        $suburbanAreas = [
            ['name' => 'Andheri', 'pincode' => '400053'],
            ['name' => 'Bandra', 'pincode' => '400050'],
            ['name' => 'Borivali', 'pincode' => '400066'],
            ['name' => 'Malad', 'pincode' => '400064'],
            ['name' => 'Goregaon', 'pincode' => '400062'],
        ];

        foreach ($suburbanAreas as $area) {
            DB::table('areas')->insert([
                'city_id' => $cityIds['MBS'],
                'area_name' => $area['name'],
                'pincode' => $area['pincode'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Areas for Pune City
        $puneAreas = [
            ['name' => 'Shivajinagar', 'pincode' => '411005'],
            ['name' => 'Deccan Gymkhana', 'pincode' => '411004'],
            ['name' => 'Koregaon Park', 'pincode' => '411001'],
            ['name' => 'Camp', 'pincode' => '411001'],
            ['name' => 'Kothrud', 'pincode' => '411038'],
        ];

        foreach ($puneAreas as $area) {
            DB::table('areas')->insert([
                'city_id' => $cityIds['PNC'],
                'area_name' => $area['name'],
                'pincode' => $area['pincode'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Areas for Ahmedabad City
        $ahmedabadAreas = [
            ['name' => 'Navrangpura', 'pincode' => '380009'],
            ['name' => 'Satellite', 'pincode' => '380015'],
            ['name' => 'Bodakdev', 'pincode' => '380054'],
            ['name' => 'Maninagar', 'pincode' => '380008'],
        ];

        foreach ($ahmedabadAreas as $area) {
            DB::table('areas')->insert([
                'city_id' => $cityIds['AHC'],
                'area_name' => $area['name'],
                'pincode' => $area['pincode'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
