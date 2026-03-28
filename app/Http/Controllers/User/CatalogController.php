<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Ebook;
use App\Models\VolumeDiscount;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        // Traemos libros físicos activos
        $physicalBooks = Book::where('active', true)->get()->map(function ($book) {
            return [
                'id' => $book->id,
                'title' => $book->title,
                'isbn' => $book->isbn,
                'level' => $book->level,
                'price_unit' => (float)$book->price_unit,
                'price_package' => (float)$book->price_package,
                'units_per_package' => $book->units_per_package,
                'total_stock' => (int)$book->total_stock,
                'image_url' => $book->image_url,
                'category' => $book->category,
                'autor' => $book->autor,
                'type' => 'physical' // <--- Identificador
            ];
        });

        // Traemos Ebooks activos
        $ebooks = Ebook::where('active', true)->get()->map(function ($ebook) {
            return [
                'id' => $ebook->id,
                'title' => $ebook->title,
                'isbn' => $ebook->isbn,
                'level' => $ebook->level,
                'price_unit' => (float)$ebook->price, // Mismo campo para el front
                'price_package' => null,
                'units_per_package' => null,
                'total_stock' => 999, // Stock virtual infinito
                'image_url' => $ebook->image_url,
                'category' => $ebook->category,
                'autor' => $ebook->autor,
                'type' => 'ebook' // <--- Identificador
            ];
        });

        // Unimos ambos arrays
        $catalog = $physicalBooks->concat($ebooks);

        return response()->json([
            'success' => true,
            'data' => $catalog,
        ], 200);
    }

    public function show($id, Request $request)
    {
        // Si la URL trae un query ?type=ebook buscamos en Ebooks, si no en Books
        if($request->query('type') === 'ebook') {
            $item = Ebook::findOrFail($id);
            $stock = 999;
        } else {
            $item = Book::findOrFail($id);
            $stock = (int)$item->total_stock;
        }

        return response()->json([
            'success' => true,
            'data' => $item,
            'total_stock' => $stock,
            'type' => $request->query('type') ?? 'physical'
        ]);
    }

    public function discounts(Request $request){
        $isInstitutional = $request->user()->customer_type === 'institutional';

        $rules = VolumeDiscount::where('is_institutional', $isInstitutional)
            ->orderBy('min_quantity', 'asc')
            ->get();

        return response()->json($rules);
    }

}
