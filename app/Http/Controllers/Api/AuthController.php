<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Super admin users don't have hospital scope
        $user = User::withoutGlobalScopes()->where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'username' => ['Your account has been deactivated.'],
            ]);
        }

        // Check hospital subscription for non-super-admin users
        if (!$user->isSuperAdmin() && $user->hospital_id) {
            $hospital = Hospital::find($user->hospital_id);
            if ($hospital && !$hospital->is_active) {
                throw ValidationException::withMessages([
                    'username' => ['Your hospital account has been deactivated.'],
                ]);
            }
            if ($hospital && !$hospital->isSubscriptionActive()) {
                throw ValidationException::withMessages([
                    'username' => ['Your hospital subscription has expired.'],
                ]);
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $response = [
            'user' => [
                'user_id' => $user->user_id,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role,
                'department_id' => $user->department_id,
                'hospital_id' => $user->hospital_id,
                'is_super_admin' => $user->isSuperAdmin(),
            ],
            'token' => $token,
        ];

        // Include hospital info for non-super-admin users
        if ($user->hospital_id) {
            $hospital = Hospital::find($user->hospital_id);
            $response['hospital'] = $hospital ? [
                'hospital_id' => $hospital->hospital_id,
                'code' => $hospital->code,
                'name' => $hospital->name,
                'type' => $hospital->type,
                'logo' => $hospital->logo,
            ] : null;
        }

        // For super admin, include list of hospitals they can switch to
        if ($user->isSuperAdmin()) {
            $response['hospitals'] = Hospital::where('is_active', true)
                ->select('hospital_id', 'code', 'name', 'type')
                ->get();
        }

        return response()->json($response);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        $response = [
            'user_id' => $user->user_id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => $user->role,
            'department_id' => $user->department_id,
            'department' => $user->department,
            'hospital_id' => $user->hospital_id,
            'is_super_admin' => $user->isSuperAdmin(),
        ];

        if ($user->hospital_id) {
            $response['hospital'] = $user->hospital;
        }

        if ($user->isSuperAdmin()) {
            $response['hospitals'] = Hospital::where('is_active', true)
                ->select('hospital_id', 'code', 'name', 'type')
                ->get();
        }

        return response()->json($response);
    }

    public function switchHospital(Request $request)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'hospital_id' => 'required|exists:hospitals,hospital_id',
        ]);

        session(['current_hospital_id' => $request->hospital_id]);

        $hospital = Hospital::find($request->hospital_id);

        return response()->json([
            'message' => 'Hospital switched successfully',
            'hospital' => $hospital,
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }
}
