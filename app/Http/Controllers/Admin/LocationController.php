<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreLocationRequest;

class LocationController extends Controller
{
    // Listar todos los estantes con su estado de llenado
    public function index()
    {
        $locations = Location::withSum('inventories as current_capacity', 'quantity')->get();
        return response()->json([
            'success' => true,
            'data' => $locations
        ], 200);
    }

    // Crear un nuevo estante/ubicación
    public function store(StoreLocationRequest $request)
    {
        try {
            $location = Location::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Ubicación creada con éxito',
                'data' => $location
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la ubicación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
