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
use Stripe\Stripe;
use Stripe\PaymentIntent;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:books,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.buy_type' => 'required|in:unit,package',
            'address_id' => 'required_without:address_data|nullable|exists:addresses,id',
            'address_data' => 'required_without:address_id|nullable|array',
            'payment_method' => 'required|in:tarjeta,paypal',
            'payment_id' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $subtotal = 0;
            $totalDiscount = 0;

            $discounts = \App\Models\VolumeDiscount::where('is_institutional', ($user->customer_type === 'institutional'))
                ->orderBy('min_quantity', 'asc')
                ->get();

            if ($request->address_id) {
                $address = Addresses::where('user_id', $user->id)->findOrFail($request->address_id);
            } else {
                if ($request->address_data['is_default']) {
                    Addresses::where('user_id', $user->id)->update(['is_default' => false]);
                }
                $address = Addresses::create([
                    'user_id'          => $user->id,
                    'recipient_name'   => $request->address_data['recipient_name'],
                    'recipient_phone'  => $request->address_data['recipient_phone'],
                    'postal_code'      => $request->address_data['postal_code'],
                    'state'            => $request->address_data['state'],
                    'municipality'     => $request->address_data['municipality'],
                    'locality'         => $request->address_data['locality'] ?: $request->address_data['municipality'],
                    'neighborhood'     => $request->address_data['neighborhood'],
                    'street'           => $request->address_data['street'],
                    'external_number'  => !empty($request->address_data['external_number']) ? $request->address_data['external_number'] : 'S/N',
                    'internal_number'  => $request->address_data['internal_number'] ?: null,
                    'references'       => $request->address_data['references'] ?: null,
                    'is_default'       => $request->address_data['is_default'],
                ]);
            }

            $shippingDetails = [
                'recipient' => $address->recipient_name,
                'phone' => $address->recipient_phone,
                'full_address' => "{$address->street} #{$address->external_number}" . ($address->internal_number ? " Int. {$address->internal_number}" : ""),
                'colonia' => $address->neighborhood,
                'ciudad' => $address->locality,
                'municipio' => $address->municipality,
                'estado' => $address->state,
                'cp' => $address->postal_code,
                'references' => $address->references
            ];

            $order = Order::create([
                'user_id'          => $user->id,
                'shipping_details' => $shippingDetails,
                'status'           => 'paid',
                'subtotal'         => 0,
                'discount'         => 0,
                'shipping_cost'    => 0,
                'total'            => 0,
                'payment_method'   => $request->payment_method,
                'payment_id'       => $request->payment_id
            ]);

            foreach ($request->items as $item) {
                $book = Book::findOrFail($item['id']);

                if ($user->customer_type === 'individual' && $item['buy_type'] === 'package') {
                    throw new \Exception("Acceso denegado para compra de paquetes.");
                }

                $unitsToSubtract = ($item['buy_type'] === 'package')
                    ? ($item['quantity'] * ($book->units_per_package ?? 1))
                    : $item['quantity'];

                $inventories = Inventory::where('book_id', $book->id)
                    ->where('quantity', '>', 0)
                    ->orderBy('quantity', 'asc')
                    ->get();

                if ($inventories->sum('quantity') < $unitsToSubtract) {
                    throw new \Exception("Stock insuficiente para: {$book->title}. Se requieren {$unitsToSubtract} unidades.");
                }

                $pendingToTake = $unitsToSubtract;
                foreach ($inventories as $inv) {
                    if ($pendingToTake <= 0) break;
                    $take = min($inv->quantity, $pendingToTake);
                    $inv->decrement('quantity', $take);
                    \App\Models\Location::where('id', $inv->location_id)->decrement('current_capacity', $take);

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

                $unitPrice = ($item['buy_type'] === 'package') ? $book->price_package : $book->price_unit;
                $itemSubtotal = $unitPrice * $item['quantity'];

                $itemDiscount = 0;
                if ($item['buy_type'] === 'unit') {
                    $applicableDiscount = $discounts->first(function ($d) use ($item) {
                        return $item['quantity'] >= $d->min_quantity &&
                            (is_null($d->max_quantity) || $item['quantity'] <= $d->max_quantity);
                    });

                    if ($applicableDiscount) {
                        $itemDiscount = $itemSubtotal * ($applicableDiscount->discount_percentage / 100);
                    }
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id'  => $book->id,
                    'quantity' => $item['quantity'],
                    'price'    => $unitPrice,
                    'discount' => $itemDiscount,
                    'buy_type' => $item['buy_type']
                ]);

                $subtotal += $itemSubtotal;
                $totalDiscount += $itemDiscount;
            }

            $amountAfterDiscount = $subtotal - $totalDiscount;
            $shippingCost = ($amountAfterDiscount >= 299) ? 0 : 129;
            $finalTotal = $amountAfterDiscount + $shippingCost;

            if ($request->payment_method === 'stripe') {
                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
                try {
                    $charge = \Stripe\Charge::create([
                        'amount' => (int)($finalTotal * 100),
                        'currency' => 'mxn',
                        'source' => $request->payment_id,
                        'description' => "Compra de Libros - Usuario ID: " . auth()->id(),
                    ]);

                    if ($charge->status !== 'succeeded') {
                        throw new \Exception("El cargo no pudo ser procesado por Stripe.");
                    }

                } catch (\Stripe\Exception\CardException $e) {
                    throw new \Exception("Tarjeta rechazada: " . $e->getError()->message);
                } catch (\Exception $e) {
                    throw new \Exception("Error al procesar el pago con Stripe: " . $e->getMessage());
                }
            }

            $order->update([
                'subtotal'      => $subtotal,
                'discount'      => $totalDiscount,
                'shipping_cost' => $shippingCost,
                'total'         => $finalTotal
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Compra procesada con éxito!',
                'order'   => $order->load('items.book')
            ], 201);
        });
    }

    public function myOrders(Request $request)
    {
        return Order::with(['items.book'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function pendingDespatch()
    {
        return Order::with(['items.book'])
            ->where('status', 'paid')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function dispatch(Request $request, $id)
    {
        $request->validate([
            'tracking_number' => 'required|string',
            'tracking_company' => 'required|string'
        ]);

        $order = Order::findOrFail($id);
        $order->update([
            'status' => 'shipped',
            'tracking_number' => $request->tracking_number,
            'tracking_company' => $request->tracking_company,
            'shipped_at' => now()
        ]);

        return response()->json(['message' => 'Guía registrada y pedido enviado']);
    }

}
