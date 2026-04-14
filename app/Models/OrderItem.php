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
        'buy_type',
        'discount',
        'ebook_id',
        'picking_locations'
    ];

    protected $casts = [
        'picking_locations' => 'array',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class)->withTrashed();
    }

    public function ebook()
    {
        return $this->belongsTo(Ebook::class);
    }
}
