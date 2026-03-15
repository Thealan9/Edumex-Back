<?php
namespace App\Http\Controllers\Warehouseman;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\PurchaseOrder;
use App\Http\Requests\Warehouseman\StoreMovementRequest;
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
                        ->select('book_id', DB::raw('SUM(quantity) as total_received'))
                        ->groupBy('book_id')
                        ->get()
                        ->pluck('total_received', 'book_id');

                    $isFullyReceived = true;
                    foreach ($po->items as $item) {
                        $receivedAmount = $receivedTotals[$item->book_id] ?? 0;
                        if ($receivedAmount < $item->quantity) {
                            $isFullyReceived = false;
                            break;
                        }
                    }

                    if ($isFullyReceived) {
                        $po->update(['status' => 'received']);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Movimientos procesados con éxito',
                'data' => [
                    'movements' => $movements,
                    'is_po_closed' => isset($po) ? ($po->status === 'received') : null
                ]
            ], 201);
        });
    }

    public function index()
    {
        return InventoryMovement::with(['book', 'user', 'location'])
            ->latest()
            ->paginate(20);
    }
}
