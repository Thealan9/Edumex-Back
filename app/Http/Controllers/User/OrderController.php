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
            'address_data' => 'required|array',
            'address_data.recipient_name' => 'required|string',
            'address_data.recipient_phone' => 'required|string',
            'address_data.postal_code' => 'required|string',
            'address_data.state' => 'required|string',
            'address_data.municipality' => 'required|string',
            'address_data.neighborhood' => 'required|string',
            'address_data.street' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $total = 0;

            // 1. Crear la dirección con salvaguardas para campos vacíos (Evita errores de DB)
            $address = Addresses::create([
                'user_id'          => $user->id,
                'recipient_name'   => $request->address_data['recipient_name'],
                'recipient_phone'  => $request->address_data['recipient_phone'],
                'postal_code'      => $request->address_data['postal_code'],
                'state'            => $request->address_data['state'],
                'municipality'     => $request->address_data['municipality'],
                'neighborhood'     => $request->address_data['neighborhood'],
                'locality'         => $request->address_data['locality'] ?: $request->address_data['neighborhood'],
                'street'           => $request->address_data['street'],
                'external_number'  => !empty($request->address_data['external_number']) ? $request->address_data['external_number'] : 'S/N',
                'internal_number'  => $request->address_data['internal_number'] ?: null,
                'references'       => $request->address_data['references'] ?: null,
                'is_default'       => $request->address_data['is_default'] ?? false,
            ]);

            // 2. Crear el Snapshot (La foto fija de la dirección en la orden)
            $shippingDetails = [
                'recipient' => $address->recipient_name,
                'phone' => $address->recipient_phone,
                'full_address' => "{$address->street} #{$address->external_number}" . ($address->internal_number ? " Int. {$address->internal_number}" : ""),
                'colonia' => $address->neighborhood,
                'cp' => $address->postal_code,
                'location' => "{$address->locality}, {$address->municipality}, {$address->state}",
                'references' => $address->references
            ];

            // 3. Crear la Orden
            $order = Order::create([
                'user_id' => $user->id,
                'shipping_details' => $shippingDetails,
                'status'  => 'completed',
                'total'   => 0
            ]);

            foreach ($request->items as $item) {
                $book = Book::findOrFail($item['id']);

                // Validación de Negocio: Individuales no compran paquetes
                if ($user->customer_type === 'individual' && $item['buy_type'] === 'package') {
                    throw new \Exception("Acceso denegado para compra de paquetes.");
                }

                // Multiplicador de stock (Trazabilidad real)
                $unitsToSubtract = ($item['buy_type'] === 'package')
                    ? ($item['quantity'] * ($book->units_per_package ?? 1))
                    : $item['quantity'];

                $pendingToTake = $unitsToSubtract;

                // 4. Lógica de Descuento por Estantes
                $inventories = Inventory::where('book_id', $book->id)
                    ->where('quantity', '>', 0)
                    ->orderBy('quantity', 'asc') // Estrategia de optimización de espacio
                    ->get();

                if ($inventories->sum('quantity') < $unitsToSubtract) {
                    throw new \Exception("Stock insuficiente para: {$book->title}. Se requieren {$unitsToSubtract} unidades.");
                }

                foreach ($inventories as $inv) {
                    if ($pendingToTake <= 0) break;

                    $take = min($inv->quantity, $pendingToTake);
                    $inv->decrement('quantity', $take);

                    // Auditoría de movimiento
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

                // 5. Registrar el ítem con precio histórico
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

            // 6. Finalizar monto total
            $order->update(['total' => $total]);

            return response()->json([
                'success' => true,
                'message' => '¡Compra procesada con éxito!',
                'order' => $order
            ], 201);
        });
    }
}
