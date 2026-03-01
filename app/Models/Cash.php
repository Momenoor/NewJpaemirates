<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cash extends Model
{
    use HasFactory;

    const PAID = 'paid';
    const UNPAID = 'unpaid';
    const PARTIAL = 'partial';
    const OVERPAID = 'overpaid';


    protected $fillable = [
        'datetime',
        'amount',
        'description',
        'type',
        'matter_id',
        'claim_id',
        'user_id',
    ];



    protected static function boot()
    {
        parent::boot();

        static::creating(function ($query) {
            $query->user_id = auth()->id();
            $query->datetime = now();
        });
    }

    public function matter()
    {
        return $this->belongsTo(Matter::class);
    }

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function statusList()
    {
        return [
            self::PAID,
            self::UNPAID,
            self::OVERPAID,
            self::PARTIAL,
        ];
    }
}
