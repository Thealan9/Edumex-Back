<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\EbookPurchase;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function getStats()
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $sevenDaysAgo = Carbon::now()->subDays(6)->startOfDay();

        $weeklySales = Order::where('status', 'paid')
            ->where('created_at', '>=', $sevenDaysAgo)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as daily_total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topBooks = DB::table('order_items')
            ->leftJoin('books', 'order_items.book_id', '=', 'books.id')
            ->leftJoin('ebooks', 'order_items.ebook_id', '=', 'ebooks.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'paid')
            ->select(
                DB::raw('COALESCE(books.title, ebooks.title) as title'),
                DB::raw('SUM(order_items.quantity) as total_sold')
            )
            ->groupBy('title')
            ->orderBy('total_sold', 'desc')
            ->take(5)
            ->get();

        $revenueSource = [
            'physical' => (float) DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereNotNull('order_items.book_id')
                ->where('orders.status', 'paid')
                ->whereBetween('orders.created_at', [$startOfWeek, $endOfWeek])
                ->sum(DB::raw('(order_items.quantity * order_items.price) - order_items.discount')),
            'digital' => (float) DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereNotNull('order_items.ebook_id')
                ->where('orders.status', 'paid')
                ->whereBetween('orders.created_at', [$startOfWeek, $endOfWeek])
                ->sum(DB::raw('(order_items.quantity * order_items.price) - order_items.discount'))
        ];

        $lowStock = Book::select('id', 'title', 'stock_alert')
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM inventories WHERE inventories.book_id = books.id) as total_stock')
            ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM inventories WHERE inventories.book_id = books.id) <= stock_alert')
            ->get();

        $physicalStatsToday = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereNotNull('order_items.book_id')
            ->where('orders.status', 'paid')
            ->whereDate('orders.created_at', $today)
            ->select(
                DB::raw('SUM(CASE WHEN buy_type = "unit" THEN quantity ELSE 0 END) as individual_count'),
                DB::raw('SUM(CASE WHEN buy_type = "package" THEN quantity ELSE 0 END) as package_count')
            )
            ->first();

        return response()->json([
            'stats' => [
                'today_sales' => (float) Order::whereDate('created_at', $today)->sum('total'),
                'ebooks_count_today' => EbookPurchase::whereDate('created_at', $today)->count(),
                'individual_books_today' => (int) ($physicalStatsToday->individual_count ?? 0),
                'packages_today' => (int) ($physicalStatsToday->package_count ?? 0),
                'critical_stock_count' => $lowStock->count(),
                'total_discounts_applied' => (float) Order::whereDate('created_at', $today)->sum('discount'),
            ],
            'charts' => [
                'weekly' => $weeklySales,
                'top_books' => $topBooks,
                'sources' => $revenueSource
            ],
            'alerts' => $lowStock->take(5)->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'current_stock' => (int)$item->total_stock,
                    'limit' => $item->stock_alert
                ];
            })
        ]);
    }
}
