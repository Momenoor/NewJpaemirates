<?php

namespace App\Models;

use App\Services\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Claim extends Model
{
    use HasFactory;

    const OFFICE_SHARE = 'office_share';

    protected $fillable = [
        'date',
        'amount',
        'status',
        'type',
        'recurring',
        'matter_id',
        'user_id',
    ];

    protected $dates = [
        'date'
    ];

    /* protected $with = [
        'cashes'
    ]; */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($query) {
            $query->date = now();
            $query->status = 'unpaid';
            $query->user_id = auth()->id();
        });
    }

    public function matter()
    {
        return $this->belongsTo(Matter::class);
    }

    public function cashes()
    {
        return $this->hasMany(Cash::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = app(Money::class)->getUnformattedNumber($value);
    }

    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = \Str::lower($value);
    }

    public function setRecurringAttribute($value)
    {
        $this->attributes['recurring'] = \Str::lower($value);
    }

    /*     public function getTypeAttribute()
    {
        return $this->type;
        return \Str::lower($this->type);
    } */

    /*  public function getRecurringAttribute()
    {
        return \Str::lower($this->recurring);
    } */

    public function getSumCash()
    {
        return $this->cashes->sum('amount');
    }

    public function getDueAmount()
    {
        return $this->amount - $this->getSumCash();
    }

    public function getClaimAmountAttribute()
    {
        return app(Money::class)->getFormattedNumber($this->amount);
    }

    public function getTypeColorAttribute()
    {

        return config('system.claims.types.' . \Str::lower($this->type) . '.color');
    }

    public function getRecurringColorAttribute()
    {
        return config('system.claims.types.recurring.values.' . \Str::lower($this->recurring) . '.color');
    }

    public function collect()
    {
    }

    public function collectClaim()
    {
    }
}
