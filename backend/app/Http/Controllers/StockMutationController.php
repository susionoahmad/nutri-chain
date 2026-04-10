<?php

namespace App\Http\Controllers;

use App\Models\StockMutation;
use Illuminate\Http\Request;

class StockMutationController extends Controller
{
    /**
     * Display a listing of the inventory movements.
     */
    public function index(Request $request)
    {
        $query = StockMutation::with('product')
            ->where('supplier_id', $request->user()->supplier_id);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $mutations = $query->latest()->paginate($request->get('limit', 50));

        return response()->json($mutations);
    }
}
