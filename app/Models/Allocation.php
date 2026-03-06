<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Allocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_id',
        'matter_id',
        'user_id',
        'amount',
        'date',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Allocation $allocation) {
            $allocation->user_id = auth()->id();
            if (!$allocation->date) {
                $allocation->date = now();
            }

            // Automatically set matter_id from the parent fee
            if (!$allocation->matter_id && $allocation->fee_id) {
                $allocation->matter_id = $allocation->fee?->matter_id;
            }
        });

        static::created(function (Allocation $allocation) {
            $allocation->fee?->updateStatus();
        });

        static::updated(function (Allocation $allocation) {
            $allocation->fee?->updateStatus();
        });

        static::deleted(function (Allocation $allocation) {
            $allocation->fee?->updateStatus();
        });
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
