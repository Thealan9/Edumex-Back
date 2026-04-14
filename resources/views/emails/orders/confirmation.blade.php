@component('mail::message')
# ¡Hola, {{ $order->user->name ?? 'Cliente' }}!

Gracias por tu compra en **EDUMEX**. Hemos recibido tu pago con éxito y ya estamos procesando tu pedido.

**Detalles de tu orden:**
- **Número de Orden:** #{{ $order->id }}
- **Total Pagado:** ${{ number_format($order->total, 2) }} MXN

@if($order->status === 'delivered')
Tus libros digitales ya están disponibles en tu cuenta.
@else
Te notificaremos en cuanto tus libros sean enviados.
@endif

@component('mail::button', ['url' => env('FRONTEND_URL') . '/home/pedidos'])
Ver mi pedido
@endcomponent

Gracias,<br>
El equipo de {{ config('app.name') }}
@endcomponent
