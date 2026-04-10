<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OnboardingController extends Controller
{
    public function complete(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'owner') {
            return response()->json(['message' => 'Hanya owner yang bisa menyelesaikan onboarding.'], 403);
        }

        $supplier = $user->supplier;

        $validated = $request->validate([
            // Step 1: Profile
            'supplier_address' => 'nullable|string',
            'supplier_phone' => 'nullable|string|max:20',
            
            // Step 2: Team Members
            'team' => 'nullable|array',
            'team.*.name' => 'required|string|max:255',
            'team.*.email' => 'required|email|unique:users,email',
            'team.*.password' => 'required|string|min:6',
            'team.*.role' => 'required|in:admin,warehouse,driver',

            // Step 3: First Product (Optional)
            'first_product' => 'nullable|array',
            'first_product.name' => 'required_with:first_product|string|max:255',
            'first_product.category' => 'required_with:first_product|string|max:255',
            'first_product.unit' => 'required_with:first_product|string|max:50',
            'first_product.cost_price' => 'required_with:first_product|numeric|min:0',
            'first_product.price' => 'required_with:first_product|numeric|min:0',
        ]);

        return DB::transaction(function () use ($supplier, $validated) {
            // 1. Update Supplier Profile
            $supplier->update([
                'address' => $validated['supplier_address'] ?? $supplier->address,
                'phone' => $validated['supplier_phone'] ?? $supplier->phone,
            ]);

            // 2. Batch Create Team Members
            if (isset($validated['team']) && count($validated['team']) > 0) {
                foreach ($validated['team'] as $staff) {
                    // Safety check for limits (Max 4 users total including owner)
                    if ($supplier->users()->count() < 4) {
                        User::create([
                            'supplier_id' => $supplier->id,
                            'name' => $staff['name'],
                            'email' => $staff['email'],
                            'password' => Hash::make($staff['password']),
                            'role' => $staff['role'],
                        ]);
                    }
                }
            }

            // 3. Create First Product
            if (isset($validated['first_product'])) {
                Product::create(array_merge($validated['first_product'], [
                    'supplier_id' => $supplier->id
                ]));
            }

            // 4. Mark Onboarding as Complete
            $supplier->update(['is_onboarded' => true]);

            return response()->json([
                'message' => 'Onboarding berhasil diselesaikan.',
                'user' => $supplier->users()->first()->load('supplier') // Reload owner with update supplier status
            ]);
        });
    }
}
