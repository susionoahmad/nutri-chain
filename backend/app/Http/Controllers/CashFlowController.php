<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashFlowController extends Controller
{
    public function index(Request $request)
    {
        $supplierId = $request->user()->supplier_id;
        $role = $request->user()->role;
        $query = CashFlow::where('supplier_id', $supplierId);

        // Optional filtering by account type
        if ($request->has('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        $history = $query->latest()->get();

        // Calculate Balances
        $tunaiBalance = CashFlow::where('supplier_id', $supplierId)
            ->where('account_type', 'cash')
            ->sum(DB::raw("CASE WHEN type = 'in' THEN amount ELSE -amount END"));

        $bankBalance = CashFlow::where('supplier_id', $supplierId)
            ->where('account_type', 'bank')
            ->sum(DB::raw("CASE WHEN type = 'in' THEN amount ELSE -amount END"));

        // Roles Restrictions
        $summary = [
            'cash' => (float) $tunaiBalance,
            'bank' => $role === 'owner' ? (float) $bankBalance : 0,
            'total' => $role === 'owner' ? (float) ($tunaiBalance + $bankBalance) : (float) $tunaiBalance,
        ];

        // If Admin, filter history to only show cash transactions for privacy
        if ($role === 'admin') {
            $history = $history->where('account_type', 'cash')->values();
        }

        return response()->json([
            'history' => $history,
            'summary' => $summary
        ]);
    }

    public function setInitialBalance(Request $request)
    {
        if ($request->user()->role !== 'owner') {
            abort(403);
        }

        $request->validate([
            'cash_amount' => 'required|numeric|min:0',
            'bank_amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
        ]);

        return DB::transaction(function () use ($request) {
            $supplierId = $request->user()->supplier_id;

            // Delete old initial balances if any to avoid duplicates
            CashFlow::where('supplier_id', $supplierId)
                ->where('category', 'initial_balance')
                ->delete();

            // Create Cash initial
            if ($request->cash_amount > 0) {
                CashFlow::create([
                    'supplier_id' => $supplierId,
                    'type' => 'in',
                    'category' => 'initial_balance',
                    'amount' => $request->cash_amount,
                    'account_type' => 'cash',
                    'note' => 'Saldo Awal Tunai',
                    'transaction_date' => $request->transaction_date,
                ]);
            }

            // Create Bank initial
            if ($request->bank_amount > 0) {
                CashFlow::create([
                    'supplier_id' => $supplierId,
                    'type' => 'in',
                    'category' => 'initial_balance',
                    'amount' => $request->bank_amount,
                    'account_type' => 'bank',
                    'note' => 'Saldo Awal Bank',
                    'transaction_date' => $request->transaction_date,
                ]);
            }

            return response()->json(['message' => 'Saldo awal berhasil diatur.']);
        });
    }

    public function storeManual(Request $request)
    {
        $role = $request->user()->role;
        // Permintaan: Admin boleh menginput biaya operasional
        if (!in_array($role, ['owner', 'admin'])) {
             abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'category' => 'required|in:expense,adjustment,initial_balance',
            'amount' => 'required|numeric|min:0',
            'account_type' => 'required|in:cash,bank',
            'note' => 'required|string',
            'transaction_date' => 'required|date',
        ]);

        // Proteksi Tambahan: Admin DIPAKSA menggunakan akun Cash
        if ($role === 'admin' && $validated['account_type'] !== 'cash') {
            return response()->json(['message' => 'Admin hanya diizinkan menginput Kas Tunai.'], 403);
        }

        $validated['supplier_id'] = $request->user()->supplier_id;

        $cashFlow = CashFlow::create($validated);

        return response()->json($cashFlow, 201);
    }
}
