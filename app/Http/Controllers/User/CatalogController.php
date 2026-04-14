<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Ebook;
use App\Models\VolumeDiscount;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $category = $request->query('category', 'Todos');
        $level = $request->query('level', 'Todos');
        $type = $request->query('type', 'Todos');
        $onlyStock = $request->query('onlyStock') === 'true';

        $physicalBooks = collect();

        if ($type === 'Todos' || $type === 'physical') {
            $query = Book::where('active', true);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('autor', 'like', "%{$search}%");
                });
            }
            if ($category !== 'Todos') $query->where('category', $category);
            if ($level !== 'Todos') $query->where('level', $level);

            $physicalBooks = $query->get()
                ->filter(function ($book) use ($onlyStock) {
                    if ($onlyStock && $book->total_stock <= 0) {
                        return false;
                    }
                    return true;
                })
                ->map(function ($book) {
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
                        'type' => 'physical'
                    ];
                });
        }

        $ebooks = collect();

        if ($type === 'Todos' || $type === 'ebook') {
            $query = Ebook::where('active', true);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('autor', 'like', "%{$search}%");
                });
            }
            if ($category !== 'Todos') $query->where('category', $category);
            if ($level !== 'Todos') $query->where('level', $level);

            $ebooks = $query->get()->map(function ($ebook) {
                return [
                    'id' => $ebook->id,
                    'title' => $ebook->title,
                    'isbn' => $ebook->isbn,
                    'level' => $ebook->level,
                    'price_unit' => (float)$ebook->price,
                    'price_package' => null,
                    'units_per_package' => null,
                    'total_stock' => 999,
                    'image_url' => $ebook->image_url,
                    'category' => $ebook->category,
                    'autor' => $ebook->autor,
                    'type' => 'ebook'
                ];
            });
        }

        $catalog = $physicalBooks->concat($ebooks);

        $page = (int) $request->query('page', 1);
        $perPage = 12;
        $total = $catalog->count();

        $items = $catalog->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query()
        ]);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total()
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
