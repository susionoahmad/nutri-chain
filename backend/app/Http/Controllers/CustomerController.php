<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        return Customer::where('supplier_id', $request->user()->supplier_id)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:15', // Matches migration
        ]);

        // Explicitly set supplier_id from authenticated user
        $supplier = $request->user()->supplier;

        if (!$supplier || !$supplier->canAddCustomer()) {
            return response()->json([
                'message' => 'Anda telah mencapai batas maksimal jumlah pelanggan untuk paket langganan saat ini.',
                'code' => 'LIMIT_REACHED'
            ], 403);
        }

        $validated['supplier_id'] = $supplier->id;

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
    }

    public function show(Customer $customer)
    {
        return $customer;
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
            'phone' => 'sometimes|required|string|max:15',
        ]);

        $customer->update($validated);

        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(null, 204);
    }
}
