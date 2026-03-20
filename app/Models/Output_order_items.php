<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Output_order_items extends Model
{
    protected $fillable = [
        'output_order_id',
        'book_id',
        'quantity',
        'location_id',
    ];

    public function order()
    {
        return $this->belongsTo(Output_orders::class, 'output_order_id');
    }


    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
