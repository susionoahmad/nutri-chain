<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Storage;

class BillingController extends Controller
{
    /**
     * Get all available subscription plans.
     */
    public function plans()
    {
        return response()->json(SubscriptionPlan::all());
    }

    /**
     * Get the current active or pending subscription for the supplier.
     */
    public function current(Request $request)
    {
        $supplier = $request->user()->supplier;

        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $subscription = Subscription::with('plan')
            ->where('supplier_id', $supplier->id)
            ->whereIn('status', ['active', 'pending'])
            ->latest()
            ->first();

        return response()->json($subscription);
    }

    /**
     * Submit a subscription request with payment proof.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_proof' => 'required|image|max:5120', // Max 5MB
        ]);

        $supplier = $request->user()->supplier;

        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        // Upload payment proof
        $path = $request->file('payment_proof')->store('payment_proofs', 'public');

        // Create subscription entry
        $subscription = Subscription::create([
            'supplier_id' => $supplier->id,
            'plan_id' => $request->plan_id,
            'status' => 'pending',
            'payment_proof' => Storage::url($path),
        ]);

        return response()->json([
            'message' => 'Bukti pembayaran berhasil diunggah. Mohon tunggu verifikasi dari admin.',
            'subscription' => $subscription
        ]);
    }
}
