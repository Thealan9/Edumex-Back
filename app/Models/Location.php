<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['code', 'max_capacity', 'current_capacity'];

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
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
