<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwner
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->role === 'owner') {
            return $next($request);
        }

        return response()->json(['message' => 'Akses ditolak. Hanya Owner yang dapat mengakses atau mengubah pengaturan supplier.'], 403);
    }
}