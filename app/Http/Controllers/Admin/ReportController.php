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

        $validOrderStatuses = ['paid', 'shipped', 'in_transit', 'delivered'];

        $latestCosts = DB::table('purchase_order_items')
            ->select('book_id', DB::raw('unit_cost as ultimo_costo'))
            ->whereIn('id', function($query) use ($endDate) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('purchase_order_items')
                    ->where('created_at', '<=', $endDate)
                    ->groupBy('book_id');
            });

        $sales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('books', 'order_items.book_id', '=', 'books.id')
            ->leftJoin('ebooks', 'order_items.ebook_id', '=', 'ebooks.id')
            ->leftJoinSub($latestCosts, 'costos', function ($join) {
                $join->on('order_items.book_id', '=', 'costos.book_id');
            })
            ->whereIn('orders.status', $validOrderStatuses)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select(
                DB::raw("MAX(COALESCE(books.title, ebooks.title)) as titulo"),
                DB::raw("MAX(COALESCE(books.isbn, ebooks.isbn)) as isbn"),

                DB::raw("SUM(CASE
                WHEN order_items.book_id IS NOT NULL AND order_items.buy_type = 'package' THEN order_items.quantity * COALESCE(books.units_per_package, 1)
                WHEN order_items.book_id IS NOT NULL THEN order_items.quantity
                ELSE 0
            END) as unidades_fisicas"),

                DB::raw("SUM(CASE WHEN order_items.ebook_id IS NOT NULL THEN order_items.quantity ELSE 0 END) as unidades_digitales"),
                DB::raw('SUM(order_items.quantity * order_items.price) as venta_bruta'),
                DB::raw('SUM(COALESCE(order_items.discount, 0)) as descuentos_item'),
                DB::raw('SUM((order_items.quantity * order_items.price) - COALESCE(order_items.discount, 0)) as total_neto'),

                DB::raw('SUM(CASE
                WHEN order_items.ebook_id IS NOT NULL
                    THEN ((order_items.quantity * order_items.price) - COALESCE(order_items.discount, 0)) * 0.20
                ELSE
                    ((order_items.quantity * order_items.price) - COALESCE(order_items.discount, 0)) - (
                        CASE
                            WHEN order_items.buy_type = "package" THEN (order_items.quantity * COALESCE(books.units_per_package, 1))
                            ELSE order_items.quantity
                        END * COALESCE(costos.ultimo_costo, 0)
                    )
            END) as ganancia_bruta_item')
            )
            ->groupBy('order_items.book_id', 'order_items.ebook_id')
            ->get();

        $inversionMes = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->whereBetween('purchase_orders.created_at', [$startDate, $endDate])
            ->where('purchase_orders.status', 'received')
            ->sum(DB::raw('purchase_order_items.quantity * purchase_order_items.unit_cost'));

        $ingresosNetos = $sales->sum('total_neto');
        $utilidadTotal = $sales->sum('ganancia_bruta_item');

        $recuperacion = 0;
        if ($inversionMes > 0) {
            $recuperacion = ($ingresosNetos / $inversionMes) * 100;
        }

        $inversionHistorica = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->where('purchase_orders.status', 'received')
            ->sum(DB::raw('purchase_order_items.quantity * purchase_order_items.unit_cost'));

        $ingresosHistoricos = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', $validOrderStatuses)
            ->sum(DB::raw('(order_items.quantity * order_items.price) - COALESCE(order_items.discount, 0)'));

        $saldoPendienteGlobal = $inversionHistorica - $ingresosHistoricos;

        Carbon::setLocale('es');
        return response()->json([
            'success' => true,
            'periodo' => ucfirst($startDate->translatedFormat('F Y')),
            'data' => $sales,
            'totales' => [
                'venta_bruta' => (float)$sales->sum('venta_bruta'),
                'descuentos_totales' => (float)$sales->sum('descuentos_item'),
                'ingresos_totales' => (float)$ingresosNetos,
                'inversion_compras' => (float)$inversionMes,
                'ganancia_ebooks' => (float)$sales->sum(function($item) {
                    return $item->unidades_digitales > 0 ? $item->ganancia_bruta_item : 0;
                }),
                'utilidad_neta' => (float)$utilidadTotal,
                'porcentaje_rentabilidad' => $ingresosNetos > 0 ? ($utilidadTotal / $ingresosNetos) * 100 : 0,
                'porcentaje_recuperacion' => (float)$recuperacion,
                'saldo_pendiente_global' => (float)$saldoPendienteGlobal
            ]
        ]);
    }
}
