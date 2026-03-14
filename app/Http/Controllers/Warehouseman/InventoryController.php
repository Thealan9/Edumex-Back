<?php
namespace App\Http\Controllers\Warehouseman;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Http\Requests\Warehouseman\StoreMovementRequest;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function store(StoreMovementRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data) {
            $location = Location::lockForUpdate()->find($data['location_id']);
            $book = Book::find($data['book_id']);

            if (in_array($data['type'], ['input', 'return'])) {
                if (!$location->hasSpaceFor($data['quantity'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Capacidad insuficiente en el estante {$location->code}. Espacio disponible: " . ($location->max_capacity - $location->current_capacity)
                    ], 409);
                }
            }

            if ($data['type'] === 'output') {
                $currentStock = Inventory::where('book_id', $data['book_id'])
                    ->where('location_id', $data['location_id'])
                    ->first();

                if (!$currentStock || $currentStock->quantity < $data['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente en esta ubicación para realizar la salida."
                    ], 422);
                }
            }

            $movement = InventoryMovement::create([
                'book_id'     => $data['book_id'],
                'user_id'     => auth()->id(),
                'location_id' => $data['location_id'],
                'type'        => $data['type'],
                'quantity'    => $data['quantity'],
                'description' => $data['description']
            ]);

            $inventory = Inventory::firstOrNew([
                'book_id'     => $data['book_id'],
                'location_id' => $data['location_id']
            ]);

            if (in_array($data['type'], ['input', 'return'])) {
                $inventory->quantity += $data['quantity'];
                $location->current_capacity += $data['quantity'];
            } else {
                $inventory->quantity -= $data['quantity'];
                $location->current_capacity -= $data['quantity'];
            }

            $inventory->save();
            $location->save();

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de almacén procesado con éxito',
                'data' => [
                    'movement' => $movement,
                    'new_stock_at_location' => $inventory->quantity
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
