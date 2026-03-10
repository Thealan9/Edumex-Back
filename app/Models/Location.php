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

    /**
     * Verifica si hay espacio disponible para una cantidad N
     * Útil para lanzar el Error 409 en el Controlador
     */
    public function hasSpaceFor($quantity): bool
    {
        return ($this->current_capacity + $quantity) <= $this->max_capacity;
    }
}
