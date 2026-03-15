<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'po_number' => $this->po_number,
            'supplier' => $this->supplier_name,
            'status' => $this->status,
            'items' => $this->items->map(fn($item) => [
                'book_id' => $item->book_id,
                'title' => $item->book->title,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost
            ]),
            'created_at' => $this->created_at->format('Y-m-d')
        ];
    }
}
