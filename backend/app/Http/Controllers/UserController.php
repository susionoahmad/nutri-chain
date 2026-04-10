<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return User::with('customer')
            ->where('supplier_id', $request->user()->supplier_id)
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,warehouse,driver,customer,owner',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $supplier = $request->user()->supplier;

        if (!$supplier || !$supplier->canAddUser()) {
            return response()->json([
                'message' => 'Anda telah mencapai batas maksimal jumlah user untuk paket langganan saat ini.',
                'code' => 'LIMIT_REACHED'
            ], 403);
        }

        $validated['supplier_id'] = $supplier->id;
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json($user->load('customer'), 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        // Pastikan hanya bisa update user di supplier yang sama
        if ($user->supplier_id !== $request->user()->supplier_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,warehouse,driver,customer,owner',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json($user->fresh()->load('customer'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->supplier_id !== $request->user()->supplier_id) {
            abort(403);
        }
        $user->delete();
        return response()->json(null, 204);
    }
}
