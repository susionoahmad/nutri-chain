<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * Menampilkan data supplier milik owner yang sedang login.
     * Termasuk Supplier Code yang digunakan untuk registrasi customer.
     */
    public function show(Request $request)
    {
        $supplier = $request->user()->supplier;

        if (!$supplier) {
            return response()->json(['message' => 'Supplier tidak ditemukan.'], 404);
        }

        return response()->json($supplier);
    }

    /**
     * Memperbarui informasi supplier, termasuk mengelola Supplier Code.
     */
    public function update(Request $request)
    {
        $supplier = $request->user()->supplier;

        if (!$supplier) {
            return response()->json(['message' => 'Supplier tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash', // Memastikan kode hanya berisi huruf, angka, dash, dan underscore
                Rule::unique('suppliers', 'code')->ignore($supplier->id),
            ],
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return response()->json([
            'message' => 'Informasi supplier dan kode akses berhasil diperbarui.',
            'supplier' => $supplier
        ]);
    }

    /**
     * Menghasilkan Supplier Code acak yang unik.
     */
    public function generateCode()
    {
        do {
            // Menghasilkan string acak uppercase sepanjang 8 karakter
            $code = strtoupper(Str::random(8));
        } while (Supplier::where('code', $code)->exists());

        return response()->json(['code' => $code]);
    }

    /**
     * Mengecek validitas kode supplier (Public Access).
     */
    public function checkCode($code)
    {
        $supplier = Supplier::where('code', $code)->first();

        if (!$supplier) {
            return response()->json(['valid' => false, 'message' => 'Kode Supplier tidak ditemukan'], 404);
        }

        return response()->json(['valid' => true, 'supplier_name' => $supplier->name]);
    }
}