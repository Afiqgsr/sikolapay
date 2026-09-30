<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bill extends Model
{
    /**
     * Kategori / Jenis Tagihan resmi di SikolaPay.
     * Digunakan secara seragam untuk form tagihan, validasi, dan filter laporan rekap.
     *
     * @var array<int, string>
     */
    public const array TYPES = [
        'SPP Bulanan',
        'Uang Ujian',
        'Uang Gedung',
        'Kegiatan',
        'Seragam',
    ];

    protected $fillable = [
        'bill_batch_id',
        'student_id',
        'name',
        'description',
        'semester',
        'amount',
        'billing_period',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'billing_period' => 'date',
            'due_date' => 'date',
        ];
    }

    public function hasBillingPeriodStarted(): bool
    {
        $billingPeriod = $this->getAttribute('billing_period');

        if (! $billingPeriod instanceof CarbonInterface) {
            return false;
        }

        $currentPeriod = Carbon::now((string) config('app.timezone'))->startOfMonth();

        return $billingPeriod->copy()->startOfMonth()->lessThanOrEqualTo($currentPeriod);
    }

    /**
     * @param  Builder<Bill>  $query
     * @return Builder<Bill>
     */
    public function scopeBillingPeriodStarted(Builder $query): Builder
    {
        $nextPeriod = Carbon::now((string) config('app.timezone'))
            ->startOfMonth()
            ->addMonth();

        return $query
            ->whereNotNull('billing_period')
            ->where('billing_period', '<', $nextPeriod);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            BillBatch::class,
            'bill_batch_id'
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)
            ->latestOfMany();
    }
}
