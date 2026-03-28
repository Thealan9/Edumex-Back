<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ebook;
use App\Http\Requests\Admin\StoreEbookRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class EbookController extends Controller
{
    public function index(Request $request)
    {
        $query = Ebook::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('autor', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        $ebooks = $query->latest()->paginate(15);
        return response()->json($ebooks);
    }

    public function store(StoreEbookRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('ebooks', 'public');
        }

        $ebook = Ebook::create($data);

        return response()->json([
            'message' => 'Ebook creado con éxito',
            'ebook' => $ebook
        ], 200);
    }

    public function show(Ebook $ebook)
    {
        return $ebook;
    }

    public function update(StoreEbookRequest $request, Ebook $ebook)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Borrar imagen anterior si existe
            if ($ebook->image_path) {
                Storage::disk('public')->delete($ebook->image_path);
            }
            $data['image_path'] = $request->file('image')->store('ebooks', 'public');
        }

        $ebook->update($data);

        return response()->json(['message' => 'Ebook actualizado', 'ebook' => $ebook]);
    }

    public function destroy(Ebook $ebook)
    {
        $ebook->delete();
        return response()->json(['message' => 'Ebook enviado a papelera']);
    }

    public function toggleStatus(Ebook $ebook)
    {
        $ebook->update(['active' => !$ebook->active]);
        return response()->json(['message' => 'Estado actualizado']);
    }
}
