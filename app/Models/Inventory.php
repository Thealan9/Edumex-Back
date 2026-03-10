<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = ['book_id', 'location_id', 'quantity'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
