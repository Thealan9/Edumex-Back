<?php
namespace App\Http\Controllers\Warehouseman;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;

class MovementController extends Controller
{
    /**
     * Listar los movimientos realizados por el warehouseman autenticado.
     */
    public function index(Request $request)
    {
        $movements = InventoryMovement::with(['book:id,title,isbn', 'location:id,code'])
            ->where('user_id', auth()->id()) // Solo ve lo que él ha hecho
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type); // Filtrar por 'input' o 'output'
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15); // Paginación para que la App de Ionic no se sature

        return response()->json([
            'success' => true,
            'data' => $movements
        ], 200);
    }

    /**
     * Ver el detalle de un movimiento específico.
     */
    public function show($id)
    {
        $movement = InventoryMovement::with(['book', 'location', 'user:id,name,last_name'])
            ->find($id);

        if (!$movement) {
            return response()->json([
                'success' => false,
                'message' => 'Movimiento no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $movement
        ], 200);
    }
}
