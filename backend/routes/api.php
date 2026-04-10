<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Saas\SaasSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register/supplier', [AuthController::class, 'registerSupplier']);
Route::post('/register/customer', [AuthController::class, 'registerCustomer']);
Route::get('/suppliers/check-code/{code}', [\App\Http\Controllers\SupplierController::class, 'checkCode']);

Route::middleware(['auth:sanctum', 'saas_active'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete']);
    
    // Public Settings (accessible to all authenticated users for Billing/Support)
    Route::get('/saas/settings', [SaasSettingController::class, 'index']);
    
    Route::get('/dashboard-stats', [DashboardController::class, 'stats']);
    
    // --- PRODUCT MODULE ---
    Route::get('/products', [ProductController::class, 'index']); // Semua role bisa lihat daftar produk
    Route::get('/products/template-import', [ProductController::class, 'templateImport'])->middleware('role:owner,admin');
    Route::post('/products/import', [ProductController::class, 'import'])->middleware('role:owner,admin');
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::middleware('role:owner,admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });

    // --- ORDER WORKFLOW MODULE ---
    // Semua role bisa melihat list order (filtered by supplier_id & role logic in Controller)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::patch('/orders/{order}', [OrderController::class, 'update']); 

    // 1. Customer: Create Order
    Route::post('/orders', [OrderController::class, 'store'])->middleware('role:customer,admin,owner');
    Route::get('/orders/check-stock/{product}', [OrderController::class, 'checkStock'])->middleware('role:customer,admin,owner');

    // 2. Admin: Confirm Order (Logic: Stok validation & reduction)
    Route::patch('/orders/{order}/confirm', [OrderController::class, 'confirm'])->middleware('role:admin,owner');

    // 3. Warehouse: Dispatch Order (Status -> on_delivery)
    Route::patch('/orders/{order}/dispatch', [OrderController::class, 'dispatch'])->middleware('role:warehouse,admin,owner');

    // 4. Driver: Deliver Order (Status -> delivered & Auto-generate Invoice)
    Route::patch('/orders/{order}/deliver', [OrderController::class, 'deliver'])->middleware('role:driver,admin,owner');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('role:customer,admin,owner');

    // --- INVOICE MODULE ---
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'uploadProof'])->middleware('role:owner,admin,customer');
    Route::put('/invoices/{invoice}/verify', [InvoiceController::class, 'verifyPayment'])->middleware('role:owner');

    // --- OWNER & REPORTING ---
    Route::middleware('role:owner')->group(function () {
        Route::apiResource('users', \App\Http\Controllers\UserController::class);
        Route::get('/supplier', [\App\Http\Controllers\SupplierController::class, 'show']);
        Route::put('/supplier', [\App\Http\Controllers\SupplierController::class, 'update']);
        Route::post('/supplier/generate-code', [\App\Http\Controllers\SupplierController::class, 'generateCode']);
        
        // Laporan Profit (Hanya Owner)
        Route::get('/reports/profit-loss', [ReportController::class, 'getProfitLoss']);

        // --- BILLING & SUBSCRIPTION (OWNER) ---
        Route::get('/billing/plans', [\App\Http\Controllers\Saas\BillingController::class, 'plans']);
        Route::get('/billing/current', [\App\Http\Controllers\Saas\BillingController::class, 'current']);
        Route::post('/billing/subscribe', [\App\Http\Controllers\Saas\BillingController::class, 'subscribe']);
    });

    // --- OPERATIONAL MODULES (ADMIN & OWNER) ---
    Route::middleware('role:owner,admin')->group(function () {
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('producers', \App\Http\Controllers\ProducerController::class);
        Route::apiResource('purchases', \App\Http\Controllers\PurchaseController::class);
        Route::post('/purchases/{purchase}/complete', [\App\Http\Controllers\PurchaseController::class, 'complete']);
        Route::post('/purchases/{purchase}/pay', [\App\Http\Controllers\PurchaseController::class, 'pay']);
        Route::post('/purchases/{purchase}/upload-proof', [\App\Http\Controllers\PurchaseController::class, 'uploadProof']);
        Route::put('/purchases/{purchase}/verify', [\App\Http\Controllers\PurchaseController::class, 'verifyPayment']);

        Route::get('/stock-mutations', [\App\Http\Controllers\StockMutationController::class, 'index']);

        Route::get('/cash-flow', [\App\Http\Controllers\CashFlowController::class, 'index']);
        Route::post('/cash-flow', [\App\Http\Controllers\CashFlowController::class, 'storeManual']);
        Route::get('/reports/cash', [ReportController::class, 'getCashReport']);
        Route::get('/reports/stock', [ReportController::class, 'getStockReport']);
        Route::get('/reports/debt-receivable', [ReportController::class, 'getDebtReceivable']);
        Route::get('/reports/product-analysis', [ReportController::class, 'getProductAnalysis']);
    });

    // --- SAAS SUPERADMIN MODULE (URL KHUSUS) ---
    Route::middleware('role:superadmin')->prefix('saas')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Saas\SaasController::class, 'dashboardStats']);
        Route::get('/tenants', [\App\Http\Controllers\Saas\SaasController::class, 'tenants']);
        Route::patch('/tenants/{supplier}', [\App\Http\Controllers\Saas\SaasController::class, 'updateTenantStatus']);
        Route::get('/pending-subscriptions', [\App\Http\Controllers\Saas\SaasController::class, 'pendingSubscriptions']);
        Route::patch('/subscriptions/{subscription}/verify', [\App\Http\Controllers\Saas\SaasController::class, 'verifySubscription']);
        
        // Plans Management
        Route::apiResource('plans', \App\Http\Controllers\Saas\SubscriptionPlanController::class);

        // Platform Settings
        Route::post('/settings', [SaasSettingController::class, 'update']);
    });
});
