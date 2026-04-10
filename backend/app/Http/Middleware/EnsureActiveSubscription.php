<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. If not logged in or doesn't have a supplier (like a clean superadmin user later), skip
        if (!$user || $user->role === 'superadmin') {
            return $next($request);
        }

        // 2. Resolve Supplier
        $supplier = $user->supplier;

        if (!$supplier) {
            return $next($request); // Should not happen for owner/admin/etc
        }

        // 3. Check Manual Deactivation by Superadmin
        if (!$supplier->is_active) {
            return response()->json([
                'message' => 'Akun Toko/Supplier Anda dinonaktifkan oleh administrator sistem.',
                'code' => 'ACCOUNT_DISABLED'
            ], 403);
        }

        // 4. Check Expiration
        if ($supplier->valid_until && now()->isAfter($supplier->valid_until)) {
            return response()->json([
                'message' => 'Masa langganan Anda telah berakhir. Silakan lakukan perpanjangan.',
                'code' => 'SUBSCRIPTION_EXPIRED',
                'valid_until' => $supplier->valid_until
            ], 403);
        }

        return $next($request);
    }
}
