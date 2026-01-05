<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::with('department')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|unique:users',
            'full_name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,doctor,nurse,receptionist,lab_technician,pharmacist,accountant',
            'department_id' => 'nullable|exists:departments,department_id',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function show(string $id)
    {
        $user = User::with('department')->findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username' => 'required|unique:users,username,' . $id,
            'full_name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:admin,doctor,nurse,receptionist,lab_technician,pharmacist,accountant',
            'department_id' => 'nullable|exists:departments,department_id',
            'is_active' => 'boolean',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);
        return response()->json($user);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'Cannot delete the only admin user'], 400);
            }
        }

        $user->update(['is_active' => false]);
        return response()->json(['message' => 'User deactivated']);
    }
}
