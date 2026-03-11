<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Addresses extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'recipient_name',
        'recipient_phone',
        'postal_code',
        'state',
        'municipality',
        'locality',
        'neighborhood',
        'street',
        'external_number',
        'internal_number',
        'references',
        'is_default'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
