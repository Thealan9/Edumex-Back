<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'isbn', 'level', 'cost', 'price_unit',
        'units_per_package', 'price_package', 'stock_alert',
        'autor', 'active', 'pages', 'year', 'edition',
        'format', 'size', 'supplier','description'
    ];

    // Relación con el Kardex
    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // Relación con el stock físico en estantes
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    // Accesorio para calcular stock total sumando todas las ubicaciones
    public function getTotalStockAttribute()
    {
        return $this->inventories()->sum('quantity');
    }
}
