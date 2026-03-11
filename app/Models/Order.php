<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id','shipping_details', 'status', 'total'];

    protected $casts = [
        'shipping_details' => 'array'
    ];

    public function items() {
        return $this->hasMany(OrderItem::class);
    }
}
