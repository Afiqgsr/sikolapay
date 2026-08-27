<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'type',
        'code',
        'provider',
        'is_active',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}