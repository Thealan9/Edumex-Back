<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PurchaseOrderController extends Controller
{
    public function pendingForWarehouse()
    {
        $orders = PurchaseOrder::with(['items.book'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function store(Request $request) {
        return DB::transaction(function () use ($request) {
            $po = PurchaseOrder::create([
                'po_number' => 'OC-' . time(),
                'admin_id' => auth()->id(),
                'supplier_name' => $request->supplier_name,
                'status' => 'pending'
            ]);


            foreach ($request->items as $item) {
                $po->items()->create([
                    'book_id' => $item['book_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost']
                ]);
            }

            return response()->json(['success' => true, 'po' => $po]);
        });
    }
}
