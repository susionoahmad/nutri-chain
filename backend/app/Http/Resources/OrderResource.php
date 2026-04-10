<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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

        // Jika relation `items` sudah di muat (loaded), kita balut dengan OrderItemResource
        if ($this->relationLoaded('items')) {
            $data['items'] = OrderItemResource::collection($this->items);
        }

        // Jika relation `activities` sudah di muat, kita masukkan ke response
        if ($this->relationLoaded('activities')) {
            $data['activities'] = $this->activities;
        }

        // Sembunyikan field yang berhubungan dengan harga dari Warehouse dan Driver
        if (in_array($role, ['warehouse', 'driver'])) {
            unset($data['total_amount']);
            
            // Note: Data Invoice biasanya tidak dikirim ke warehouse/driver, tapi jika ada, kita buang
            if (isset($data['invoice'])) {
                unset($data['invoice']);
            }
        }

        return $data;
    }
}
