<?php

namespace App\Http\Controllers;

use App\Models\Producer;
use Illuminate\Http\Request;

class ProducerController extends Controller
{
    public function index(Request $request)
    {
        return Producer::where('supplier_id', $request->user()->supplier_id)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        $validated['supplier_id'] = $request->user()->supplier_id;

        $producer = Producer::create($validated);

        return response()->json($producer, 201);
    }

    public function show(Producer $producer)
    {
        return $producer;
    }

    public function update(Request $request, Producer $producer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        $producer->update($validated);

        return response()->json($producer);
    }

    public function destroy(Producer $producer)
    {
        $producer->delete();

        return response()->json(null, 204);
    }
}
