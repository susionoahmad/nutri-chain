<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Admin/Owner mengonfirmasi pesanan dan mengurangi stok.
     */
    public function confirm(Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Hanya pesanan pending yang bisa dikonfirmasi.'], 400);
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->orderItems as $item) {
                // Lock baris produk untuk mencegah race condition stok
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product || $product->stock < $item->qty) {
                    throw new \Exception("Stok produk '{$item->product_name}' tidak mencukupi.");
                }

                // Kurangi stok
                $product->decrement('stock', $item->qty);
            }

            $order->update(['status' => 'confirmed']);

            return response()->json([
                'message' => 'Pesanan berhasil dikonfirmasi dan stok telah dikurangi.',
                'order' => $order->load('orderItems')
            ]);
        });
    }

    /**
     * Warehouse mengubah status menjadi pengiriman.
     */
    public function dispatch(Order $order)
    {
        if ($order->status !== 'confirmed') {
            return response()->json(['message' => 'Pesanan harus dikonfirmasi terlebih dahulu.'], 400);
        }

        $order->update(['status' => 'on_delivery']);

        return response()->json([
            'message' => 'Barang sedang dalam perjalanan.',
            'order' => $order
        ]);
    }

    /**
     * Driver mengonfirmasi barang sampai & Auto-generate Invoice.
     */
    public function deliver(Order $order)
    {
        if ($order->status !== 'on_delivery') {
            return response()->json(['message' => 'Pesanan harus dalam status pengiriman.'], 400);
        }

        return DB::transaction(function () use ($order) {
            // 1. Update status Order
            $order->update(['status' => 'delivered']);

            // 2. Buat record Pengiriman (Deliveries)
            Delivery::create([
                'order_id' => $order->id,
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);

            // 3. Kalkulasi Invoice
            // total = sum(qty * price)
            // total_cost = sum(qty * cost_price)
            $total = 0;
            $totalCost = 0;

            foreach ($order->orderItems as $item) {
                $total += $item->qty * $item->price;
                $totalCost += $item->qty * $item->cost_price;
            }

            $totalProfit = $total - $totalCost;

            // 4. Generate Invoice Otomatis
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'total' => $total,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
                'status' => 'unpaid',
                'due_date' => now()->addDays(7), // Contoh jatuh tempo 7 hari
            ]);

            return response()->json([
                'message' => 'Pesanan diterima. Invoice otomatis telah diterbitkan.',
                'invoice' => $invoice,
                'order' => $order
            ]);
        });
    }
}