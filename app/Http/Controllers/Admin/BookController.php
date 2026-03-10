<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Http\Requests\Admin\StoreBookRequest;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class BookController extends Controller
{
    // Listar libros con opción de búsqueda básica
    public function index(Request $request)
    {
        $books = Book::when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('isbn', $search);
            })->get();

        return response()->json([
            'success' => true,
            'data' => $books
        ], 200);
    }

    // Guardar un nuevo libro
    public function store(StoreBookRequest $request)
    {
        try {
            $book = Book::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Libro registrado exitosamente en el catálogo',
                'data' => $book
            ], 201);

        } catch (QueryException $e) {
            // Error 409 si hay un conflicto inesperado (ej. ISBN duplicado que saltó el request)
            return response()->json([
                'success' => false,
                'message' => 'Conflicto al registrar el libro. Verifique los datos.',
                'error' => $e->getMessage()
            ], 409);
        }
    }

    // Ver detalle de un libro
    public function show($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Libro no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $book
        ], 200);
    }

    // Actualizar (Cambio de costo/precio manual)
    public function update(Request $request, Book $book)
    {
        // Validación rápida para actualización
        $data = $request->validate([
            'cost' => 'numeric',
            'price_unit' => 'numeric',
            'active' => 'boolean'
        ]);

        $book->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Información del libro actualizada',
            'data' => $book
        ], 200);
    }
}
