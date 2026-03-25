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

        $report = Book::all()->map(function($book) use ($startDate, $endDate) {

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

        return response()->json([
            'success' => true,
            'periodo' => $startDate->translatedFormat('F Y'),
            'data'    => $report
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
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $sales = DB::table('order_items')
            ->join('books', 'order_items.book_id', '=', 'books.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select(
                'books.title as titulo',
                'books.isbn',
                DB::raw("SUM(CASE WHEN buy_type = 'unit' THEN quantity ELSE 0 END) as unidades_sueltas"),
                DB::raw("SUM(CASE WHEN buy_type = 'package' THEN quantity ELSE 0 END) as paquetes_vendidos"),
                DB::raw('SUM(order_items.quantity * order_items.price) as subtotal'),
                DB::raw('SUM(order_items.discount) as descuentos'),
                DB::raw('SUM((order_items.quantity * order_items.price) - order_items.discount) as total_neto')
            )
            ->groupBy('books.id', 'books.title', 'books.isbn')
            ->get();

        $totales = [
            'subtotal_general' => $sales->sum('subtotal'),
            'descuentos_general' => $sales->sum('descuentos'),
            'total_general' => $sales->sum('total_neto'),
            'total_unidades' => $sales->sum('unidades_sueltas'),
            'total_paquetes' => $sales->sum('paquetes_vendidos'),
        ];

        return response()->json([
            'success' => true,
            'periodo' => $startDate->translatedFormat('F Y'),
            'data' => $sales,
            'totales' => $totales
        ]);
    }
}
