<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $request->user()->role ?? 'guest';

        $data = parent::toArray($request);

        // Warehouse tidak boleh melihat harga sama sekali (atau cost_price)
        if ($role === 'warehouse') {
            unset($data['price']);
            unset($data['cost_price']);
        }

        // Driver tidak boleh melihat harga
        if ($role === 'driver') {
            unset($data['price']);
            unset($data['cost_price']);
        }

        return $data;
    }
}
