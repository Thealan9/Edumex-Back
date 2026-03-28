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
            'items.*.id' => 'required|integer',
            'items.*.type' => 'required|in:physical,ebook',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.buy_type' => 'required|in:unit,package',
            'address_id' => 'nullable|exists:addresses,id',
            'address_data' => 'nullable|array',
            'payment_method' => 'required|in:tarjeta,paypal',
            'payment_id' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $subtotal = 0;
            $totalDiscount = 0;
            $hasPhysical = collect($request->items)->contains('type', 'physical');

            if ($hasPhysical && !$request->address_id && !$request->address_data) {
                throw new \Exception("Se requiere una dirección para productos físicos.");
            }

            $shippingDetails = null;
            if ($hasPhysical || $request->address_id || $request->address_data) {
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
                        'external_number'  => $request->address_data['external_number'] ?: 'S/N',
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
            }

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

            $discounts = \App\Models\VolumeDiscount::where('is_institutional', ($user->customer_type === 'institutional'))
                ->orderBy('min_quantity', 'asc')
                ->get();

            foreach ($request->items as $item) {
                if ($item['type'] === 'ebook') {
                    $book = \App\Models\Ebook::findOrFail($item['id']);
                    $unitPrice = $book->price;

                    for ($i = 0; $i < $item['quantity']; $i++) {
                        \App\Models\EbookPurchase::create([
                            'ebook_id' => $book->id,
                            'order_id' => $order->id,
                            'distributor' => $book->supplier,
                            'platform'    => $book->platform,
                            'code' => \App\Models\EbookPurchase::generateCode()
                        ]);
                    }
                } else {
                    $book = Book::findOrFail($item['id']);
                    if ($user->customer_type === 'individual' && $item['buy_type'] === 'package') {
                        throw new \Exception("Acceso denegado para paquetes.");
                    }

                    $unitPrice = ($item['buy_type'] === 'package') ? $book->price_package : $book->price_unit;
                    $unitsToSubtract = ($item['buy_type'] === 'package') ? ($item['quantity'] * ($book->units_per_package ?? 1)) : $item['quantity'];

                    $inventories = Inventory::where('book_id', $book->id)->where('quantity', '>', 0)->orderBy('quantity', 'asc')->get();
                    if ($inventories->sum('quantity') < $unitsToSubtract) {
                        throw new \Exception("Stock insuficiente para: {$book->title}.");
                    }

                    $pending = $unitsToSubtract;
                    foreach ($inventories as $inv) {
                        if ($pending <= 0) break;
                        $take = min($inv->quantity, $pending);
                        $inv->decrement('quantity', $take);
                        \App\Models\Location::where('id', $inv->location_id)->decrement('current_capacity', $take);

                        InventoryMovement::create([
                            'book_id' => $book->id,
                            'location_id' => $inv->location_id,
                            'user_id' => $user->id,
                            'type' => 'output',
                            'quantity' => $take,
                            'description' => "Venta Orden #{$order->id}"
                        ]);
                        $pending -= $take;
                    }
                }

                $itemSubtotal = $unitPrice * $item['quantity'];
                $itemDiscount = 0;

                if ($item['buy_type'] === 'unit') {
                    $applicable = $discounts->first(function ($d) use ($item) {
                        return $item['quantity'] >= $d->min_quantity && (is_null($d->max_quantity) || $item['quantity'] <= $d->max_quantity);
                    });
                    if ($applicable) {
                        $itemDiscount = $itemSubtotal * ($applicable->discount_percentage / 100);
                    }
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id'  => ($item['type'] === 'physical') ? $book->id : null,
                    'ebook_id' => ($item['type'] === 'ebook') ? $book->id : null,
                    'quantity' => $item['quantity'],
                    'price'    => $unitPrice,
                    'discount' => $itemDiscount,
                    'buy_type' => $item['buy_type']
                ]);

                $subtotal += $itemSubtotal;
                $totalDiscount += $itemDiscount;
            }

            $amountAfterDiscount = $subtotal - $totalDiscount;
            $shippingCost = ($hasPhysical && $amountAfterDiscount < 299) ? 129 : 0;
            $finalTotal = $amountAfterDiscount + $shippingCost;

            if ($request->payment_method === 'tarjeta') {
                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
                \Stripe\Charge::create([
                    'amount' => (int)($finalTotal * 100),
                    'currency' => 'mxn',
                    'source' => $request->payment_id,
                    'description' => "Compra Edumex - ID: " . $user->id,
                ]);
            }

            $order->update([
                'subtotal'      => $subtotal,
                'discount'      => $totalDiscount,
                'shipping_cost' => $shippingCost,
                'total'         => $finalTotal
            ]);

            if (!$hasPhysical) {
                $order->update(['status' => 'delivered']);
            }
            return response()->json([
                'success' => true,
                'message' => '¡Compra procesada con éxito!',
                'order'   => $order->load('items')
            ], 201);
        });
    }
    public function myOrders(Request $request)
    {
        return Order::with(['items.book', 'items.ebook', 'ebookPurchases.ebook'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function pendingDespatch()
    {
        return Order::where('status', 'paid')
            ->whereHas('items', function ($query) {
                $query->whereNotNull('book_id');
            })
            ->with(['items' => function ($query) {
                $query->whereNotNull('book_id')->with('book');
            }])
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
