<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Supplier;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaasController extends Controller
{
    public function dashboardStats()
    {
        return response()->json([
            'total_tenants' => Supplier::count(),
            'active_tenants' => Supplier::where('is_active', true)->where('valid_until', '>=', now())->count(),
            'expired_tenants' => Supplier::where('valid_until', '<', now())->count(),
            'pending_payments' => Subscription::where('status', 'pending')->count(),
            'total_revenue' => Subscription::where('status', 'active')->with('plan')->get()->sum(function($s) {
                return (float) $s->plan->price;
            }),
        ]);
    }

    public function tenants()
    {
        return response()->json(Supplier::withCount('users')->latest()->get());
    }

    public function updateTenantStatus(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $supplier->update($validated);

        return response()->json(['message' => 'Status tenant berhasil diperbarui.', 'supplier' => $supplier]);
    }

    public function pendingSubscriptions()
    {
        return response()->json(Subscription::with(['supplier', 'plan'])
            ->where('status', 'pending')
            ->latest()
            ->get());
    }

    public function verifySubscription(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        return DB::transaction(function () use ($subscription, $validated) {
            if ($validated['action'] === 'approve') {
                $plan = $subscription->plan;
                
                // 1. Mark subscription as active
                $subscription->update([
                    'status' => 'active',
                    'start_date' => now(),
                    'end_date' => now()->addDays($plan->duration_days),
                ]);

                // 2. Sync to Supplier cache
                $subscription->supplier->update([
                    'is_active' => true,
                    'valid_until' => $subscription->end_date,
                ]);

                return response()->json(['message' => 'Pembayaran dikonfirmasi. Langganan telah diperpanjang.']);
            } else {
                $subscription->update(['status' => 'void']);
                return response()->json(['message' => 'Pembayaran ditolak.']);
            }
        });
    }
}
