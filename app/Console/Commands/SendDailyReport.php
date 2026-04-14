<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\DailyFinancialReport;
use Carbon\Carbon;

class SendDailyReport extends Command
{
    protected $signature = 'report:daily';
    protected $description = 'Calcula las finanzas del día y envía el PDF al administrador';

    public function handle()
    {
        $startDate = Carbon::today()->startOfDay();
        $endDate = Carbon::today()->endOfDay();
        $validOrderStatuses = ['paid', 'shipped', 'in_transit', 'delivered'];

        // Consulta de últimos costos
        $latestCosts = DB::table('purchase_order_items')
            ->select('book_id', DB::raw('unit_cost as ultimo_costo'))
            ->whereIn('id', function($query) use ($endDate) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('purchase_order_items')
                    ->where('created_at', '<=', $endDate)
                    ->groupBy('book_id');
            });

        // Consulta de VENTAS (Obtenemos la lista con ->get() para pintar la tabla)
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
                DB::raw("SUM(CASE WHEN order_items.book_id IS NOT NULL AND order_items.buy_type = 'package' THEN order_items.quantity * COALESCE(books.units_per_package, 1) WHEN order_items.book_id IS NOT NULL THEN order_items.quantity ELSE 0 END) as unidades_fisicas"),
                DB::raw("SUM(CASE WHEN order_items.ebook_id IS NOT NULL THEN order_items.quantity ELSE 0 END) as unidades_digitales"),
                DB::raw('SUM(order_items.quantity * order_items.price) as venta_bruta'),
                DB::raw('SUM(COALESCE(order_items.discount, 0)) as descuentos_item'),
                DB::raw('SUM((order_items.quantity * order_items.price) - COALESCE(order_items.discount, 0)) as total_neto'),
                DB::raw('SUM(CASE WHEN order_items.ebook_id IS NOT NULL THEN ((order_items.quantity * order_items.price) - COALESCE(order_items.discount, 0)) * 0.20 ELSE ((order_items.quantity * order_items.price) - COALESCE(order_items.discount, 0)) - (CASE WHEN order_items.buy_type = "package" THEN (order_items.quantity * COALESCE(books.units_per_package, 1)) ELSE order_items.quantity END * COALESCE(costos.ultimo_costo, 0)) END) as ganancia_bruta_item')
            )
            ->groupBy('order_items.book_id', 'order_items.ebook_id')
            ->get();

        // Inversión del día
        $inversionMes = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->whereBetween('purchase_orders.created_at', [$startDate, $endDate])
            ->where('purchase_orders.status', 'received')
            ->sum(DB::raw('purchase_order_items.quantity * purchase_order_items.unit_cost'));

        $ingresosNetos = $sales->sum('total_neto');
        $utilidadTotal = $sales->sum('ganancia_bruta_item');
        $recuperacion = $inversionMes > 0 ? ($ingresosNetos / $inversionMes) * 100 : 0;

        $totales = [
            'venta_bruta' => (float)$sales->sum('venta_bruta'),
            'descuentos_totales' => (float)$sales->sum('descuentos_item'),
            'ingresos_totales' => (float)$ingresosNetos,
            'inversion_compras' => (float)$inversionMes,
            'utilidad_neta' => (float)$utilidadTotal,
            'porcentaje_recuperacion' => (float)$recuperacion,
        ];

        Carbon::setLocale('es');
        $fechaStr = Carbon::today()->translatedFormat('d M Y');

        // Enviamos correo con PDF adjunto
        Mail::to('21610040@utgz.edu.mx')->send(new DailyFinancialReport($totales, $sales, $fechaStr));

        $this->info('Reporte diario PDF enviado.');
    }
}
