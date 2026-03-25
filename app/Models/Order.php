<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id','shipping_details',
        'status', 'total','tracking_number',
        'tracking_company','shipped_at',
        'subtotal','discount','shipping_cost',
        'payment_id','payment_method',];

    protected $casts = [
        'shipping_details' => 'array',
        'shipped_at' => 'datetime'
    ];

    public function items() {
        return $this->hasMany(OrderItem::class);
    }
}
