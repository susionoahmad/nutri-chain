<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\CashFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        return Purchase::with(['producer', 'items.product'])
            ->where('supplier_id', $request->user()->supplier_id)
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'producer_id' => 'required|exists:producers,id',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $purchase = Purchase::create([
                'supplier_id' => $request->user()->supplier_id,
                'producer_id' => $validated['producer_id'],
                'purchase_number' => 'PUR-' . strtoupper(Str::random(8)),
                'purchase_date' => $validated['purchase_date'],
                'total_amount' => 0,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            $total = 0;
            foreach ($validated['items'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'cost_price' => $item['cost_price'],
                ]);
                $total += ($item['qty'] * $item['cost_price']);
            }

            $purchase->update(['total_amount' => $total]);

            return response()->json($purchase->load('items'), 201);
        });
    }

    public function complete(Request $request, Purchase $purchase)
    {
        if ($purchase->status !== 'pending') {
            return response()->json(['message' => 'Hanya transaksi pending yang bisa diselesaikan.'], 400);
        }

        return DB::transaction(function () use ($purchase) {
            // Increment Stok & Log Mutation
            foreach ($purchase->items as $item) {
                $stock = \App\Models\Stock::firstOrCreate(
                    ['product_id' => $item->product_id],
                    ['qty' => 0]
                );
                
                $oldQty = $stock->qty;
                $stock->increment('qty', $item->qty);
                $newQty = $stock->qty;

                // RECORD MUTATION
                \App\Models\StockMutation::create([
                    'supplier_id' => $purchase->supplier_id,
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'qty' => $item->qty,
                    'old_qty' => $oldQty,
                    'new_qty' => $newQty,
                    'reference_type' => 'Purchase',
                    'reference_id' => $purchase->id,
                    'note' => "Pembelian dari Produsen: " . $purchase->producer->name,
                    'transaction_date' => now(),
                ]);
            }

            $purchase->update(['status' => 'completed']);

            return response()->json($purchase->load('items.product', 'producer'));
        });
    }

    /**
     * Pelunasan Hutang Pembelian
     */
    public function pay(Request $request, Purchase $purchase)
    {
        if ($purchase->payment_status === 'paid') {
            return response()->json(['message' => 'Transaksi ini sudah lunas.'], 422);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,bank',
            'payment_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $currentBalance = \App\Models\CashFlow::where('supplier_id', $purchase->supplier_id)
            ->where('account_type', $validated['payment_method'])
            ->sum(DB::raw("CASE WHEN type = 'in' THEN amount ELSE -amount END"));

        if ($currentBalance < $purchase->total_amount) {
            return response()->json([
                'message' => 'Saldo ' . ($validated['payment_method'] === 'cash' ? 'Tunai' : 'Bank') . ' tidak mencukupi untuk pelunasan.'
            ], 400);
        }

        return DB::transaction(function () use ($request, $purchase, $validated) {
            $purchase->update([
                'payment_status' => 'paid',
                'payment_method' => $validated['payment_method'],
            ]);

            // LOG CASH FLOW OUT
            \App\Models\CashFlow::create([
                'supplier_id' => $purchase->supplier_id,
                'type' => 'out',
                'category' => 'purchase',
                'amount' => $purchase->total_amount,
                'account_type' => $validated['payment_method'],
                'reference_type' => 'Purchase',
                'reference_id' => $purchase->id,
                'note' => $validated['note'] ?? ("Pelunasan Hutang Pembelian: " . $purchase->purchase_number),
                'transaction_date' => $validated['payment_date'],
            ]);

            return response()->json($purchase->load('producer'));
        });
    }

    public function uploadProof(Request $request, Purchase $purchase)
    {
        if (!in_array($request->user()->role, ['admin', 'owner'])) {
            abort(403);
        }

        $request->validate([
            'payment_proof' => 'required|image|max:5120',
            'payment_method' => 'required|in:cash,bank',
        ]);

        if ($request->hasFile('payment_proof')) {
            try {
                $path = $request->file('payment_proof')->storeOnCloudinary('nutrichain/purchases')->getSecurePath();
                
                $purchase->update([
                    'payment_proof' => $path,
                    'payment_method' => $request->payment_method,
                    'payment_status' => 'pending_verification'
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Gagal mengupload bukti pembayaran. Pastikan CLOUDINARY_URL terkonfigurasi di server.', 'error' => $e->getMessage()], 500);
            }
        }

        return response()->json($purchase);
    }

    public function verifyPayment(Request $request, Purchase $purchase)
    {
        if ($request->user()->role !== 'owner') {
            abort(403);
        }

        $request->validate(['status' => 'required|in:paid,unpaid']);

        return DB::transaction(function () use ($request, $purchase) {
            if ($request->status === 'paid') {
                $paymentMethod = $purchase->payment_method ?? 'cash';
                $currentBalance = \App\Models\CashFlow::where('supplier_id', $purchase->supplier_id)
                    ->where('account_type', $paymentMethod)
                    ->sum(DB::raw("CASE WHEN type = 'in' THEN amount ELSE -amount END"));

                if ($currentBalance < $purchase->total_amount) {
                    return response()->json([
                        'message' => 'Gagal verifikasi. Saldo ' . ($paymentMethod === 'cash' ? 'Tunai' : 'Bank') . ' tidak mencukupi.'
                    ], 400);
                }

                $purchase->update(['payment_status' => 'paid']);

                // LOG CASH FLOW OUT
                CashFlow::create([
                    'supplier_id' => $purchase->supplier_id,
                    'type' => 'out',
                    'category' => 'purchase',
                    'amount' => $purchase->total_amount,
                    'account_type' => $purchase->payment_method ?? 'cash',
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'note' => "Pembelian Ke Produsen: " . $purchase->purchase_number,
                    'transaction_date' => now(),
                ]);
            } else {
                $purchase->update(['payment_status' => 'unpaid', 'payment_proof' => null]);
            }

            return response()->json($purchase);
        });
    }
}
