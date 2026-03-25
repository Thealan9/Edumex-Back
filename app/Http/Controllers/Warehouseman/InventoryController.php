<?php
namespace App\Http\Controllers\Warehouseman;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Output_order_items;
use App\Models\Output_orders;
use App\Models\PurchaseOrder;
use App\Http\Requests\Warehouseman\StoreMovementRequest;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
class InventoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'book_id' => 'required|exists:books,id',
            'type' => 'required|in:input,output,adjustment,return',
            'description' => 'required|string',
            'reference_id' => 'nullable|integer',
            'item_id' => 'required|integer',
            'reference_type' => 'nullable|string',
            'distributions' => 'required|array|min:1',
            'distributions.*.location_id' => 'required|exists:locations,id',
            'distributions.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($data) {
            $book = Book::find($data['book_id']);
            $movements = [];

            if (in_array($data['type'], ['input', 'return'])) {
                foreach ($data['distributions'] as $dist) {
                    $location = Location::find($dist['location_id']);
                    if (!$location->hasSpaceFor($dist['quantity'])) {
                        return response()->json([
                            'success' => false,
                            'message' => "Capacidad insuficiente en el estante {$location->code} para guardar {$dist['quantity']} unidades."
                        ], 409);
                    }
                }
            }

            foreach ($data['distributions'] as $dist) {
                $location = Location::lockForUpdate()->find($dist['location_id']);

                $movements[] = InventoryMovement::create([
                    'book_id'        => $data['book_id'],
                    'user_id'        => auth()->id(),
                    'location_id'    => $dist['location_id'],
                    'type'           => $data['type'],
                    'quantity'       => $dist['quantity'],
                    'description'    => $data['description'],
                    'reference_id'   => $data['reference_id'] ?? null,
                    'reference_type' => $data['reference_type'] ?? null
                ]);

                $inventory = Inventory::firstOrNew([
                    'book_id'     => $data['book_id'],
                    'location_id' => $dist['location_id']
                ]);

                if (in_array($data['type'], ['input', 'return'])) {
                    $inventory->quantity += $dist['quantity'];
                    $location->current_capacity += $dist['quantity'];
                } else {
                    $inventory->quantity -= $dist['quantity'];
                    $location->current_capacity -= $dist['quantity'];
                }

                $inventory->save();
                $location->save();
            }

            if (($data['reference_type'] ?? null) === 'purchase_order' && ($data['reference_id'] ?? null)) {
                $po = PurchaseOrder::with('items')->find($data['reference_id']);

                if ($po) {
                    $receivedTotals = InventoryMovement::where('reference_id', $po->id)
                        ->where('reference_type', 'purchase_order')
                        ->select('book_id', DB::raw('SUM(quantity) as total'))
                        ->groupBy('book_id')
                        ->get()->pluck('total', 'book_id');
                    PurchaseOrderItem::where('id', $data['item_id'])->update(['status' => 'completed']);

                    $hasPendingItems = PurchaseOrderItem::where('purchase_order_id', $po->id)
                        ->where('status', '!=', 'completed')
                        ->exists();

                    if (!$hasPendingItems) {
                        $po->update(['status' => 'received']);
                    }
                }
            }

            if (($data['reference_type'] ?? null) === 'output_order' && ($data['reference_id'] ?? null)) {
                $oo = Output_orders::with('items')->find($data['reference_id']);
                if ($oo) {
                    $withdrawnTotals = InventoryMovement::where('reference_id', $oo->id)
                        ->where('reference_type', 'output_order')
                        ->select('book_id', DB::raw('SUM(quantity) as total'))
                        ->groupBy('book_id')
                        ->get()->pluck('total', 'book_id');
                    Output_order_items::where('id', $data['item_id'])->update(['status' => 'completed']);

                    $hasPendingOutputs = Output_order_items::where('output_order_id', $oo->id)
                        ->where('status', '!=', 'completed')
                        ->exists();

                    if (!$hasPendingOutputs) {
                        $oo->update(['status' => 'processed']);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Movimientos procesados con éxito',
                'data' => [
                    'movements' => $movements
                ]
            ], 201);
        });
    }

    private function isOrderComplete($order, $totals)
    {
        foreach ($order->items as $item) {
            $currentAmount = $totals[$item->book_id] ?? 0;
            if ($currentAmount < $item->quantity) {
                return false;
            }
        }
        return true;
    }

    public function getLocationsByBook($book_id)
    {
        $locations = Inventory::where('book_id', $book_id)
            ->where('quantity', '>', 0)
            ->with('location')
            ->get()
            ->map(function($inv) {
                return [
                    'id' => $inv->location->id,
                    'code' => $inv->location->code,
                    'current_stock' => $inv->quantity,
                    'max_capacity' => $inv->location->max_capacity,
                    'current_capacity' => $inv->location->current_capacity
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    public function index()
    {
        return InventoryMovement::with(['book', 'user', 'location'])
            ->latest()
            ->paginate(20);
    }
}
