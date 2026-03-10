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
        // Añadimos customer_type y tax_id a la selección para el front
        return response()->json(
            User::select('id', 'name', 'last_name', 'email', 'role', 'customer_type', 'tax_id', 'active', 'created_at')
                ->orderBy('created_at', 'desc')
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
            'tax_id'        => 'nullable|string|unique:users,tax_id', // DNI/RUC para instituciones
            'active'        => 'boolean'
        ]);

        $user = User::create([
            'name'          => $data['name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'password'      => Hash::make($data['password']),
            'role'          => $data['role'],
            'customer_type' => $data['customer_type'],
            'tax_id'        => $data['tax_id'],
            'active'        => $data['active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'user' => $user
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'role'          => 'required|in:admin,user,warehouseman',
            'customer_type' => 'required|in:individual,institutional',
            'tax_id'        => 'nullable|string|unique:users,tax_id,' . $user->id,
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Usuario actualizado',
            'user' => $user
        ]);
    }

    public function show($id)
    {
        // Usamos findOrFail para asegurar que devuelva 404 si no existe
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
}
