<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EbookPurchase extends Model
{
    protected $fillable = [
        'ebook_id',
        'ticket_detail_id',
        'distributor',
        'generated_code'
    ];

    public function ebook()
    {
        return $this->belongsTo(Ebook::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public static function generateCode()
    {

        return strtoupper(Str::random(5)) . '-' . strtoupper(Str::random(5));
    }
}
