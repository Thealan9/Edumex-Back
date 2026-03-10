<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\InventoryMovement;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:books,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $total = 0;

            // 1. Crear cabecera de orden
            $order = Order::create([
                'user_id' => $user->id,
                'status'  => 'completed',
                'total'   => 0
            ]);

            foreach ($request->items as $item) {
                $book = Book::findOrFail($item['id']);
                $pendingQty = $item['quantity'];

                // --- LÓGICA DE TRAZABILIDAD ---
                // Buscamos inventario disponible en estantes
                $inventories = Inventory::where('book_id', $book->id)
                    ->where('quantity', '>', 0)
                    ->orderBy('quantity', 'asc') // Prioriza vaciar estantes con poco stock
                    ->get();

                if ($inventories->sum('quantity') < $pendingQty) {
                    throw new \Exception("Stock insuficiente para: {$book->title}");
                }

                foreach ($inventories as $inv) {
                    if ($pendingQty <= 0) break;

                    $take = min($inv->quantity, $pendingQty);

                    // Restar del estante
                    $inv->decrement('quantity', $take);

                    // Registrar Movimiento de Salida Automático
                    InventoryMovement::create([
                        'book_id' => $book->id,
                        'location_id' => $inv->location_id,
                        'user_id' => $user->id,
                        'type' => 'output',
                        'quantity' => $take,
                        'description' => "Venta automática - Orden #{$order->id}"
                    ]);

                    $pendingQty -= $take;
                }

                // 2. Registrar el ítem en la orden
                // Usamos precio unitario (luego implementamos lógica de paquete)
                $price = $book->price_unit;

                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id'  => $book->id,
                    'quantity' => $item['quantity'],
                    'price'    => $price
                ]);

                $total += ($price * $item['quantity']);
            }

            $order->update(['total' => $total]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido procesado y stock actualizado en estantes',
                'order_id' => $order->id
            ]);
        });
    }
}
