<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['code', 'max_capacity', 'current_capacity','active'];

    protected $casts = [
        'active' => 'boolean',
    ];
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
    public function hasSpaceFor($quantity): bool
    {
        return ($this->current_capacity + $quantity) <= $this->max_capacity;
    }

    public function validateSpace($capacity_edit): bool
    {
        if($this->current_capacity <= $capacity_edit){
            return false;
        } else{
            return true;
        }
    }
}
