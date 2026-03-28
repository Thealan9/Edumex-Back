<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Http\Requests\Admin\StoreBookRequest;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $books = Book::when($search, function ($query, $search) {
            return $query->where('title', 'like', "%{$search}%")
                ->orWhere('autor', 'like', "%{$search}%"); // <--- ESTO ES LA CLAVE
        })
            ->orderBy('created_at', 'desc')
            ->latest()->paginate(15);

        return response()->json($books);
    }

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
            return response()->json([
                'success' => false,
                'message' => 'Conflicto al registrar el libro. Verifique los datos.',
                'error' => $e->getMessage()
            ], 409);
        }
    }

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

    public function update(StoreBookRequest $request, Book $book)
    {
        $book->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Información del libro actualizada',
            'data' => $book
        ], 200);
    }

    public function updateImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $book = Book::findOrFail($id);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('books', 'public');

            $book->image_path = $path;
            $book->save();

            return response()->json([
                'success' => true,
                'image_url' => asset('storage/' . $path),
                'image_path' => $path
            ]);
        }

        return response()->json(['success' => false], 400);
    }
}
