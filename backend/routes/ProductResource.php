<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $request->user()?->role;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->unit,
            'stock' => (float) ($this->stock ?? 0),
            
            // Price ditampilkan untuk Owner (Full), Admin (Edit), dan Customer (Order).
            // Warehouse dan Driver tidak memerlukan info harga.
            'price' => $this->when(in_array($role, ['owner', 'admin', 'customer']), (float) $this->price),
            
            // HPP / Modal: Strictly hanya untuk Owner.
            'cost_price' => $this->when($role === 'owner', (float) $this->cost_price),
            
            'supplier_id' => $this->supplier_id,
        ];
    }
}