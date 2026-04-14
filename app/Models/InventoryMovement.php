<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'book_id', 'user_id', 'location_id',
        'type', 'quantity', 'description','reference_id','reference_type'
    ];

    public function book()
    {
        return $this->belongsTo(Book::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class); // El Admin o Warehouseman responsable
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
