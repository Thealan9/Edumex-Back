<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'book_id',
        'quantity',
        'price',
        'buy_type'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relación con el libro
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
