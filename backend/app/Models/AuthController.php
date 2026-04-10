<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Registrasi khusus untuk Customer/Toko (SPPG)
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            // Supplier_id biasanya didapat dari context (misal: URL /regis/{supplier_slug})
            'supplier_id' => 'required|exists:suppliers,id', 
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            // Data Toko/SPPG
            'store_name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Buat data profil Customer (SPPG)
            $customer = Customer::create([
                'supplier_id' => $validated['supplier_id'],
                'name' => $validated['store_name'],
                'address' => $validated['address'],
                'phone' => $validated['phone'],
            ]);

            // 2. Buat akun User dengan role 'customer'
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'customer',
                'supplier_id' => $validated['supplier_id'],
                'customer_id' => $customer->id,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Registrasi berhasil. Selamat datang di Nutri-Chain.',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load('customer')
            ], 201);
        });
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with(['supplier', 'customer'])->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Kredensial salah.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['access_token' => $token, 'user' => $user]);
    }
}