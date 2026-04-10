<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\CashFlow;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function getProfitLoss(Request $request)
    {
        $start = $request->start_date ?? Carbon::now()->startOfMonth()->toDateString();
        $end = $request->end_date ?? Carbon::now()->endOfDay()->toDateString();
        $user = $request->user();
        if ($user->role !== 'owner') {
            return response()->json(['message' => 'Laporan Laba Rugi hanya dapat diakses oleh Owner.'], 403);
        }
        $supplier_id = $user->supplier_id;

        // Omzet (Revenue) from completed orders
        $revenue = Order::where('supplier_id', $supplier_id)
            ->whereBetween('order_date', [$start, $end])
            ->where('status', 'delivered')
            ->sum('total_amount');

        // COGS (HPP) from order items
        $cogs = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.supplier_id', $supplier_id)
            ->whereBetween('orders.order_date', [$start, $end])
            ->where('orders.status', 'delivered')
            ->select(DB::raw('SUM(order_items.qty * order_items.cost_price) as total_cogs'))
            ->first()->total_cogs ?? 0;

        // Operating Expenses (Biaya Operasional) from CashFlow
        $expenses = CashFlow::where('supplier_id', $supplier_id)
            ->whereBetween('transaction_date', [$start, $end])
            ->where('category', 'expense')
            ->where('type', 'out')
            ->sum('amount');

        return response()->json([
            'revenue' => (float)$revenue,
            'cogs' => (float)$cogs,
            'gross_profit' => (float)($revenue - $cogs),
            'expenses' => (float)$expenses,
            'net_profit' => (float)($revenue - $cogs - $expenses),
            'period' => ['start' => $start, 'end' => $end]
        ]);
    }

    public function getCashReport(Request $request)
    {
        $start = $request->start_date;
        $end = $request->end_date;
        $role = $request->user()->role;
        $supplier_id = $request->user()->supplier_id;

        $query = CashFlow::where('supplier_id', $supplier_id);
        
        // Permintaan: Admin hanya melihat kas tunai
        if ($role === 'admin') {
            $query->where('account_type', 'cash');
        }
        if ($start && $end) {
            $query->whereBetween('transaction_date', [$start, $end]);
        }

        $history = $query->orderBy('transaction_date', 'desc')->get();
        
        $summary = [
            'total_in' => $query->clone()->where('type', 'in')->sum('amount'),
            'total_out' => $query->clone()->where('type', 'out')->sum('amount'),
        ];

        return response()->json([
            'history' => $history,
            'summary' => $summary
        ]);
    }

    public function getStockReport(Request $request)
    {
        $start = $request->start_date;
        $end = $request->end_date;
        $supplier_id = $request->user()->supplier_id;

        // Stock IN (Purchases & Manual Inventory IN/Adjustments that add stock)
        // For adjustments, we only count them if they increased stock implicitly by checking the type or note.
        // Actually, since we now have 'in', 'out', and 'adjustment', we'll treat 'in' as stock in. 
        $stock_in = DB::table('stock_mutations')
            ->join('products', 'stock_mutations.product_id', '=', 'products.id')
            ->where('stock_mutations.supplier_id', $supplier_id)
            ->whereIn('stock_mutations.type', ['in', 'adjustment']) 
            // Note: Since 'adjustment' is positive difference, we'll just include it in 'in' for simplicity, 
            // or better, only include if new_qty > old_qty for adjustments.
            ->where(function ($query) {
                $query->where('stock_mutations.type', 'in')
                      ->orWhere(function ($q) {
                          $q->where('stock_mutations.type', 'adjustment')
                            ->whereRaw('stock_mutations.new_qty > stock_mutations.old_qty');
                      });
            })
            ->when($start && $end, fn($q) => $q->whereBetween(DB::raw('DATE(stock_mutations.transaction_date)'), [$start, $end]))
            ->select('products.name', DB::raw('SUM(stock_mutations.qty) as total_qty'))
            ->groupBy('products.id', 'products.name')
            ->get();

        // Stock OUT (Orders & Downward Adjustments)
        $stock_out = DB::table('stock_mutations')
            ->join('products', 'stock_mutations.product_id', '=', 'products.id')
            ->where('stock_mutations.supplier_id', $supplier_id)
            ->where(function ($query) {
                $query->where('stock_mutations.type', 'out')
                      ->orWhere(function ($q) {
                          $q->where('stock_mutations.type', 'adjustment')
                            ->whereRaw('stock_mutations.new_qty < stock_mutations.old_qty');
                      });
            })
            ->when($start && $end, fn($q) => $q->whereBetween(DB::raw('DATE(stock_mutations.transaction_date)'), [$start, $end]))
            ->select('products.name', DB::raw('SUM(stock_mutations.qty) as total_qty'))
            ->groupBy('products.id', 'products.name')
            ->get();

        return response()->json([
            'in' => $stock_in,
            'out' => $stock_out
        ]);
    }

    public function getDebtReceivable(Request $request)
    {
        $supplier_id = $request->user()->supplier_id;
        $start = $request->start_date;
        $end = $request->end_date;

        // Piutang (Receivables) - Customer Invoices not paid
        $receivablesQuery = Invoice::whereHas('order', function($q) use ($supplier_id) {
            $q->where('supplier_id', $supplier_id);
        })->where('status', '!=', 'paid');

        $receivables = $receivablesQuery->with('order.customer')->get()
            ->map(fn($i) => [
                'name' => $i->order->customer->name ?? 'N/A',
                'phone' => $i->order->customer->phone ?? '',
                'order_number' => $i->order->order_number ?? '',
                'amount' => (float)$i->total,
                'due_date' => $i->due_date,
                'status' => $i->status
            ]);

        // Hutang (Debt/Payables) - Purchases from Producers not paid
        $debtsQuery = Purchase::where('supplier_id', $supplier_id)
            ->where('payment_status', '!=', 'paid');

        $debts = $debtsQuery->with('producer')->get()
            ->map(fn($p) => [
                'name' => $p->producer->name ?? 'N/A',
                'amount' => (float)$p->total_amount,
                'date' => $p->purchase_date,
                'status' => $p->payment_status
            ]);

        return response()->json([
            'receivables' => $receivables,
            'debts' => $debts,
            'total_receivables' => (float)$receivables->sum('amount'),
            'total_debts' => (float)$debts->sum('amount'),
        ]);
    }
    public function getProductAnalysis(Request $request)
    {
        $start = $request->start_date ?? Carbon::now()->startOfMonth()->toDateString();
        $end = $request->end_date ?? Carbon::now()->endOfDay()->toDateString();
        $supplier_id = $request->user()->supplier_id;

        // 1. Grouped by Product
        $productStats = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.supplier_id', $supplier_id)
            ->whereBetween('orders.order_date', [$start, $end])
            ->where('orders.status', 'delivered')
            ->select(
                'products.name',
                'products.category',
                DB::raw('SUM(order_items.qty) as total_qty'),
                DB::raw('SUM(order_items.qty * order_items.price) as revenue'),
                DB::raw('SUM(order_items.qty * (order_items.price - order_items.cost_price)) as gross_profit')
            )
            ->groupBy('products.id', 'products.name', 'products.category')
            ->orderBy('gross_profit', 'desc')
            ->get();

        // 2. Grouped by Category
        $categoryStats = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.supplier_id', $supplier_id)
            ->whereBetween('orders.order_date', [$start, $end])
            ->where('orders.status', 'delivered')
            ->select(
                DB::raw('COALESCE(products.category, "Uncategorized") as category_name'),
                DB::raw('SUM(order_items.qty) as total_qty'),
                DB::raw('SUM(order_items.qty * order_items.price) as revenue'),
                DB::raw('SUM(order_items.qty * (order_items.price - order_items.cost_price)) as gross_profit')
            )
            ->groupBy('category_name')
            ->orderBy('gross_profit', 'desc')
            ->get();

        return response()->json([
            'products' => $productStats,
            'categories' => $categoryStats,
            'period' => ['start' => $start, 'end' => $end]
        ]);
    }
}
