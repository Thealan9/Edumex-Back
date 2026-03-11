<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\InventoryMovement;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Addresses;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:books,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.buy_type' => 'required|in:unit,package',
            // address_id es obligatorio si no hay address_data
            'address_id' => 'required_without:address_data|nullable|exists:addresses,id',
            'address_data' => 'required_without:address_id|nullable|array',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $total = 0;

            // --- MANEJO DE DIRECCIÓN ---
            if ($request->address_id) {
                // Caso A: Usar dirección existente
                $address = Addresses::where('user_id', $user->id)->findOrFail($request->address_id);
            } else {
                if ($request->address_data['is_default']) {
                    // Ponemos todas las direcciones anteriores del usuario en false
                    Addresses::where('user_id', $user->id)->update(['is_default' => false]);
                }
                // Caso B: Crear nueva dirección (Solo se ejecuta si no hay address_id)
                $address = Addresses::create([
                    'user_id'          => $user->id,
                    'recipient_name'   => $request->address_data['recipient_name'],
                    'recipient_phone'  => $request->address_data['recipient_phone'],
                    'postal_code'      => $request->address_data['postal_code'],
                    'state'            => $request->address_data['state'],
                    'municipality'     => $request->address_data['municipality'],
                    'locality'         => $request->address_data['locality'] ?: $request->address_data['municipality'], // CIUDAD
                    'neighborhood'     => $request->address_data['neighborhood'], // COLONIA
                    'street'           => $request->address_data['street'],
                    'external_number'  => !empty($request->address_data['external_number']) ? $request->address_data['external_number'] : 'S/N',
                    'internal_number'  => $request->address_data['internal_number'] ?: null,
                    'references'       => $request->address_data['references'] ?: null,
                    'is_default'       => $request->address_data['is_default'],
                ]);
            }

            // 1. Crear el Snapshot Histórico (Para que la orden nunca cambie)
            $shippingDetails = [
                'recipient' => $address->recipient_name,
                'phone' => $address->recipient_phone,
                'full_address' => "{$address->street} #{$address->external_number}" . ($address->internal_number ? " Int. {$address->internal_number}" : ""),
                'colonia' => $address->neighborhood,
                'ciudad' => $address->locality, // <-- Ciudad mapeada correctamente
                'municipio' => $address->municipality,
                'estado' => $address->state,
                'cp' => $address->postal_code,
                'references' => $address->references
            ];

            // 2. Crear la Orden
            $order = Order::create([
                'user_id' => $user->id,
                'shipping_details' => $shippingDetails,
                'status'  => 'completed',
                'total'   => 0
            ]);

            // --- PROCESAMIENTO DE ITEMS E INVENTARIO ---
            foreach ($request->items as $item) {
                $book = Book::findOrFail($item['id']);

                if ($user->customer_type === 'individual' && $item['buy_type'] === 'package') {
                    throw new \Exception("Acceso denegado para compra de paquetes.");
                }

                $unitsToSubtract = ($item['buy_type'] === 'package')
                    ? ($item['quantity'] * ($book->units_per_package ?? 1))
                    : $item['quantity'];

                $pendingToTake = $unitsToSubtract;

                $inventories = Inventory::where('book_id', $book->id)
                    ->where('quantity', '>', 0)
                    ->orderBy('quantity', 'asc')
                    ->get();

                if ($inventories->sum('quantity') < $unitsToSubtract) {
                    throw new \Exception("Stock insuficiente para: {$book->title}. Se requieren {$unitsToSubtract} unidades.");
                }

                foreach ($inventories as $inv) {
                    if ($pendingToTake <= 0) break;

                    $take = min($inv->quantity, $pendingToTake);
                    $inv->decrement('quantity', $take);

                    InventoryMovement::create([
                        'book_id' => $book->id,
                        'location_id' => $inv->location_id,
                        'user_id' => $user->id,
                        'type' => 'output',
                        'quantity' => $take,
                        'description' => "Venta Orden #{$order->id} ({$item['buy_type']})"
                    ]);

                    $pendingToTake -= $take;
                }

                $price = ($item['buy_type'] === 'package') ? $book->price_package : $book->price_unit;

                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id'  => $book->id,
                    'quantity' => $item['quantity'],
                    'price'    => $price,
                    'buy_type' => $item['buy_type']
                ]);

                $total += ($price * $item['quantity']);
            }

            $order->update(['total' => $total]);

            return response()->json([
                'success' => true,
                'message' => '¡Compra procesada con éxito!',
                'order' => $order
            ], 201);
        });
    }
}
