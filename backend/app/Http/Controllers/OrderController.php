<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items.product', 'invoice'])
            ->where('supplier_id', $request->user()->supplier_id);

        if ($request->user()->role === 'customer') {
            $query->where('customer_id', $request->user()->customer_id);
        }

        return \App\Http\Resources\OrderResource::collection($query->latest()->get());
    }

    public function store(Request $request)
    {
        // If user is a customer, customer_id is auto-resolved from their profile
        $isCustomerRole = $request->user()->role === 'customer';

        $validated = $request->validate([
            'customer_id' => $isCustomerRole ? 'nullable' : 'required|exists:customers,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        // Auto-assign customer_id if role is customer
        if ($isCustomerRole) {
            $customerId = $request->user()->customer_id;
            if (!$customerId) {
                return response()->json(['message' => 'Akun Anda belum terhubung ke data SPPG. Hubungi admin.'], 422);
            }
            $validated['customer_id'] = $customerId;
        }

        return DB::transaction(function () use ($request, $validated) {
            $user = $request->user();
            
            // 1. Create Order
            $order = Order::create([
                'supplier_id' => $user->supplier_id,
                'customer_id' => $validated['customer_id'],
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'],
                'total_amount' => 0, // Will update after items
                'status' => 'pending',
                'notes' => $validated['notes'],
            ]);

            $totalAmount = 0;

            // 2. Process Items
            foreach ($validated['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                
                // Snapshot values
                $price = $product->price;
                $costPrice = $product->cost_price;
                $qty = $itemData['qty'];
                $subtotal = $price * $qty;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => $price,
                    'cost_price' => $costPrice,
                ]);

                $totalAmount += $subtotal;
            }

            // 4. Update Final Amount
            $order->update(['total_amount' => $totalAmount]);

            // 5. LOG ACTIVITY: Pesanan Dibuat
            ActivityLog::create([
                'user_id' => $user->id,
                'supplier_id' => $user->supplier_id,
                'subject_type' => Order::class,
                'subject_id' => $order->id,
                'action' => 'created',
                'description' => "Pesanan baru #{$order->order_number} dibuat oleh " . ($user->role === 'customer' ? 'Customer' : 'Admin'),
                'causer_name' => $user->name,
            ]);

            return response()->json(new \App\Http\Resources\OrderResource($order->load(['customer', 'items.product'])), 201);
        });
    }

    public function show(Order $order)
    {
        return new \App\Http\Resources\OrderResource($order->load(['customer', 'items.product', 'delivery', 'invoice', 'activities']));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,on_delivery,delivered,cancelled',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $order->update($validated);

        return response()->json(new \App\Http\Resources\OrderResource($order->load(['customer', 'items.product', 'activities'])));
    }

    /**
     * Step 2: Admin/Owner - Konfirmasi Pesanan & Potong Stok
     */
    public function confirm(Request $request, Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Hanya pesanan PENDING yang bisa dikonfirmasi.'], 422);
        }

        return DB::transaction(function () use ($request, $order) {
            // 1. VALIDASI & PENGURANGAN STOK
            foreach ($order->items as $item) {
                $stockRecord = Stock::firstOrCreate(['product_id' => $item->product_id], ['qty' => 0]);
                
                $oldQty = $stockRecord->qty;
                $stockRecord->decrement('qty', $item->qty);
                $newQty = $stockRecord->qty;

                // RECORD MUTATION
                \App\Models\StockMutation::create([
                    'supplier_id' => $order->supplier_id,
                    'product_id' => $item->product_id,
                    'type' => 'out',
                    'qty' => $item->qty,
                    'old_qty' => $oldQty,
                    'new_qty' => $newQty,
                    'reference_type' => 'Order',
                    'reference_id' => $order->id,
                    'note' => "Penjualan ke pelanggan: " . $order->customer->name,
                    'transaction_date' => now(),
                ]);
            }

            // 2. UPDATE STATUS
            $oldStatus = $order->status;
            $order->update(['status' => 'confirmed']);

            // 3. LOG ACTIVITY
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'supplier_id' => $order->supplier_id,
                'subject_type' => Order::class,
                'subject_id' => $order->id,
                'action' => 'status_updated',
                'description' => "Pesanan dikonfirmasi oleh Admin. Stok telah dikurangi.",
                'causer_name' => $request->user()->name,
                'properties' => ['old_status' => $oldStatus, 'new_status' => 'confirmed']
            ]);

            return response()->json(new \App\Http\Resources\OrderResource($order->load(['customer', 'items.product', 'activities'])));
        });
    }

    /**
     * Step 3: Warehouse - Kirim Barang
     */
    public function dispatch(Request $request, Order $order)
    {
        if ($order->status !== 'confirmed') {
            return response()->json(['message' => 'Hanya pesanan CONFIRMED yang bisa dikirim.'], 422);
        }

        $oldStatus = $order->status;
        $order->update(['status' => 'on_delivery']);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'supplier_id' => $order->supplier_id,
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'action' => 'status_updated',
            'description' => "Pesanan sedang dalam pengiriman.",
            'causer_name' => $request->user()->name,
            'properties' => ['old_status' => $oldStatus, 'new_status' => 'on_delivery']
        ]);

        return response()->json(new \App\Http\Resources\OrderResource($order->load(['customer', 'items.product', 'activities'])));
    }

    /**
     * Step 4: Driver - Barang Diterima & Auto Invoice
     */
    public function deliver(Request $request, Order $order)
    {
        if ($order->status !== 'on_delivery') {
            return response()->json(['message' => 'Hanya pesanan ON_DELIVERY yang bisa ditandai selesai.'], 422);
        }

        return DB::transaction(function () use ($request, $order) {
            $oldStatus = $order->status;
            
            // 1. Update Status
            $order->update(['status' => 'delivered']);

            // 2. Generate Delivery Record
            Delivery::updateOrCreate(
                ['order_id' => $order->id],
                ['status' => 'success', 'delivered_at' => now()]
            );

            // 3. Generate Invoice
            $totalCost = $order->items->sum(function($item) {
                return $item->qty * $item->cost_price;
            });
            $totalProfit = $order->total_amount - $totalCost;

            Invoice::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'total' => $order->total_amount,
                    'total_cost' => $totalCost,
                    'total_profit' => $totalProfit,
                    'status' => 'unpaid',
                    'due_date' => now()->addDays(7),
                ]
            );

            // 4. Log Activity
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'supplier_id' => $order->supplier_id,
                'subject_type' => Order::class,
                'subject_id' => $order->id,
                'action' => 'status_updated',
                'description' => "Pesanan telah diterima oleh pelanggan. Invoice otomatis dibuat.",
                'causer_name' => $request->user()->name,
                'properties' => ['old_status' => $oldStatus, 'new_status' => 'delivered']
            ]);

            return response()->json(new \App\Http\Resources\OrderResource($order->load(['customer', 'items.product', 'activities'])));
        });
    }

    /**
     * Pembatalan Pesanan 
     * Logic: Jika status sebelumnya sudah confirmed, maka kembalikan stok.
     * Jika sudah ada invoice, maka batalkan invoice (void).
     */
    public function cancel(Request $request, Order $order)
    {
        if ($order->status === 'cancelled') {
            return response()->json(['message' => 'Pesanan sudah dibatalkan.'], 422);
        }

        return DB::transaction(function () use ($request, $order) {
            $oldStatus = $order->status;
            
            // 1. KEMBALIKAN STOK jika status sebelumnya sudah confirmed, on_delivery, atau delivered
            if (in_array($oldStatus, ['confirmed', 'on_delivery', 'delivered'])) {
                foreach ($order->items as $item) {
                    $stockRecord = Stock::where('product_id', $item->product_id)->first();
                    if ($stockRecord) {
                        $oldQty = $stockRecord->qty;
                        $stockRecord->increment('qty', $item->qty);
                        $newQty = $stockRecord->qty;

                        // RECORD MUTATION
                        \App\Models\StockMutation::create([
                            'supplier_id' => $order->supplier_id,
                            'product_id' => $item->product_id,
                            'type' => 'in',
                            'qty' => $item->qty,
                            'old_qty' => $oldQty,
                            'new_qty' => $newQty,
                            'reference_type' => 'Order',
                            'reference_id' => $order->id,
                            'note' => "Pengembalian stok (Pembatalan Pesanan): " . $order->customer->name,
                            'transaction_date' => now(),
                        ]);
                    }
                }
            }

            // 2. BATALKAN INVOICE jika ada
            if ($order->invoice) {
                $order->invoice->update(['status' => 'void']);
            }

            // 3. UPDATE STATUS
            $order->update(['status' => 'cancelled']);

            // 4. LOG ACTIVITY
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'supplier_id' => $order->supplier_id,
                'subject_type' => Order::class,
                'subject_id' => $order->id,
                'action' => 'status_updated',
                'description' => "Pesanan dibatalkan. " . (in_array($oldStatus, ['confirmed', 'on_delivery', 'delivered']) ? "Stok barang telah dikembalikan." : ""),
                'causer_name' => $request->user()->name,
                'properties' => ['old_status' => $oldStatus, 'new_status' => 'cancelled']
            ]);

            return response()->json(new \App\Http\Resources\OrderResource($order->load(['customer', 'items.product', 'activities', 'invoice'])));
        });
    }

    public function destroy(Order $order)
    {
        // Add back stock if deleted but not cancelled
        if ($order->status !== 'cancelled' && in_array($order->status, ['confirmed', 'on_delivery', 'delivered'])) {
            foreach ($order->items as $item) {
                $stockRecord = Stock::where('product_id', $item->product_id)->first();
                if ($stockRecord) {
                    $stockRecord->increment('qty', $item->qty);
                }
            }
        }
        $order->delete();
        return response()->json(null, 204);
    }

    /**
     * Cek stok produk secara real-time untuk validasi di halaman Create Order.
     */
    public function checkStock(Product $product)
    {
        $stockRecord = Stock::where('product_id', $product->id)->first();

        return response()->json([
            'product_id' => $product->id,
            'available_qty' => $stockRecord ? (int) $stockRecord->qty : 0,
            'unit' => $product->unit,
        ]);
    }
}
