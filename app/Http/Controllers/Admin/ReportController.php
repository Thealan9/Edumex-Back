<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class ReportController extends Controller
{
    public function monthlyInventory(Request $request)
    {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Obtener libros físicos
        $booksReport = Book::all()->map(function($book) use ($startDate, $endDate) {
            $stockInicial = $book->movements()
                ->where('created_at', '<', $startDate)
                ->selectRaw("SUM(CASE WHEN type IN ('input', 'return') THEN quantity ELSE -quantity END) as total")
                ->value('total') ?? 0;

            $movimientosMes = $book->movements()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $entradas = $movimientosMes->whereIn('type', ['input', 'return'])->sum('quantity');
            $salidas  = $movimientosMes->whereIn('type', ['output', 'adjustment'])->sum('quantity');
            $stockFinal = $stockInicial + $entradas - $salidas;

            return [
                'tipo'          => 'Físico',
                'isbn'          => $book->isbn,
                'titulo'        => $book->title,
                'nivel'         => $book->level,
                'stock_inicial' => (int)$stockInicial,
                'entradas'      => (int)$entradas,
                'salidas'       => (int)$salidas,
                'stock_final'   => (int)$stockFinal,
                'alerta'        => $stockFinal <= $book->stock_alert
            ];
        });

        Carbon::setLocale('es');
        $periodo = $startDate->translatedFormat('F Y');

        return response()->json([
            'success' => true,
            'periodo' => ucfirst($periodo),
            'data'    => $booksReport
        ]);
    }
    public function salesSummary()
    {
        $sales = DB::table('inventory_movements')
            ->join('users', 'inventory_movements::user_id', '=', 'users.id')
            ->where('inventory_movements.type', 'output')
            ->select('users.customer_type', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('users.customer_type')
            ->get();

        return response()->json(['success' => true, 'data' => $sales]);
    }
    public function getFinancialReport(Request $request)
    {
        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $sales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('books', 'order_items.book_id', '=', 'books.id')
            ->leftJoin('ebooks', 'order_items.ebook_id', '=', 'ebooks.id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select(
                'books.id as book_id',
                DB::raw("COALESCE(books.title, ebooks.title) as titulo"),
                DB::raw("COALESCE(books.isbn, ebooks.isbn) as isbn"),
                DB::raw("SUM(CASE WHEN order_items.book_id IS NOT NULL THEN order_items.quantity ELSE 0 END) as unidades_fisicas"),
                DB::raw("SUM(CASE WHEN order_items.ebook_id IS NOT NULL THEN order_items.quantity ELSE 0 END) as unidades_digitales"),
                DB::raw('SUM((order_items.quantity * order_items.price) - order_items.discount) as total_neto'),
                // Nueva lógica: 20% si es ebook, 100% si es físico (el costo se resta al final)
                DB::raw('SUM(CASE
                WHEN order_items.ebook_id IS NOT NULL THEN ((order_items.quantity * order_items.price) - order_items.discount) * 0.20
                ELSE ((order_items.quantity * order_items.price) - order_items.discount)
            END) as ganancia_bruta_item')
            )
            ->groupBy('books.id', 'titulo', 'isbn')
            ->get();

        // Inversión en libros físicos (Compras recibidas este mes)
        $costosInversion = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->whereBetween('purchase_orders.created_at', [$startDate, $endDate])
            ->where('purchase_orders.status', 'received')
            ->sum(DB::raw('purchase_order_items.quantity * purchase_order_items.unit_cost'));

        $ingresosNetos = $sales->sum('total_neto');
        $gananciaEbooks = $sales->sum(function($item) {
            return $item->unidades_digitales > 0 ? $item->ganancia_bruta_item : 0;
        });

        // Utilidad Final = (Ganancia de Ebooks 20%) + (Ingreso Físico - Costo de Compra)
        $utilidadReal = $gananciaEbooks + ($sales->where('unidades_fisicas', '>', 0)->sum('total_neto') - $costosInversion);

        Carbon::setLocale('es');
        $periodo = $startDate->translatedFormat('F Y');

        return response()->json([
            'success' => true,
            'periodo' => ucfirst($periodo),
            'data' => $sales,
            'totales' => [
                'ingresos_totales' => (float)$ingresosNetos,
                'inversion_compras' => (float)$costosInversion,
                'ganancia_ebooks' => (float)$gananciaEbooks,
                'utilidad_neta' => (float)$utilidadReal,
                'porcentaje_rentabilidad' => $ingresosNetos > 0 ? ($utilidadReal / $ingresosNetos) * 100 : 0
            ]
        ]);
    }}
