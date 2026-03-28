<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ebook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'image_path', 'isbn', 'level', 'category',
        'price', 'description', 'autor',
        'active', 'pages', 'year', 'edition', 'supplier','platform'
    ];

    public function purchases()
    {
        return $this->hasMany(EbookPurchase::class);
    }

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }

        return asset('images/default-book.png');
    }

}
