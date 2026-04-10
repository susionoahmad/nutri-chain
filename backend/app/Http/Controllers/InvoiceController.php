<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\CashFlow;
use App\Models\ActivityLog;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->user()->role;
        $query = Invoice::with(['order.customer', 'order.items.product']);

        if ($role === 'customer') {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('customer_id', $request->user()->customer_id);
            });
        } elseif (!in_array($role, ['owner', 'admin'])) {
            // Warehouse & Driver should probably not see invoices
            return response()->json(['data' => []]);
        }

        return InvoiceResource::collection($query->with('activities')->latest()->get());
    }

    public function uploadProof(Request $request, Invoice $invoice)
    {
        // Hanya Admin (atau Owner) yang boleh unggah bukti pembayaran
        if (!in_array($request->user()->role, ['admin', 'owner'])) {
            abort(403, 'Unauthorized. Only admin can upload payment proof.');
        }

        $request->validate([
            'payment_proof' => 'required|image|max:5120', // max 5MB
            'payment_method' => 'required|in:cash,bank',
        ]);

        if ($invoice->status === 'paid') {
            return response()->json(['message' => 'Invoice already paid.'], 400);
        }

        if ($request->hasFile('payment_proof')) {
            // Hapus file lama jika ada (dan jika itu file lokal, bukan cloudinary URL)
            if ($invoice->payment_proof && !str_starts_with($invoice->payment_proof, 'http')) {
                Storage::disk('public')->delete($invoice->payment_proof);
            }
            
            // Konfigurasi Cloudinary: Upload langsung ke Cloudinary
            // Jika ada `.env` CLOUDINARY_URL, ini akan langsung terupload ke cloud.
            // Jika untuk local testing tanpa CLOUDINARY_URL, bisa gagal, jadi pastikan diatur.
            try {
                $path = $request->file('payment_proof')->storeOnCloudinary('nutrichain/invoices')->getSecurePath();
            } catch (\Exception $e) {
                // Return 500 error jika Cloudinary belum di set (terutama saat testing lokal)
                return response()->json(['message' => 'Gagal mengupload bukti pembayaran. Pastikan CLOUDINARY_URL terkonfigurasi di server.', 'error' => $e->getMessage()], 500);
            }
            
            $invoice->update([
                'payment_proof' => $path,
                'payment_method' => $request->payment_method,
                'status' => 'pending_verification' // Tunggu dicek owner
            ]);

            // LOG ACTIVITY: Bukti Bayar Diunggah
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'supplier_id' => $invoice->order->supplier_id,
                'subject_type' => Invoice::class,
                'subject_id' => $invoice->id,
                'action' => 'proof_uploaded',
                'description' => "Bukti pembayaran (" . strtoupper($request->payment_method) . ") diunggah oleh " . $request->user()->name,
                'causer_name' => $request->user()->name,
            ]);
            
            return new InvoiceResource($invoice->load(['order.customer', 'activities']));
        }

        return response()->json(['message' => 'File not detected.'], 400);
    }

    public function verifyPayment(Request $request, Invoice $invoice)
    {
        // Dual control: Hanya owner yang boleh memverifikasi
        if ($request->user()->role !== 'owner') {
            abort(403, 'Unauthorized. Only owner can verify and close payments.');
        }

        $request->validate([
            'status' => 'required|in:paid,unpaid'
        ]);

        $newStatus = $request->status;

        // Jika owner mereject (mengembalikan ke unpaid)
        if ($newStatus === 'unpaid') {
            // Optionally delete proof from disk
            if ($invoice->payment_proof) {
                 Storage::disk('public')->delete($invoice->payment_proof);
            }
            $invoice->update([
                'payment_proof' => null,
                'status' => 'unpaid'
            ]);

            // LOG ACTIVITY: Pembayaran Ditolak
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'supplier_id' => $invoice->order->supplier_id,
                'subject_type' => Invoice::class,
                'subject_id' => $invoice->id,
                'action' => 'payment_rejected',
                'description' => "Pembayaran ditolak oleh Owner: " . $request->user()->name,
                'causer_name' => $request->user()->name,
            ]);
        } else {
            return DB::transaction(function () use ($request, $invoice) {
                // Approve
                $invoice->update([
                    'status' => 'paid'
                ]);

                // LOG ACTIVITY: Pembayaran Disahkan
                ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'supplier_id' => $invoice->order->supplier_id,
                    'subject_type' => Invoice::class,
                    'subject_id' => $invoice->id,
                    'action' => 'payment_verified',
                    'description' => "Pembayaran disahkan oleh " . $request->user()->name . ". Saldo masuk ke Buku Kas.",
                    'causer_name' => $request->user()->name,
                ]);

                // LOG CASH FLOW IN
                CashFlow::create([
                    'supplier_id' => $invoice->order->supplier_id,
                    'type' => 'in',
                    'category' => 'sales',
                    'amount' => $invoice->total,
                    'account_type' => $invoice->payment_method ?? 'cash',
                    'reference_type' => 'invoice',
                    'reference_id' => $invoice->id,
                    'note' => "Pelunasan Invoice: " . $invoice->order->order_number,
                    'transaction_date' => now(),
                ]);

                return new InvoiceResource($invoice->load(['order.customer', 'activities']));
            });
        }
    }
}
