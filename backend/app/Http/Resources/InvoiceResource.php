<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $request->user()->role;
        $isOwnerOrAdmin = in_array($role, ['owner', 'admin']);

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order' => new OrderResource($this->whenLoaded('order')),
            'total' => $this->total,
            // Sembunyikan Cost dan Profit dari Customer/Driver/Warehouse
            'total_cost' => $this->when($isOwnerOrAdmin, $this->total_cost),
            'total_profit' => $this->when($isOwnerOrAdmin, $this->total_profit),
            'status' => $this->status,
            'due_date' => $this->due_date,
            'payment_method' => $this->payment_method,
            'payment_proof' => $this->payment_proof ? (str_starts_with($this->payment_proof, 'http') ? $this->payment_proof : url('storage/' . $this->payment_proof)) : null,
            'payment_proof_path' => $this->payment_proof,
            // Sembunyikan activities dari Customer (hanya owner/admin)
            'activities' => $this->when($isOwnerOrAdmin, $this->whenLoaded('activities')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
