<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'bill_id',
        'payer_id',
        'payment_method_id',
        'payment_number',
        'amount',
        'gateway_transaction_id',
        'gateway_reference',
        'gateway_status',
        'payment_url',
        'proof_of_payment',
        'status',
        'paid_at',
        'proof_uploaded_at',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(PaymentVerification::class);
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(PaymentVerification::class)
            ->latestOfMany('processed_at');
    }
    protected function casts(): array
    {
        return [
            'proof_uploaded_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}