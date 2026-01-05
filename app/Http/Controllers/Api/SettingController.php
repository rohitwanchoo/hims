<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(Setting::all());
    }

    public function store(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            Setting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        return response()->json(['message' => 'Settings saved successfully']);
    }

    public function show(string $key)
    {
        $setting = Setting::where('setting_key', $key)->first();
        return response()->json($setting);
    }

    public function update(Request $request, string $key)
    {
        $setting = Setting::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $request->value]
        );

        return response()->json($setting);
    }

    public function destroy(string $key)
    {
        Setting::where('setting_key', $key)->delete();
        return response()->json(['message' => 'Setting deleted']);
    }
}
