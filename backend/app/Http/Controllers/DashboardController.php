<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\CashFlow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $supplierId = $request->user()->supplier_id;
        $role = $request->user()->role;
        $customerId = $request->user()->customer_id;

        if ($role === 'customer') {
            return $this->customerStats($supplierId, $customerId);
        } elseif ($role === 'warehouse') {
            return $this->warehouseStats($supplierId);
        } elseif ($role === 'driver') {
            return $this->driverStats($supplierId);
        }

        if ($role === 'owner') {
            return $this->ownerStats($supplierId);
        }

        // Default: Admin
        return $this->adminStats($supplierId);
    }

    private function ownerStats($supplierId)
    {
        $totalProducts = Product::where('supplier_id', $supplierId)->count();
        $totalCustomers = Customer::where('supplier_id', $supplierId)->count();
        $totalOrders = Order::where('supplier_id', $supplierId)->count();
        
        $totalRevenue = Order::where('supplier_id', $supplierId)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        // Total Tagihan Invoice yang belum dibayar semua pelanggan
        $unpaidInvoices = Invoice::whereHas('order', function ($query) use ($supplierId) {
            $query->where('supplier_id', $supplierId);
        })->where('status', 'unpaid')->sum('total');

        // Total Profit
        $totalProfit = Invoice::whereHas('order', function ($query) use ($supplierId) {
            $query->where('supplier_id', $supplierId);
        })->sum('total_profit');

        // Kalkulasi Berapa Banyak Pesanan Baru yang Menunggu Konfirmasi (Pending)
        $pendingOrdersCount = Order::where('supplier_id', $supplierId)
            ->where('status', 'pending')->count();

        // Kalkulasi Berapa Banyak Invoice yang Menunggu Persetujuan Pembayaran (Pending Verification)
        $pendingPaymentsCount = Invoice::whereHas('order', function ($query) use ($supplierId) {
            $query->where('supplier_id', $supplierId);
        })->where('status', 'pending_verification')->count();

        // 1. Month Profit (Month to Date)
        $monthProfit = Invoice::whereHas('order', function ($query) use ($supplierId) {
            $query->where('supplier_id', $supplierId);
        })->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()])
          ->sum('total_profit');

        // 2. Cash Balances
        $cashInBank = CashFlow::where('supplier_id', $supplierId)->where('account_type', 'bank')->where('type', 'in')->sum('amount');
        $cashOutBank = CashFlow::where('supplier_id', $supplierId)->where('account_type', 'bank')->where('type', 'out')->sum('amount');
        $cashBalanceBank = $cashInBank - $cashOutBank;

        $cashInCash = CashFlow::where('supplier_id', $supplierId)->where('account_type', 'cash')->where('type', 'in')->sum('amount');
        $cashOutCash = CashFlow::where('supplier_id', $supplierId)->where('account_type', 'cash')->where('type', 'out')->sum('amount');
        $cashBalanceCash = $cashInCash - $cashOutCash;

        $totalCashBalance = $cashBalanceBank + $cashBalanceCash;

        $recentOrders = Order::with('customer')
            ->where('supplier_id', $supplierId)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'role' => 'owner',
            'pending_orders_count' => $pendingOrdersCount,
            'pending_payments_count' => $pendingPaymentsCount,
            'total_products' => $totalProducts,
            'total_customers' => $totalCustomers,
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'total_profit' => (float) $totalProfit,
            'month_profit' => (float) $monthProfit,
            'cash_balance_bank' => (float) $cashBalanceBank,
            'cash_balance_cash' => (float) $cashBalanceCash,
            'total_cash_balance' => (float) $totalCashBalance,
            'unpaid_invoices' => (float) $unpaidInvoices,
            'recent_orders' => OrderResource::collection($recentOrders),
        ]);
    }

    private function adminStats($supplierId)
    {
        $totalProducts = Product::where('supplier_id', $supplierId)->count();
        $totalCustomers = Customer::where('supplier_id', $supplierId)->count();
        $totalOrders = Order::where('supplier_id', $supplierId)->count();
        
        $totalRevenue = Order::where('supplier_id', $supplierId)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        // Total Tagihan Invoice yang belum lunas (Operational visibility)
        $unpaidInvoices = Invoice::whereHas('order', function ($query) use ($supplierId) {
            $query->where('supplier_id', $supplierId);
        })->where('status', 'unpaid')->sum('total');

        // Admin ONLY sees Pending Order confirmation count
        $pendingOrdersCount = Order::where('supplier_id', $supplierId)
            ->where('status', 'pending')->count();

        $recentOrders = Order::with('customer')
            ->where('supplier_id', $supplierId)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'role' => 'admin',
            'pending_orders_count' => $pendingOrdersCount,
            'pending_payments_count' => 0, // Admin doesn't handle payments verification
            'total_products' => $totalProducts,
            'total_customers' => $totalCustomers,
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'unpaid_invoices' => (float) $unpaidInvoices,
            'recent_orders' => OrderResource::collection($recentOrders),
            // Explicitly hide sensitive fields
            'total_profit' => 0,
            'month_profit' => 0,
            'total_cash_balance' => 0,
        ]);
    }

    private function customerStats($supplierId, $customerId)
    {
        $totalOrders = Order::where('customer_id', $customerId)->count();
        
        $activeOrders = Order::where('customer_id', $customerId)
            ->whereIn('status', ['pending', 'confirmed', 'on_delivery'])
            ->count();

        // Hutang spesifik SPPG ini yang belum lunas
        $unpaidDebt = Invoice::whereHas('order', function ($query) use ($customerId) {
            $query->where('customer_id', $customerId);
        })->where('status', 'unpaid')->sum('total');

        $recentOrders = Order::with('items.product', 'invoice')
            ->where('customer_id', $customerId)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'role' => 'customer',
            'total_orders' => $totalOrders,
            'active_orders' => $activeOrders,
            'unpaid_debt' => (float) $unpaidDebt,
            'recent_orders' => OrderResource::collection($recentOrders),
        ]);
    }

    private function warehouseStats($supplierId)
    {
        $ordersToPick = Order::where('supplier_id', $supplierId)
            ->where('status', 'confirmed')->count();

        // Kalkulasi total buah barang yang harus dicari (quantity packing) di gudang
        $totalItemsToPick = Order::where('supplier_id', $supplierId)
            ->where('status', 'confirmed')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->sum('order_items.qty');

        $recentQueue = Order::with('customer', 'items.product')
            ->where('supplier_id', $supplierId)
            ->where('status', 'confirmed')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'role' => 'warehouse',
            'orders_to_pick' => $ordersToPick,
            'total_items_to_pick' => (int) $totalItemsToPick,
            'recent_orders' => OrderResource::collection($recentQueue),
        ]);
    }

    private function driverStats($supplierId)
    {
        $ordersToDeliver = Order::where('supplier_id', $supplierId)
            ->where('status', 'on_delivery')->count();

        $deliveredToday = Order::where('supplier_id', $supplierId)
            ->where('status', 'delivered')
            ->whereDate('updated_at', today())
            ->count();

        $recentQueue = Order::with('customer')
            ->where('supplier_id', $supplierId)
            ->whereIn('status', ['on_delivery', 'delivered'])
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'role' => 'driver',
            'orders_to_deliver' => $ordersToDeliver,
            'delivered_today' => $deliveredToday,
            'recent_orders' => OrderResource::collection($recentQueue),
        ]);
    }
}
