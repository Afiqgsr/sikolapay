<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillBatch extends Model
{
    protected $fillable = [
        'name',
        'description',
        'semester',
        'amount',
        'billing_period',
        'due_date',
        'target_type',
        'target_value',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'billing_period' => 'date',
            'due_date' => 'date',
        ];
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
