<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Addresses;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->addresses()->latest()->get());
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'recipient_name' => 'required|string',
            'recipient_phone' => 'required|string',
            'postal_code' => 'required|string',
            'state' => 'required|string',
            'municipality' => 'required|string',
            'locality' => 'required|string',
            'neighborhood' => 'required|string',
            'street' => 'required|string',
            'external_number' => 'required|string',
            'internal_number' => 'nullable|string',
            'references' => 'nullable|string',
            'is_default' => 'boolean'
        ]);

        if ($request->is_default) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($data);
        return response()->json($address);
    }

    public function update(Request $request, $id)
    {
        $address = Addresses::where('user_id', auth()->id())->findOrFail($id);

        if ($request->is_default) {
            Addresses::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $address->update($request->all());
        return response()->json($address);
    }

    public function destroy(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();
        return response()->json(['message' => 'Dirección eliminada']);
    }
}
