<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id','shipping_details',
        'status', 'total','tracking_number',
        'tracking_company','shipped_at'];

    protected $casts = [
        'shipping_details' => 'array',
        'shipped_at' => 'datetime'
    ];

    public function items() {
        return $this->hasMany(OrderItem::class);
    }
}
