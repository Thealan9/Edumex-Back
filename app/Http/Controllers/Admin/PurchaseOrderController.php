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
            ->where('warehouseman_id', auth()->id())
            ->latest()
            ->get();

        return response()->json($orders);
    }
    public function pendingDispatches()
    {
        $orders = PurchaseOrder::with(['items.book'])
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    public function store(Request $request) {
        $request->validate([
            'warehouseman_id' => 'required|exists:users,id',
            'supplier_name' => 'required',
            'items' => 'required|array|min:1'
        ]);

        return DB::transaction(function () use ($request) {
            $po = PurchaseOrder::create([
                'po_number' => 'OC-' . time(),
                'admin_id' => auth()->id(),
                'warehouseman_id' => $request->warehouseman_id,
                'supplier_name' => $request->supplier_name,
                'notes' => $request->notes,
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
