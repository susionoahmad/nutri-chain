<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;

class AuthController extends Controller
{
    /**
     * Pendaftaran Khusus Supplier (Owner Baru)
     * Diakses dari menu utama/login.
     */
    public function registerSupplier(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Generate Unique Supplier Code
            do {
                $code = strtoupper(Str::random(8));
            } while (Supplier::where('code', $code)->exists());

            // 2. Create Supplier
            $supplier = Supplier::create([
                'name' => $request->company_name,
                'code' => $code,
                'contact_person' => $request->name,
                'email' => $request->email,
            ]);

            // 3. Create User (Owner)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'owner',
                'supplier_id' => $supplier->id,
            ]);

            // 4. SAAS: Setup 7 Days Trial
            $trialPlan = SubscriptionPlan::where('name', 'Trial')->first();
            
            // If Trial plan doesn't exist, create it on the fly or ensure it has correct values
            if (!$trialPlan) {
                $trialPlan = SubscriptionPlan::create([
                    'name' => 'Trial',
                    'price' => 0,
                    'duration_days' => 7,
                    'max_users' => 4,
                    'max_customers' => 3,
                    'features' => []
                ]);
            }

            $validUntil = now()->addDays($trialPlan->duration_days);
            
            // Update Supplier valid_until cache
            $supplier->update(['valid_until' => $validUntil]);

            // Create initial active subscription record
            Subscription::create([
                'supplier_id' => $supplier->id,
                'plan_id' => $trialPlan->id,
                'start_date' => now(),
                'end_date' => $validUntil,
                'status' => 'active',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load('supplier')
            ], 201);
        });
    }

    /**
     * Pendaftaran Khusus Pelanggan (Customer)
     * Diakses melalui link undangan dari supplier.
     */
    public function registerCustomer(Request $request)
    {
        $request->validate([
            'supplier_code' => 'required|string|exists:suppliers,code',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'store_name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
        ]);

        return DB::transaction(function () use ($request) {
            $supplier = Supplier::where('code', $request->supplier_code)->firstOrFail();

            // 1. Create Customer Profile
            $customer = Customer::create([
                'supplier_id' => $supplier->id,
                'name' => $request->store_name,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
            ]);

            // 2. Create User Account (Customer Role)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'customer',
                'supplier_id' => $supplier->id,
                'customer_id' => $customer->id,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load('supplier', 'customer')
            ], 201);
        });
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Mencegah Brute Force: Kunci limitasi berdasarkan Email & IP Pengguna
        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        // Jika telah gagal lebih dari 5 kali, maka tolak permintaan
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => ["Terlalu banyak percobaan log in yang gagal. Coba lagi dalam {$seconds} detik."],
            ]);
        }

        $user = User::with('supplier', 'customer')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            // Catat kegagalan. (Timer blokir aktif 60 detik)
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => ['Alamat Email atau Kata Sandi yang dimasukkan salah.'],
            ]);
        }

        // Jika login sukses, bersihkan hitung kegagalan log in
        RateLimiter::clear($throttleKey);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('supplier', 'customer'));
    }
}
