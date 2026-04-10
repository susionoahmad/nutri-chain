<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Eager load stock so frontend gets product.stock.qty
        return Product::with('stock')
            ->where('supplier_id', $request->user()->supplier_id)
            ->get()
            ->map(function ($product) {
                // Flatten: expose stock qty directly as `stock` field for convenience
                $product->stock_qty = $product->stock->qty ?? 0;
                return $product;
            });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'category'   => 'nullable|string|max:100',
            'unit'       => 'required|string|max:50',
            'cost_price' => 'required|numeric|min:0',
            'price'      => 'required|numeric|min:0',
            'stock'      => 'required|integer|min:0',
        ]);

        $validated['supplier_id'] = $request->user()->supplier_id;
        $stockQty = $validated['stock'];
        unset($validated['stock']); // 'stock' column doesn't exist in products

        $product = Product::create($validated);

        // Create stock record in stocks table
        Stock::create([
            'product_id' => $product->id,
            'qty'        => $stockQty,
        ]);

        if ($stockQty > 0) {
            StockMutation::create([
                'supplier_id' => $request->user()->supplier_id,
                'product_id'  => $product->id,
                'type'        => 'in',
                'qty'         => $stockQty,
                'old_qty'     => 0,
                'new_qty'     => $stockQty,
                'reference_type' => 'Stock Initialization',
                'reference_id'   => null,
                'note'        => 'Saldo awal stok produk baru',
                'transaction_date' => now(),
            ]);
        }

        $product->load('stock');
        $product->stock_qty = $product->stock->qty ?? 0;

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        $product->load('stock');
        $product->stock_qty = $product->stock->qty ?? 0;
        return $product;
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'category'   => 'nullable|string|max:100',
            'unit'       => 'sometimes|required|string|max:50',
            'cost_price' => 'sometimes|required|numeric|min:0',
            'price'      => 'sometimes|required|numeric|min:0',
            'stock'      => 'sometimes|required|integer|min:0',
        ]);

        // Handle stock separately
        if (isset($validated['stock'])) {
            $stockQty = $validated['stock'];
            unset($validated['stock']);

            $stock = Stock::firstOrCreate(
                ['product_id' => $product->id],
                ['qty' => 0]
            );

            $oldQty = $stock->qty;
            if ($oldQty !== $stockQty) {
                $diff = $stockQty - $oldQty;
                
                $stock->update(['qty' => $stockQty]);
                
                StockMutation::create([
                    'supplier_id' => $request->user()->supplier_id,
                    'product_id'  => $product->id,
                    'type'        => 'adjustment',
                    'qty'         => abs($diff),
                    'old_qty'     => $oldQty,
                    'new_qty'     => $stockQty,
                    'reference_type' => 'Manual Adjustment',
                    'reference_id'   => null,
                    'note'        => 'Penyesuaian stok manual dari inventori',
                    'transaction_date' => now(),
                ]);
            }
        }

        $product->update($validated);
        $product->load('stock');
        $product->stock_qty = $product->stock->qty ?? 0;

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete(); // stocks are cascade-deleted via FK

        return response()->json(null, 204);
    }

    public function templateImport()
    {
        $header = [
            ['Nama Produk', 'Kategori', 'Satuan', 'Harga Beli', 'Harga Jual', 'Stok Awal']
        ];
        
        $xlsx = SimpleXLSXGen::fromArray($header);
        $content = (string) $xlsx;
        
        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="template_import_produk.xlsx"',
            'Content-Length' => strlen($content)
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        $file = $request->file('file');
        
        if (! $xlsx = SimpleXLSX::parse($file->getPathname())) {
            return response()->json([
                'message' => 'Gagal membaca file Excel',
                'error' => SimpleXLSX::parseError()
            ], 400);
        }

        $rows = $xlsx->rows();
        
        if (count($rows) <= 1) {
            return response()->json([
                'message' => 'File Excel kosong atau hanya berisi header'
            ], 400);
        }

        // Hapus row pertama (header)
        array_shift($rows);

        $supplierId = $request->user()->supplier_id;
        $importedCount = 0;

        try {
            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                // Baris ke di Excel = index + 2 (karena array dari 0 dan ada 1 row header + row mulai 1 indexnya)
                $excelRowNumber = $index + 2;

                // Pastikan minimal ada data nama dan satuan
                if (empty(trim($row[0])) && empty(trim($row[2]))) {
                    continue; // Skip baris yang benar-benar kosong
                }

                if (empty(trim($row[0]))) {
                    throw ValidationException::withMessages(['file' => "Baris $excelRowNumber: Nama produk tidak boleh kosong."]);
                }
                
                $costPrice = isset($row[3]) && is_numeric($row[3]) ? (float) $row[3] : 0;
                $price = isset($row[4]) && is_numeric($row[4]) ? (float) $row[4] : 0;
                $stockQty = isset($row[5]) && is_numeric($row[5]) ? (int) $row[5] : 0;
                
                if ($price <= 0) {
                     throw ValidationException::withMessages(['file' => "Baris $excelRowNumber: Harga jual (Harga Jual) harus lebih besar dari 0."]);
                }

                $product = Product::create([
                    'supplier_id' => $supplierId,
                    'name'        => trim($row[0]),
                    'category'    => isset($row[1]) ? trim($row[1]) : null,
                    'unit'        => isset($row[2]) ? trim($row[2]) : 'Pcs',
                    'cost_price'  => $costPrice,
                    'price'       => $price,
                ]);

                // Create stock record
                Stock::create([
                    'product_id' => $product->id,
                    'qty'        => $stockQty > 0 ? $stockQty : 0,
                ]);

                // Mutasi stok awal
                if ($stockQty > 0) {
                    StockMutation::create([
                        'supplier_id' => $supplierId,
                        'product_id'  => $product->id,
                        'type'        => 'in',
                        'qty'         => $stockQty,
                        'old_qty'     => 0,
                        'new_qty'     => $stockQty,
                        'reference_type' => 'Stock Initialization',
                        'reference_id'   => null,
                        'note'        => 'Saldo awal stok import',
                        'transaction_date' => now(),
                    ]);
                }

                $importedCount++;
            }

            DB::commit();

            return response()->json([
                'message' => "Berhasil import $importedCount produk."
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat import data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
