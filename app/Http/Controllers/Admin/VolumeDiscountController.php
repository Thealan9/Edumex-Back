<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VolumeDiscount;
use App\Http\Requests\Admin\StoreDiscountRequest;
use Illuminate\Http\Request;

class VolumeDiscountController extends Controller
{
    public function index()
    {
        $discounts = VolumeDiscount::orderBy('min_quantity', 'asc')->get();
        return response()->json(['success' => true, 'data' => $discounts], 200);
    }

    public function store(StoreDiscountRequest $request)
    {
        // Validar que no haya solapamiento de rangos (Opcional pero recomendado)
        $exists = VolumeDiscount::where('min_quantity', $request->min_quantity)
            ->where('is_institutional', $request->is_institutional)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una regla para esta cantidad mínima.'
            ], 409);
        }

        $discount = VolumeDiscount::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Regla de descuento creada',
            'data' => $discount
        ], 201);
    }

    public function destroy(VolumeDiscount $volumeDiscount)
    {
        $volumeDiscount->delete();
        return response()->json(['success' => true, 'message' => 'Descuento eliminado'], 200);
    }
}
