<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Http\Requests\Admin\StoreBookRequest;
use App\Models\Ebook;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;

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
            $data = $request->validated();

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('books', 'public');
            }

            $book = Book::create($data);

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
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($book->image_path) {
                Storage::disk('public')->delete($book->image_path);
            }
            $data['image_path'] = $request->file('image')->store('books', 'public');
        }
        $book->update($data);

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

    public function toggleStatus(Book $book)
    {
        $book->update(['active' => !$book->active]);
        return response()->json(['message' => 'Estado actualizado']);
    }

    public function nameBooks()
    {
        $books = Book::select('id', 'title', 'image_path')
            ->withSum('inventories', 'quantity')
            ->get();

        return response()->json($books);
    }

    public function destroy(Book $book)
    {
        $book->delete();
        return response()->json(['message' => 'Book enviado a papelera']);
    }
}
