<?php

namespace App\Http\Controllers\Warehouseman;

use App\Http\Controllers\Controller;
use App\Models\Output_orders;
use Illuminate\Http\Request;

class OutputOrderController extends Controller
{
    public function pending()
    {
        $orders = Output_orders::with(['items.book'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json($orders);
    }
}
