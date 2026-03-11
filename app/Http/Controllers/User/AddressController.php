<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Addresses;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        // Retorna todas las direcciones del usuario logueado
        return response()->json($request->user()->addresses()->latest()->get());
    }

    public function destroy(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();
        return response()->json(['message' => 'Dirección eliminada']);
    }
}
