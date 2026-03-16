<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\VolumeDiscount;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::where('active', true)->get();

        $discounts = VolumeDiscount::orderBy('min_quantity', 'asc')->get();
        $catalog = $books->map(function ($book) {
            return [
                'id' => $book->id,
                'title' => $book->title,
                'isbn' => $book->isbn,
                'level' => $book->level,
                'price_package' => $book->price_package,
                'units_per_package' => $book->units_per_package,
                'total_stock' => (int)$book->total_stock,
                'price_unit' => (float)$book->price_unit,
                'image_url' => $book->image_url,
                'category' => $book->category,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $catalog,
            'global_discounts' => $discounts
        ], 200);
    }

    public function show($id)
    {
        $book = Book::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $book,
            'total_stock' => (int)$book->total_stock,
        ]);
    }

}
