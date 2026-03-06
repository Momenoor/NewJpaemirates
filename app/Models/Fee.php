<?php

namespace App\Models;

use App\Enums\FeeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fee extends Model
{
    use HasFactory;

    protected $fillable = [
        'matter_id',
        'user_id',
        'type',
        'amount',
        'date',
        'description',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => FeeStatus::class,
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Fee $fee) {
            $fee->user_id = auth()->id();
            if (!$fee->date) {
                $fee->date = now();
            }
        });
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalAllocatedAttribute(): float
    {
        return (float) $this->allocations()->sum('amount');
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->amount - $this->total_allocated);
    }

    /**
     * Update the fee status based on allocations.
     */
    public function updateStatus(): void
    {
        $allocated = (float) $this->allocations()->sum('amount');
        $total = (float) $this->amount;

        if ($allocated <= 0) {
            $this->status = FeeStatus::UNPAID;
        } elseif ($allocated < $total) {
            $this->status = FeeStatus::PARTIAL;
        } elseif ($allocated == $total) {
            $this->status = FeeStatus::PAID;
        } else {
            $this->status = FeeStatus::OVERPAID;
        }

        if ($this->isDirty('status')) {
            $this->save();
        }

        // Also update matter collection status
        $this->matter?->updateCollectionStatus();
    }
}
