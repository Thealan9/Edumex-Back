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

        // Traemos todos los libros con sus movimientos previos y actuales
        $report = Book::all()->map(function($book) use ($startDate, $endDate) {

            // Stock Inicial: Movimientos antes de la fecha de inicio
            $stockInicial = $book->movements()
                ->where('created_at', '<', $startDate)
                ->selectRaw("SUM(CASE WHEN type IN ('input', 'return') THEN quantity ELSE -quantity END) as total")
                ->value('total') ?? 0;

            // Movimientos dentro del rango (entradas y salidas)
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
        // Reporte rápido de ventas por tipo de cliente (Institucional vs Persona)
        $sales = DB::table('inventory_movements')
            ->join('users', 'inventory_movements::user_id', '=', 'users.id')
            ->where('inventory_movements.type', 'output')
            ->select('users.customer_type', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('users.customer_type')
            ->get();

        return response()->json(['success' => true, 'data' => $sales]);
    }
}
