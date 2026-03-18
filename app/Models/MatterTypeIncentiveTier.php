<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MatterTypeIncentiveTier extends Model
{
    use LogsActivity;

    protected $fillable = [
        'config_id',
        'difficulty',
        'days_from',
        'days_to',
        'percentage',
    ];

    protected $casts = [
        'days_from' => 'integer',
        'days_to' => 'integer',
        'percentage' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(MatterTypeIncentiveConfig::class, 'config_id');
    }
}
