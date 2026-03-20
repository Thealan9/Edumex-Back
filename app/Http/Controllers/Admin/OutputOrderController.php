<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Output_orders;
use App\Models\Output_order_items;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OutputOrderController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'reason'      => 'required|string',
            'notes'       => 'nullable|string',
            'items'       => 'required|array|min:1',
            'items.*.book_id'  => 'required|exists:books,id',
            'items.*.location_id'=> 'required|exists:locations,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {

            $orderNumber = 'SAL-' . date('Ymd') . '-' . strtoupper(Str::random(4));

            $order = Output_orders::create([
                'order_number' => $orderNumber,
                'reason'       => $request->reason,
                'notes'        => $request->notes,
                'status'       => 'pending',
                'created_by'      => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                Output_order_items::create([
                    'output_order_id' => $order->id,
                    'book_id'         => $item['book_id'],
                    'location_id'     => $item['location_id'],
                    'quantity'        => $item['quantity'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Orden de salida autorizada con éxito.',
                'order'   => $order->load('items.book')
            ], 201);
        });
    }

    public function index()
    {
        return Output_orders::with(['items.book', 'user'])
            ->latest()
            ->paginate(15);
    }
}
