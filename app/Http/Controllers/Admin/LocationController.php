<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreLocationRequest;

class LocationController extends Controller
{

    public function index()
    {
        $locations = Location::withSum('inventories as current_capacity', 'quantity')->get();
        return response()->json([
            'success' => true,
            'data' => $locations
        ], 200);
    }


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

    public function update(StoreLocationRequest $request, Location $location){
        try {
            if(!$location->validateSpace($request->max_capacity)) {
                $location->update($request->validated());

                return response()->json([
                    'success' => true,
                    'message' => 'Ubicación editada correctamente'
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'La cantidad debe ser mayor a la cantidad disponible actual',
                ], 409);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error',
            ], 500);
        }
    }


    public function destroy(Location $location)
    {
        $location->delete();
        return response()->json(['message' => 'Ubicación eliminada']);
    }
}
