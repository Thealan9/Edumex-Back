<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(
            User::orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|min:8',
            'role'          => 'required|in:admin,user,warehouseman',
            'customer_type' => 'required|in:individual,institutional',
            'active'        => 'boolean',
            'phone'         => 'required|string|max:10',

            'institution_name' => 'required_if:customer_type,institutional|nullable|string|max:255',
            'tax_id'           => 'required_if:customer_type,institutional|nullable|string|unique:users,tax_id|max:12',
            'address'          => 'required_if:customer_type,institutional|nullable|string',
            'postal_code'      => 'required_if:customer_type,institutional|nullable|string|max:10',
        ]);

        $user = User::create([
            'name'             => $data['name'],
            'last_name'        => $data['last_name'],
            'email'            => $data['email'],
            'password'         => Hash::make($data['password']),
            'role'             => $data['role'],
            'customer_type'    => $data['customer_type'],
            'institution_name' => $data['institution_name'] ?? null,
            'tax_id'           => $data['tax_id'] ?? null,
            'phone'            => $data['phone'],
            'address'          => $data['address'] ?? null,
            'postal_code'      => $data['postal_code'] ?? null,
            'active'           => $data['active'] ?? true,
        ]);

        return response()->json(['message' => 'Usuario registrado', 'user' => $user], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'role'          => 'required|in:admin,user,warehouseman',
            'customer_type' => 'required|in:individual,institutional',
            'active'        => 'boolean',
            'phone'         => 'required|string|max:10',

            'institution_name' => 'required_if:customer_type,institutional|nullable|string|max:255',
            'tax_id'           => 'required_if:customer_type,institutional|nullable|string|max:12|unique:users,tax_id,' . $user->id,
            'address'          => 'required_if:customer_type,institutional|nullable|string',
            'postal_code'      => 'required_if:customer_type,institutional|nullable|string|max:10',

        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Usuario actualizado',
            'user' => $user
        ]);
    }

    public function show($id)
    {
        return response()->json(User::findOrFail($id));
    }

    public function destroy(User $user, Request $request)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta'], 422);
        }

        $user->delete();
        return response()->json(['message' => 'Usuario eliminado']);
    }

    public function toggleActive(User $user, Request $request)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'No puedes bloquear tu propia cuenta'], 422);
        }

        $user->update(['active' => !$user->active]);

        return response()->json([
            'message' => $user->active ? 'Usuario activado' : 'Usuario bloqueado',
            'user' => $user
        ]);
    }

    public function changePassword(User $user, Request $request)
    {
        $request->validate(['password' => 'required|min:8']);

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Contraseña actualizada']);
    }

    public function getWarehousemen()
    {
        return User::where('role', 'warehouseman')
            ->where('active', 1)
            ->get(['id', 'name']);
    }
}
