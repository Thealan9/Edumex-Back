<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolumeDiscount extends Model
{
    protected $fillable = [
        'min_quantity', 'max_quantity',
        'discount_percentage', 'is_institutional'
    ];
}
