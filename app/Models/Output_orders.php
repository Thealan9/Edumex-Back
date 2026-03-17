<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Output_orders extends Model
{
    protected $fillable = ['order_number', 'reason', 'notes', 'status', 'created_by'];

    public function items()
    {
        return $this->hasMany(Output_order_items::class,'output_order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
