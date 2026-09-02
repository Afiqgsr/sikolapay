<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'guardian_id',
        'class_room_id',
        'entry_year',
        'status',
        'nisn',
        'nis',
        'name',
        'gender',
        'birth_date',
        'birth_place',
        'address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(
            Guardian::class
        );
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(
            ClassRoom::class
        );
    }

    public function bills(): HasMany
    {
        return $this->hasMany(
            Bill::class
        );
    }
}