<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
class SimulateDeliveries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simulate:deliveries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simula el avance de los estatus de envío de forma automática';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. De 'shipped' (Enviado) a 'in_transit' (En camino)
        $toTransit = Order::where('status', 'shipped')
            ->where('shipped_at', '<=', now()->subMinute())
            ->update(['status' => 'in_transit']);

        // 2. De 'in_transit' a 'delivered' (Entregado)
        $toDelivered = Order::where('status', 'in_transit')
            ->where('updated_at', '<=', now()->subMinutes(1))
            ->update(['status' => 'delivered']);

        $this->info("Simulación terminada: $toTransit en tránsito, $toDelivered entregados.");
    }
}
